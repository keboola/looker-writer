<?php /** @noinspection PhpUnused */

declare(strict_types=1);

namespace Keboola\LookerWriter;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Keboola\Component\BaseComponent;
use Keboola\Component\UserException;
use Keboola\LookerWriter\ConfigDefinition\RunConfigDefinition;
use Keboola\LookerWriter\Exception\LookerWriterException;
use Keboola\SnowflakeDbAdapter\Connection;
use Keboola\SnowflakeDbAdapter\QueryBuilder;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Swagger\Client\Api\ApiAuthApi;
use Swagger\Client\Api\ConnectionApi;
use Swagger\Client\ApiException;
use Swagger\Client\Configuration;
use Swagger\Client\Model\AccessToken;
use Swagger\Client\Model\DBConnection;
use Swagger\Client\Model\DBConnectionOverride;

class Component extends BaseComponent
{
    private const COMPONENT_KEBOOLA_WR_DB_SNOWFLAKE = 'keboola.wr-db-snowflake';
    public const ACTION_RUN = 'run';
    public const ACTION_TEST_CONNECTION = 'testConnection';
    public const ACTION_TEST_LOOKER_CREDENTIALS = 'testLookerCredentials';

    /** @var AccessToken|null */
    private $lookerAccessToken;

    protected function getSyncActions(): array
    {
        $syncActions = parent::getSyncActions();
        $syncActions[self::ACTION_TEST_CONNECTION] = 'handleTestConnection';
        $syncActions[self::ACTION_TEST_LOOKER_CREDENTIALS] = 'handleTestLookerCredentials';
        return $syncActions;
    }

    protected function handleTestConnection(): array
    {
        try {
            $this->testConnection();
        } catch (\Throwable $e) {
            throw new UserException(sprintf("Connection failed: '%s'", $e->getMessage()), 0, $e);
        }

        return [
            'status' => 'success',
        ];
    }

    protected function handleTestLookerCredentials(): array
    {
        $logger = $this->getLogger();
        if (!$logger instanceof Logger) {
            throw new LookerWriterException('Logger must allow setting handlers');
        }
        $testHandler = new TestHandler();
        $logger->pushHandler($testHandler);
        $this->lookerApiLogin();
        return [
            'status' => 'success',
            'messages' => array_map(function (array $record) {
                return $record['message'];
            }, $testHandler->getRecords()),
        ];
    }

    protected function run(): void
    {
        $this->ensureConnectionExists();
        $this->runWriterJob();
    }

    public function ensureConnectionExists(): ?DBConnection
    {
        $dbCredentialsClient = new ConnectionApi(
            $this->getAuthenticatedClient($this->getLookerAccessToken()),
            $this->getLookerConfiguration($this->getAppConfig()->getLookerHost())
        );
        $foundConnections = array_filter(
            $dbCredentialsClient->allConnections('name'),
            function (DBConnection $connection) {
                return $connection->getName() === $this->getLookerConnectionName();
            }
        );
        if (count($foundConnections) === 0) {
            $this->getLogger()->info(sprintf(
                'Creating DB connection in Looker "%s"',
                $this->getLookerConnectionName()
            ));
            return $dbCredentialsClient->createConnection($this->createDbConnectionApiObject());
        }

        $this->getLogger()->info(sprintf(
            'Connection "%s" already exists in Looker',
            $this->getLookerConnectionName()
        ));
        return  null;
    }

    private function runWriterJob(): void
    {
        $this->getLogger()->info('Starting the writer job');
        $client = $this->getSyrupClient();
        $job = $client->runJob(
            self::COMPONENT_KEBOOLA_WR_DB_SNOWFLAKE,
            $this->getSnowflakeWriterConfigData()
        );
        if ($job['status'] === 'error') {
            throw new LookerWriterException(sprintf(
                'Writer job failed with following message: "%s"',
                $job['result']['message']
            ));
        } elseif ($job['status'] !== 'success') {
            throw new LookerWriterException(sprintf(
                'Writer job failed with status "%s" and message: "%s"',
                $job['status'],
                $job['result']['message'] ?? 'No message'
            ));
        }
        $this->getLogger()->info(sprintf('Writer job "%d" succeeded', $job['id']));
        $this->getLogger()->info(sprintf(
            'Data has been written to schema assigned to Looker DB Connection "%s"',
            $this->getLookerConnectionName()
        ));
    }

    private function createDbConnectionApiObject(): DBConnection
    {
        $dbConnection = new DBConnection();
        $config = $this->getAppConfig();
        $dbConnection->setDialectName('snowflake');
        $dbConnection->setName($this->getLookerConnectionName());
        $dbConnection->setHost($config->getDbHost());
        $dbConnection->setUsername($config->getDbUsername());
        $dbConnection->setPassword($config->getDbPassword());
        $dbConnection->setJdbcAdditionalParams(
            sprintf(
                'account=%s&warehouse=%s',
                $config->getDbAccount(),
                $config->getDbWarehouse()
            )
        );
        $dbConnection->setDatabase($config->getDbDatabase());
        $dbConnection->setSchema($config->getDbSchema());
        if ($config->hasCacheConnection()) {
            $dbConnection->setTmpDbName($config->getCacheSchema());
            $pdtContextOverride = new DBConnectionOverride();
            $pdtContextOverride->setContext('pdt');
            $pdtContextOverride->setHost($config->getCacheDbHost());
            $pdtContextOverride->setUsername($config->getCacheDbUsername());
            $pdtContextOverride->setPassword($config->getCacheDbPassword());
            $pdtContextOverride->setJdbcAdditionalParams(sprintf(
                'account=%s&warehouse=%s',
                $config->getCacheDbAccount(),
                $config->getCacheDbWarehouse()
            ));
            $pdtContextOverride->setDatabase($config->getCacheDbDatabase());
            $dbConnection->setPdtContextOverride($pdtContextOverride);
        }
        return $dbConnection;
    }

