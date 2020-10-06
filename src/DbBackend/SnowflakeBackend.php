<?php

declare(strict_types=1);

namespace Keboola\LookerWriter\DbBackend;

use Keboola\LookerWriter\Config;
use Swagger\Client\Model\DBConnection;
use Swagger\Client\Model\DBConnectionOverride;

class SnowflakeBackend implements DbBackend
{
    public const COMPONENT_KEBOOLA_WR_DB_SNOWFLAKE = 'keboola.wr-db-snowflake';

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

    public function getWriterComponentName(): string
    {
        return self::COMPONENT_KEBOOLA_WR_DB_SNOWFLAKE;
    }

    public function getTestConnectionConfig(): ?array
    {
        return [
            'parameters' => [
                'db' => $this->getDbConfig(),
            ],
        ];
    }

    public function getWriterConfig(): array
    {
        $config = [
            'storage' => [
                'input' => $this->config->getWriterInputMapping(),
            ],
            'parameters' => [
                'db' => $this->getDbConfig(),
                'tables' => $this->config->getTables(),
            ],
        ];

        $runId = $this->config->getRunId();
        if ($runId) {
            $config['parameters']['db']['runId'] = $runId;
        }

        return $config;
    }

    private function getDbConfig(): array
    {
        return [
            'host' => $this->config->getDbHost(),
            'database' => $this->config->getDbDatabase(),
            'user' => $this->config->getDbUsername(),
            '#password' => $this->config->getDbPassword(),
            'schema' => $this->config->getDbSchemaName(),
            'warehouse' => $this->config->getDbWarehouse(),
        ];
    }
}
