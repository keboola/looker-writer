<?php

declare(strict_types=1);

namespace Keboola\LookerWriter\DbBackend;

use Swagger\Client\Model\DBConnection;

interface DbBackend
{
    public function createDbConnectionApiObject(string $connectionName): DBConnection;

    public function getWriterComponentName(): string;

    public function getTestConnectionConfig(): array;

    public function getWriterConfig(): array;
}
