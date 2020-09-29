<?php

declare(strict_types=1);

namespace Keboola\LookerWriter\DbBackend;

use Keboola\LookerWriter\Config;
use Keboola\SnowflakeDbAdapter\Connection;
use Keboola\SnowflakeDbAdapter\QueryBuilder;
use Swagger\Client\Model\DBConnection;
use Swagger\Client\Model\DBConnectionOverride;

class SnowflakeBackend implements DbBackend
{
    private const COMPONENT_KEBOOLA_WR_DB_SNOWFLAKE = 'keboola.wr-db-snowflake';

    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function createDbConnectionApiObject(string $connectionName): DBConnection
    {
        $dbConnection = new DBConnection();
        $dbConnection->setDialectName('snowflake');
        $dbConnection->setName($connectionName);
        $dbConnection->setHost($this->config->getDbHost());
        $dbConnection->setUsername($this->config->getDbUsername());
        $dbConnection->setPassword($this->config->getDbPassword());
        $dbConnection->setJdbcAdditionalParams(
            sprintf(
                'account=%s&warehouse=%s',
                $this->config->getDbAccount(),
                $this->config->getDbWarehouse()
            )
        );
        $dbConnection->setDatabase($this->config->getDbDatabase());
        $dbConnection->setSchema($this->config->getDbSchema());
        if ($this->config->hasCacheConnection()) {
            $dbConnection->setTmpDbName($this->config->getCacheSchema());
            $pdtContextOverride = new DBConnectionOverride();
            $pdtContextOverride->setContext('pdt');
            $pdtContextOverride->setHost($this->config->getCacheDbHost());
            $pdtContextOverride->setUsername($this->config->getCacheDbUsername());
            $pdtContextOverride->setPassword($this->config->getCacheDbPassword());
            $pdtContextOverride->setJdbcAdditionalParams(sprintf(
                'account=%s&warehouse=%s',
                $this->config->getCacheDbAccount(),
                $this->config->getCacheDbWarehouse()
            ));
            $pdtContextOverride->setDatabase($this->config->getCacheDbDatabase());
            $dbConnection->setPdtContextOverride($pdtContextOverride);
        }
        return $dbConnection;
    }

    public function testConnection(): void
    {
        $db = new Connection([
            'database' => $this->config->getDbDatabase(),
            'host' => $this->config->getDbHost(),
            'password' => $this->config->getDbPassword(),
            'user' => $this->config->getDbUsername(),
            'warehouse' => $this->config->getDbWarehouse(),
        ]);
        $db->query('USE SCHEMA ' . QueryBuilder::quoteIdentifier($this->config->getDbSchema()));
    }

    public function getWriterComponentName(): string
    {
        return self::COMPONENT_KEBOOLA_WR_DB_SNOWFLAKE;
    }

    public function getWriterConfig(): array
    {
        $configData = [
            'configData' => [
                'storage' => [
                    'input' => $this->config->getWriterInputMapping(),
                ],
                'parameters' => [
                    'db' => [
                        'host' => $this->config->getDbHost(),
                        'database' => $this->config->getDbDatabase(),
                        'user' => $this->config->getDbUsername(),
                        'password' => $this->config->getDbPassword(),
                        'schema' => $this->config->getDbSchemaName(),
                        'warehouse' => $this->config->getDbWarehouse(),
                    ],
                    'tables' => $this->config->getTables(),
                ],
            ],
        ];

        $runId = $this->config->getRunId();
        if ($runId) {
            $configData['configData']['parameters']['db']['runId'] = $runId;
        }

        return $configData;
    }
}
