<?php

declare(strict_types=1);

namespace Keboola\LookerWriter;

use InvalidArgumentException;
use Keboola\Component\Config\BaseConfig;
use Keboola\LookerWriter\ConfigDefinition\Node\DbNodeDefinition;
use Keboola\LookerWriter\Exception\LookerWriterException;

class Config extends BaseConfig
{
    public function getDriver(): string
    {
        return $this->getValue(
            [
                'parameters',
                'db',
                'driver',
            ]
        );
    }

    public function getBigQueryDataset(): string
    {
        return $this->getValue(
            [
                'parameters',
                'db',
                'dataset',
            ]
        );
    }

    public function getBigQueryProjectId(): string
    {
        return $this->getValue(
            [
                'parameters',
                'db',
                'service_account',
                'project_id',
            ]
        );
    }

    public function getBigQueryServiceAccount(bool $renamePrivateKey): array
    {
        if ($this->getDriver() !== DbNodeDefinition::DRIVER_BIGQUERY) {
            throw new LookerWriterException('Service account can be used only for BigQuery driver.');
        }

        // Get array from configuration
        $cert =  $this->getValue(
            [
                'parameters',
                'db',
                'service_account',
            ]
        );

        if ($renamePrivateKey) {
            // Rename #private_key -> private_key
            // For Looker is required "private_key"
            // ... but for BigQuery Writer is required "#private_key"
            $cert['private_key'] = $cert['#private_key'];
            unset($cert['#private_key']);
        }

        return $cert;
    }

    public function getLookerCredentialsId(): string
    {
        return $this->getValue(
            [
                'parameters',
                'looker',
                'credentialsId',
            ]
        );
    }
    public function getLookerToken(): string
    {
        return $this->getValue(
            [
                'parameters',
                'looker',
                '#token',
            ]
        );
    }

    public function getLookerHost(): string
    {
        return $this->getValue(
            [
                'parameters',
                'looker',
                'host',
            ]
        );
    }

    public function getConfigId(): string
    {
        $configId = getenv('KBC_CONFIGID');
        if (!$configId) {
            throw new LookerWriterException('KBC_CONFIGID environment variable must be set');
        }
        return $configId;
    }

    public function getConnectionName(): ?string
    {
        $connectionName = $this->getValue(
            [
                'parameters',
                'looker',
                'connectionName',
            ],
            false
        );

        if (!$connectionName) {
            return null;
        }

        return $connectionName;
    }

    public function getDbHost(): string
    {
        return $this->getValue(
            [
                'parameters',
                'db',
                'host',
            ]
        );
    }
    public function getCacheDbHost(): string
    {
        return $this->getValue(
            [
                'parameters',
                'db_cache',
                'host',
            ]
        );
    }

    public function getDbUsername(): string
    {
        return $this->getValue(
            [
                'parameters',
                'db',
                'user',
            ]
        );
    }
    public function getCacheDbUsername(): string
    {
        return $this->getValue(
            [
                'parameters',
                'db_cache',
                'user',
            ]
        );
    }

    public function getDbPassword(): string
    {
        $password = $this->getValue(
            [
                'parameters',
                'db',
                '#password',
            ],
            false
        );
        if ($password === false) {
            $password = $this->getValue(
                [
                    'parameters',
                    'db',
                    'password',
                ],
                false
            );
        }
        return $password;
    }
    public function getCacheDbPassword(): string
    {
        $password = $this->getValue(
            [
                'parameters',
                'db_cache',
                '#password',
            ],
            false
        );
        if ($password === false) {
            $password = $this->getValue(
                [
                    'parameters',
                    'db_cache',
                    'password',
                ]
            );
        }
        return $password;
    }

    public function getDbAccount(): string
    {
        $host = $this->getValue(
            [
                'parameters',
                'db',
                'host',
            ]
        );
        $parts = explode('.', $host, 2);
        return $parts[0];
    }
    public function getCacheDbAccount(): string
    {
        $host = $this->getValue(
            [
                'parameters',
                'db_cache',
                'host',
            ]
        );
        $parts = explode('.', $host, 1);
        return $parts[0];
    }

    public function getDbWarehouse(): string
    {
        return $this->getValue(
            [
                'parameters',
                'db',
                'warehouse',
            ]
        );
    }
    public function getCacheDbWarehouse(): string
    {
        return $this->getValue(
            [
                'parameters',
                'db_cache',
                'warehouse',
            ]
        );
    }

    public function getDbDatabase(): string
    {
        return $this->getValue(
            [
                'parameters',
                'db',
                'database',
            ]
        );
    }

    public function getCacheDbDatabase(): string
    {
        return $this->getValue(
            [
                'parameters',
                'db_cache',
                'database',
            ]
        );
    }

    public function getStorageApiToken(): string
    {
        $token = getenv('KBC_TOKEN');
        if (!$token) {
            throw new LookerWriterException('KBC_TOKEN environment variable must be set');
        }
        return $token;
    }

    public function getStorageApiUrl(): string
    {
        $url = getenv('KBC_URL');
        if (!$url) {
            throw new LookerWriterException('KBC_URL environment variable must be set');
        }
        return $url;
    }

    public function getStageFileProvider(): string
    {

        $fileProvider = getenv('KBC_STAGING_FILE_PROVIDER');
        if (!$fileProvider) {
            throw new LookerWriterException('KBC_STAGING_FILE_PROVIDER environment variable must be set');
        }
        return $fileProvider;
    }

    public function getDbSchemaName(): string
    {
        return $this->getValue(
            [
                'parameters',
                'db',
                'schema',
            ]
        );
    }

    public function getWriterInputMapping(): array
    {
        return $this->getValue([
            'storage',
            'input',
        ]);
    }

    public function getRunId(): string
    {
        $runId = getenv('KBC_RUNID');
        if (!$runId) {
            throw new LookerWriterException('KBC_RUNID environment variable must be set');
        }
        return $runId;
    }

    public function getTables(): array
    {
        return $this->getValue(
            [
                'parameters',
                'tables',
            ]
        );
    }

    public function getCacheSchema(): string
    {
        return $this->getValue(
            [
                'parameters',
                'db_cache',
                'schema',
            ]
        );
    }

    public function getDbSchema(): string
    {
        return $this->getValue(
            [
                'parameters',
                'db',
                'schema',
            ]
        );
    }

    public function hasCacheConnection(): bool
    {
        try {
            $this->getValue(
                [
                    'parameters',
                    'db_cache',
                ]
            );
            return true;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    public function getLocation(): string
    {
        return $this->getValue([
            'parameters',
            'db',
            'location',
        ]);
    }
}