    protected function getAuthenticatedClient(string $accessToken): Client
    {
        $stack = HandlerStack::create();
        $client = new Client(
            [
                'headers' => [
                    'Authorization' => 'token ' . $accessToken,
                ],
                'handler' => $stack,
            ]
        );
        return $client;
    }

    private function getLookerConnectionName(): string
    {
        return 'wr_looker_' . $this->getAppConfig()->getConfigId();
    }

    private function lookerApiLogin(): void
    {
        $config = $this->getAppConfig();
        $client = new ApiAuthApi(null, $this->getLookerConfiguration($config->getLookerHost()));
        try {
            $this->lookerAccessToken = $client->login(
                $config->getLookerCredentialsId(),
                $config->getLookerToken()
            );
        } catch (ApiException $e) {
            if ($e->getCode() === 404) {
                throw new UserException('Invalid Looker credentials');
            }
            // intentionally throw away exception as it leaks credentials in message
            throw new LookerWriterException('Login to Looker API failed');
        }
        $this->getLogger()->info('Successfully athenticated with Looker API');
    }

    public function getAppConfig(): Config
    {
        /** @var Config $config */
        $config = $this->getConfig();
        return $config;
    }

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    protected function getConfigDefinitionClass(): string
    {
        $rawConfig = $this->getRawConfig();
        $action = $rawConfig['action'] ?? 'run';
        switch ($action) {
            case self::ACTION_TEST_CONNECTION:
                return ConfigDefinition\TestConnectionDefinition::class;
            case self::ACTION_TEST_LOOKER_CREDENTIALS:
                return ConfigDefinition\TestLookerCredentialsDefinition::class;
            case self::ACTION_RUN:
                return RunConfigDefinition::class;
            default:
                throw new LookerWriterException(sprintf('Unknown action "%s"', $action));
        }
    }

    private function getSnowflakeWriterConfigData(): array
    {
        $configData = [
            'configData' => [
                'storage' => [
                    'input' => $this->getAppConfig()->getWriterInputMapping(),
                ],
                'parameters' => [
                    'db' => [
                        'host' => $this->getAppConfig()->getDbHost(),
                        'database' => $this->getAppConfig()->getDbDatabase(),
                        'user' => $this->getAppConfig()->getDbUsername(),
                        'password' => $this->getAppConfig()->getDbPassword(),
                        'schema' => $this->getAppConfig()->getDbSchemaName(),
                        'warehouse' => $this->getAppConfig()->getDbWarehouse(),
                    ],
                    'tables' => $this->getAppConfig()->getTables(),
                ],
            ],
        ];

        $runId = $this->getAppConfig()->getRunId();
        if ($runId) {
            $configData['configData']['parameters']['db']['runId'] = $runId;
        }

        return $configData;
    }

    private function getSyrupClient(): \Keboola\Syrup\Client
    {
        return new \Keboola\Syrup\Client([
            'token' => $this->getAppConfig()->getStorageApiToken(),
            'url' => $this->getSyrupUrl(),
            'super' => 'docker',
            'runId' => $this->getAppConfig()->getRunId(),
        ]);
    }

    private function getSyrupUrl(): string
    {
        $storageClient = new \Keboola\StorageApi\Client([
            'token' => $this->getAppConfig()->getStorageApiToken(),
            'url' => $this->getAppConfig()->getStorageApiUrl(),
        ]);
        $services = $storageClient->indexAction()['services'];
        $serviceId = 'syrup';
        $foundServices = array_values(array_filter($services, function ($service) use ($serviceId) {
            return $service['id'] === $serviceId;
        }));
        if (empty($foundServices)) {
            throw new LookerWriterException(sprintf('%s service not found', $serviceId));
        }
        return $foundServices[0]['url'];
    }

    private function testConnection(): void
    {
        $db = new Connection([
            'database' => $this->getAppConfig()->getDbDatabase(),
            'host' => $this->getAppConfig()->getDbHost(),
            'password' => $this->getAppConfig()->getDbPassword(),
            'user' => $this->getAppConfig()->getDbUsername(),
            'warehouse' => $this->getAppConfig()->getDbWarehouse(),
        ]);
        $db->query('USE SCHEMA ' . QueryBuilder::quoteIdentifier($this->getAppConfig()->getDbSchema()));
    }

    private function getLookerAccessToken(): string
    {
        if (!$this->lookerAccessToken) {
            $this->lookerApiLogin();
        }
        return $this->lookerAccessToken->getAccessToken();
    }

    private function getLookerConfiguration(string $host): Configuration
    {
        $configuration = new Configuration();
        $configuration->setHost($host);
        return $configuration;
    }
}
