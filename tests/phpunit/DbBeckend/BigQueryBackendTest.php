<?php

namespace Keboola\LookerWriter\Tests\DbBeckend;

use Keboola\LookerWriter\Config;
use Keboola\LookerWriter\ConfigDefinition\RunConfigDefinition;
use Keboola\LookerWriter\DbBackend\BigQueryBackend;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class BigQueryBackendTest extends TestCase
{
    public function testComponentName(): void
    {
        $config = new Config([], new RunConfigDefinition());

        $bigQueryBackend = new BigQueryBackend($config);

        Assert::assertEquals('keboola.wr-google-bigquery-v2', $bigQueryBackend->getWriterComponentName());
    }
}