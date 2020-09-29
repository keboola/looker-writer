<?php /** @noinspection PhpUnused */

declare(strict_types=1);

namespace Keboola\LookerWriter;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Keboola\Component\BaseComponent;
use Keboola\Component\UserException;
use Keboola\LookerWriter\ConfigDefinition\RunConfigDefinition;
use Keboola\LookerWriter\DbBackend\DbBackend;
use Keboola\LookerWriter\DbBackend\SnowflakeBackend;
use Keboola\LookerWriter\Exception\LookerWriterException;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Swagger\Client\Api\ApiAuthApi;
use Swagger\Client\Api\ConnectionApi;
use Swagger\Client\ApiException;
use Swagger\Client\Configuration;
use Swagger\Client\Model\AccessToken;
use Swagger\Client\Model\DBConnection;

class Component extends BaseComponent
{
    public const ACTION_RUN = 'run';
    public const ACTION_TEST_CONNECTION = 'testConnection';
    public const ACTION_TEST_LOOKER_CREDENTIALS = 'testLookerCredentials';

    private ?AccessToken $lookerAccessToken;

    private DbBackend $dbBackend;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
        $this->dbBackend = new SnowflakeBackend($this->getAppConfig());
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
            $this->dbBackend->testConnection();
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
            return $dbCredentialsClient->createConnection(
                $this->dbBackend->createDbConnectionApiObject($this->getLookerConnectionName())
            );
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
            $this->dbBackend->getWriterComponentName(),
            $this->dbBackend->getWriterConfig()
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
