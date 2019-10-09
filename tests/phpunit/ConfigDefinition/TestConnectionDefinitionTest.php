<?php

declare(strict_types=1);

namespace Keboola\LookerWriter\Tests\ConfigDefinition;

use Generator;
use Keboola\LookerWriter\Config;
use Keboola\LookerWriter\ConfigDefinition\TestConnectionDefinition;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class TestConnectionDefinitionTest extends TestCase
{
    /**
     * @dataProvider provideValidConfigs
     */
    public function testValidConfig(array $rawConfig): void
    {
        $config = new Config($rawConfig, new TestConnectionDefinition());
        $this->assertInstanceOf(Config::class, $config);
    }

    public function provideValidConfigs(): Generator
    {
        $fullConfig = $this->getFullConfig();
        yield 'full config' => [$fullConfig];
    }

    /**
     * @dataProvider provideInvalidConfigs
     */
    public function testInvalidConfig(string $expectedExceptionMessage, array $rawConfig): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $config = new Config($rawConfig, new TestConnectionDefinition());
    }

    public function provideInvalidConfigs(): Generator
    {
        $fullConfig = $this->getFullConfig();
        $updated = $fullConfig;
        unset($updated['parameters']['db']);
        yield 'missing db' => [
            'The child node "db" at path "root.parameters" must be configured.',
            $updated,
        ];
    }

    public function getFullConfig(): array
    {
        return [
            'parameters' => [
                'db' => [
                    'host' => 'kebooladev.snowflakecomputing.com',
                    'database' => 'TF_LOOKER_WRITER_TEMP',
                    'user' => 'TF_LOOKER_WRITER_TEMP',
                    '#password' => 'password',
                    'warehouse' => 'DEV',
                    'schema' => 'TF_LOOKER_123456',
                ],
            ],
        ];
    }
}
