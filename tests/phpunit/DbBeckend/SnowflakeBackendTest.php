<?php

declare(strict_types=1);

namespace Keboola\LookerWriter\Tests\DbBeckend;

use Generator;
use Keboola\LookerWriter\Config;
use Keboola\LookerWriter\ConfigDefinition\RunConfigDefinition;
use Keboola\LookerWriter\DbBackend\SnowflakeBackend;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class SnowflakeBackendTest extends TestCase
{
    /** @dataProvider componentNameDataProvider */
    public function testComponentName(string $storageUrl, string $expectedComponentId): void
    {
        putenv('KBC_URL=' . $storageUrl);
        $config = new Config([], new RunConfigDefinition());

        $snflkBackend = new SnowflakeBackend($config);

        Assert::assertEquals($expectedComponentId, $snflkBackend->getWriterComponentName());
    }

    public function componentNameDataProvider(): Generator
    {
        yield 'azure-ne' => [
            'connection.north-europe.azure.keboola.com',
            'keboola.wr-snowflake-blob-storage',
        ];

        yield 'azure-csas-prod' => [
            'connection.csas.keboola.cloud',
            'keboola.wr-snowflake-blob-storage',
        ];

        yield 'azure-csas-test' => [
            'connection.csas-test.keboola.com',
            'keboola.wr-snowflake-blob-storage',
        ];

        yield 'aws-us' => [
            'connection.keboola.com',
            'keboola.wr-db-snowflake',
        ];

        yield 'aws-eu' => [
            'connection.eu-central-1.keboola.com',
            'keboola.wr-db-snowflake',
        ];
    }
}
