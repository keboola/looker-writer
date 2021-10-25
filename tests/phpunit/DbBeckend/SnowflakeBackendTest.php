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
    public function testComponentName(string $stageingFileProvider, string $expectedComponentId): void
    {
        putenv('KBC_STAGING_FILE_PROVIDER=' . $stageingFileProvider);
        $config = new Config([], new RunConfigDefinition());

        $snflkBackend = new SnowflakeBackend($config);

        Assert::assertEquals($expectedComponentId, $snflkBackend->getWriterComponentName());
    }

    public function componentNameDataProvider(): Generator
    {
        yield 'azure' => [
            'azure',
            'keboola.wr-snowflake-blob-storage',
        ];

        yield 'aws' => [
            'aws',
            'keboola.wr-db-snowflake',
        ];
    }
}
