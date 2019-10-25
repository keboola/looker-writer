<?php

declare(strict_types=1);

namespace Keboola\LookerWriter;

use InvalidArgumentException;
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
}
