<?php

declare(strict_types=1);

namespace Keboola\LookerWriter\DbBackend;

use Keboola\LookerWriter\Config;
use Swagger\Client\Model\DBConnection;

class BigQueryBackend implements DbBackend
{
    public const COMPONENT_KEBOOLA_WR_DB_BIGQUERY = 'keboola.wr-google-bigquery-v2';

    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function createDbConnectionApiObject(string $connectionName): DBConnection
    {
        $cert = base64_encode(
            (string) json_encode($this->config->getBigQueryServiceAccount(true))
        );
        $dbConnection = new DBConnection();
        $dbConnection->setDialectName('bigquery_standard_sql');
        $dbConnection->setName($connectionName);
        $dbConnection->setHost($this->config->getBigQueryProjectId());
        $dbConnection->setCertificate($cert);
        $dbConnection->setFileType('json');
        $dbConnection->setDatabase($this->config->getBigQueryDataset());

        return $dbConnection;
    }

    public function getWriterComponentName(): string
    {
        return self::COMPONENT_KEBOOLA_WR_DB_BIGQUERY;
    }

    public function getTestConnectionConfig(): ?array
    {
        // Not supported in BigQuery Writer
        return null;
    }

    public function getWriterConfig(): array
    {
        return [
            'storage' => [
                'input' => $this->config->getWriterInputMapping(),
            ],
            'parameters' => [
                'dataset' => $this->config->getBigQueryDataset(),
                'service_account' => $this->config->getBigQueryServiceAccount(false),
                'tables' => $this->config->getTables(),
                'region' => $this->config->getRegion(),
            ],
        ];
    }
}
