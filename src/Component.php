<?php

declare(strict_types=1);

namespace Keboola\LookerWriter;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Keboola\Component\BaseComponent;
use Keboola\LookerWriter\Exception\LookerWriterException;
use Swagger\Client\Api\ApiAuthApi;
use Swagger\Client\Api\ConnectionApi;
use Swagger\Client\Model\AccessToken;
use Swagger\Client\Model\DBConnection;
use Swagger\Client\Model\DBConnectionOverride;

class Component extends BaseComponent
{
    private const COMPONENT_KEBOOLA_WR_DB_SNOWFLAKE = 'keboola.wr-db-snowflake';

    /** @var AccessToken */
    private $lookerAccessToken;

    protected function run(): void
    {
        $this->lookerApiLogin();
        $this->ensureConnectionExists();
        $this->runWriterJob();
    }

    public function ensureConnectionExists(): ?DBConnection
    {
        $dbCredentialsClient = new ConnectionApi(
            $this->getAuthenticatedClient($this->lookerAccessToken->getAccessToken())
        );
        $foundConnections = array_filter(
            $dbCredentialsClient->allConnections('name'),
            function (DBConnection $connection) {
                return $connection->getName() === $this->getLookerConnectionName();
            }
        );
        if (count($foundConnections) === 0) {
            $this->getLogger()->info('Creating connection');
            return $dbCredentialsClient->createConnection($this->createDbConnectionApiObject());
        }

        if ($this->getAppConfig()->isForceUpdateConnection()) {
            $this->getLogger()->info('Forced connection update is in effect, updating');
            return $dbCredentialsClient->updateConnection(
                $this->getLookerConnectionName(),
                $this->createDbConnectionApiObject()
            );
        }

        $this->getLogger()->info('Connection already exists');
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
        $pdtContextOverride = new DBConnectionOverride();
        $pdtContextOverride->setContext('pdt');
        $pdtContextOverride->setHost($config->getCacheDbHost());
        $pdtContextOverride->setUsername($config->getCacheDbUsername());
        $pdtContextOverride->setPassword($config->getCacheDbPassword());
        $pdtContextOverride->setJdbcAdditionalParams(
            sprintf(
                'account=%s&warehouse=%s',
                $config->getCacheDbAccount(),
                $config->getCacheDbWarehouse()
            )
        );
        $pdtContextOverride->setDatabase($config->getCacheDbDatabase());
        $dbConnection->setPdtContextOverride($pdtContextOverride);
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
        $client = new ApiAuthApi();
        $this->lookerAccessToken = $client->login(
            $config->getLookerCredentialsId(),
            $config->getLookerToken()
        );
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
        return ConfigDefinition::class;
    }

    private function getSnowflakeWriterConfigData(): array
    {
        return [
            'configData' => [
                'storage' => [
                    'input' => [
                        'tables' => $this->getAppConfig()->getWriterTableConfig(),
                        'files' => [],
                    ],
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
                    'tables' => [
                        0 => [
                            'tableId' => 'in.c-lepsimisto.v1_announcement_ListByCity',
                            'dbName' => 'v1_announcement_ListByCity',
                            'export' => true,
                            'items' => [
                                0 => [
                                    'name' => 'id',
                                    'dbName' => 'id',
                                    'type' => 'int',
                                    'size' => null,
                                    'nullable' => null,
                                    'default' => null,
                                ],/*
                                1 => [
                                    'name' => 'name',
                                    'dbName' => 'name',
                                    'type' => 'nvarchar',
                                    'size' => 255,
                                    'nullable' => null,
                                    'default' => null,
                                ],
                                2 => [
                                    'name' => 'glasses',
                                    'dbName' => 'glasses',
                                    'type' => 'nvarchar',
                                    'size' => 255,
                                    'nullable' => null,
                                    'default' => null,
                                ],*/
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getSyrupClient(): \Keboola\Syrup\Client
    {
        return new \Keboola\Syrup\Client([
            'token' => $this->getAppConfig()->getStorageApiToken(),
            'url' => $this->getAppConfig()->getStorageApiUrl(),
            'super' => 'docker',
            'runId' => $this->getAppConfig()->getRunId(),
        ]);
    }
}
