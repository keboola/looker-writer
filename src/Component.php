<?php /** @noinspection PhpUnused */

declare(strict_types=1);

namespace Keboola\LookerWriter;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use Keboola\Component\BaseComponent;
use Keboola\Component\UserException;
use Keboola\LookerWriter\ConfigDefinition\Node\DbNodeDefinition;
use Keboola\LookerWriter\ConfigDefinition\RunConfigDefinition;
use Keboola\LookerWriter\DbBackend\BigQueryBackend;
use Keboola\LookerWriter\DbBackend\DbBackend;
use Keboola\LookerWriter\DbBackend\SnowflakeBackend;
use Keboola\LookerWriter\Exception\LookerWriterException;
use Keboola\LookerWriter\JobRunner\QueueV2JobRunner;
use Keboola\LookerWriter\JobRunner\SyrupJobRunner;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Swagger\Client\Api\ApiAuthApi;
use Swagger\Client\Api\ConnectionApi;
use Swagger\Client\ApiException;
use Swagger\Client\Configuration;
use Swagger\Client\Model\AccessToken;
use Swagger\Client\Model\DBConnection;
use Throwable;

class Component extends BaseComponent
{
    public const ACTION_RUN = 'run';
    public const ACTION_TEST_CONNECTION = 'testConnection';
    public const ACTION_TEST_LOOKER_CREDENTIALS = 'testLookerCredentials';

    private ?AccessToken $lookerAccessToken = null;

    private DbBackend $dbBackend;

    private ?ConnectionApi $dbCredentialsClient = null;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);

        // "parameters.db.driver" is not provided in the config for testLookerCredentials,
        // ... so we cannot create backend wrapper
        if (in_array($this->getConfig()->getAction(), [self::ACTION_RUN, self::ACTION_TEST_CONNECTION])) {
            $this->dbBackend = $this->createBackendWrapper();
        }
    }

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
            $testConnectionConfig = $this->dbBackend->getTestConnectionConfig();
            if ($testConnectionConfig === null) {
                // Test connection is not supported by BigQueryWriter
                return ['success' => 'true', 'message' => 'Not supported.'];
            }
            $jobRunner = new SyrupJobRunner($this->getAppConfig(), $this->getLogger());
            $jobResult = $jobRunner->runTestConnection(
                $this->dbBackend->getWriterComponentName(),
                $testConnectionConfig
            );
        } catch (Throwable $e) {
            $prev = $e->getPrevious();
            $payload = $prev && $prev instanceof GuzzleClientException ?
                @json_decode($prev->getResponse()->getBody()->getContents(), true) :
                null;
            $message = $payload['message'] ?? $e->getMessage();
            throw new UserException(sprintf("Connection failed: '%s'", $message), 0, $e);
        }

        return $jobResult;
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

    public function ensureConnectionExists(): DBConnection
    {
        $name = $this->getLookerConnectionName();
        try {
            return $this->getConnection($name);
        } catch (ApiException $e) {
            // Create connection if not found
            if ($e->getCode() === 404) {
                return $this->createConnection($name);
            }

            throw $e;
        }
    }

    private function runWriterJob(): void
    {
        $this->getLogger()->info('Starting the writer job');

        $verifyToken = $this->getStorageApiClient()->verifyToken();

        if (in_array('queuev2', $verifyToken['owner']['features'])) {
            $jobRunner = new QueueV2JobRunner($this->getAppConfig(), $this->getLogger());
        } else {
            $jobRunner = new SyrupJobRunner($this->getAppConfig(), $this->getLogger());
        }
        $jobResult = $jobRunner->runJob(
            $this->dbBackend->getWriterComponentName(),
            $this->dbBackend->getWriterConfig()
        );

        $jobRunner->processingJobResult($jobResult);

        $this->getLogger()->info(sprintf(
            'Data has been written to schema assigned to Looker DB Connection "%s"',
            $this->getLookerConnectionName()
        ));
    }

    protected function getAuthenticatedClient(string $accessToken): Client
    {
        $stack = HandlerStack::create();
        return new Client(
            [
                'headers' => [
                    'Authorization' => 'token ' . $accessToken,
                ],
                'handler' => $stack,
            ]
        );
    }

    private function getLookerConnectionName(): string
    {
        return strtolower(sprintf(
            'wr_looker_%s',
            $this->getAppConfig()->getConnectionName() ?? $this->getAppConfig()->getConfigId()
        ));
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
            if ($e->getCode() === 502) {
                throw new UserException('Invalid Looker URL host');
            }
            // intentionally throw away exception as it leaks credentials in message
            throw new UserException('Login to Looker API failed, code: ' . $e->getCode());
        }
        $this->getLogger()->info('Successfully authenticated with Looker API');
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

    private function createBackendWrapper(): DbBackend
    {
        $driver = $this->getAppConfig()->getDriver();
        switch ($driver) {
            case DbNodeDefinition::DRIVER_SNOWFLAKE:
                return new SnowflakeBackend($this->getAppConfig());

            case DbNodeDefinition::DRIVER_BIGQUERY:
                return new BigQueryBackend($this->getAppConfig());
        }

        throw new LookerWriterException(sprintf('Unexpected driver "%s".', $driver));
    }

    private function getDbCredentialsClient(): ConnectionApi
    {
        if (!$this->dbCredentialsClient) {
            $this->dbCredentialsClient = new ConnectionApi(
                $this->getAuthenticatedClient($this->getLookerAccessToken()),
                $this->getLookerConfiguration($this->getAppConfig()->getLookerHost())
            );
        }

        return $this->dbCredentialsClient;
    }

    private function getConnection(string $name): DBConnection
    {
        $connection = $this->getDbCredentialsClient()->connection($name);
        $this->getLogger()->info(sprintf('Connection "%s" already exists in Looker', $name));
        return $connection;
    }

    private function createConnection(string $name): DBConnection
    {
        $this->getLogger()->info(sprintf('Creating DB connection in Looker "%s"', $name));
        $apiObject = $this->dbBackend->createDbConnectionApiObject($name);
        return $this
            ->getDbCredentialsClient()
            ->createConnection($apiObject);
    }

    private function getStorageApiClient(): \Keboola\StorageApi\Client
    {
        return new \Keboola\StorageApi\Client([
            'token' => $this->getAppConfig()->getStorageApiToken(),
            'url' => $this->getAppConfig()->getStorageApiUrl(),
        ]);
    }
}
