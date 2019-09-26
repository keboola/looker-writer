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
}
