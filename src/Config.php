<?php

declare(strict_types=1);

namespace Keboola\LookerWriter;

use Keboola\Component\Config\BaseConfig;
use Keboola\LookerWriter\Exception\LookerWriterException;

class Config extends BaseConfig
{

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

    public function getConfigId(): string
    {
        $configId = getenv('KBC_CONFIGID');
        if (!$configId) {
            throw new LookerWriterException('KBC_CONFIGID environment variable must be set');
        }
        return $configId;
    }

    public function getDbHost(): string
    {
        return $this->getValue(
            [
                'parameters',
                'db',
                'looker',
                'host',
            ]
        );
    }
    public function getCacheDbHost(): string
    {
        return $this->getValue(
            [
                'parameters',
                'db',
                'looker_cache',
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
                'looker',
                'user',
            ]
        );
    }
    public function getCacheDbUsername(): string
    {
        return $this->getValue(
            [
                'parameters',
                'db',
                'looker_cache',
                'user',
            ]
        );
    }

    public function getDbPassword(): string
    {
        return $this->getValue(
            [
                'parameters',
                'db',
                'looker',
                '#password',
            ]
        );
    }
    public function getCacheDbPassword(): string
    {
        return $this->getValue(
            [
                'parameters',
                'db',
                'looker_cache',
                '#password',
            ]
        );
    }

    public function getDbAccount(): string
    {
        return $this->getValue(
            [
                'parameters',
                'db',
                'looker',
                'account',
            ]
        );
    }
    public function getCacheDbAccount(): string
    {
        return $this->getValue(
            [
                'parameters',
                'db',
                'looker_cache',
                'account',
            ]
        );
    }

    public function getDbWarehouse(): string
    {
        return $this->getValue(
            [
                'parameters',
                'db',
                'looker',
                'warehouse',
            ]
        );
    }
    public function getCacheDbWarehouse(): string
    {
        return $this->getValue(
            [
                'parameters',
                'db',
                'looker_cache',
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
                'looker',
                'database',
            ]
        );
    }
    public function getCacheDbDatabase(): string
    {
        return $this->getValue(
            [
                'parameters',
                'db',
                'looker_cache',
                'database',
            ]
        );
    }

    public function isForceUpdateConnection(): bool
    {
        return $this->getValue(
            [
                'parameters',
                'forceUpdateConnection',
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

    public function getDbSchemaName(): string
    {
        // @todo
        return 'TF_LOOKER_123456';
    }

    public function getWriterTableConfig(): array
    {
        return [
            [
                'source' => 'in.c-lepsimisto.v1_announcement_ListByCity',
                'destination' => 'in.c-lepsimisto.v1_announcement_ListByCity.csv',
                'limit' => 50,
                'columns' => [],
                'where_values' => [],
                'where_operator' => 'eq',
            ],
        ];
    }

    public function getRunId(): string
    {
        $runId = getenv('KBC_RUNID');
        if (!$runId) {
            throw new LookerWriterException('KBC_RUNID environment variable must be set');
        }
        return $runId;
    }
}
