<?php

declare(strict_types=1);

namespace Keboola\LookerWriter\Tests\ConfigDefinition;

use Generator;
use Keboola\LookerWriter\Config;
use Keboola\LookerWriter\ConfigDefinition\RegisterToLookerDefinition;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class RegisterToLookerDefinitionTest extends TestCase
{
    /**
     * @dataProvider provideValidConfigs
     */
    public function testValidConfig(array $rawConfig): void
    {
        $config = new Config($rawConfig, new RegisterToLookerDefinition());
        $this->assertInstanceOf(Config::class, $config);
    }

    public function provideValidConfigs(): Generator
    {
        $fullConfig = $this->getFullConfig();
        yield 'full config' => [$fullConfig];

        $updated = $fullConfig;
        unset($updated['paramters']['db_cache']);
        yield 'cache db is optional' => [$updated];
    }

    /**
     * @dataProvider provideInvalidConfigs
     */
    public function testInvalidConfig(string $expectedExceptionMessage, array $rawConfig): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        new Config($rawConfig, new RegisterToLookerDefinition());
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

        $updated = $fullConfig;
        unset($updated['parameters']['looker']);
        yield 'missing looker' => [
            'The child node "looker" at path "root.parameters" must be configured.',
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
                'db_cache' => [
                    'host' => 'kebooladev.snowflakecomputing.com',
                    'database' => 'TF_LOOKER_WRITER_TEMP',
                    'user' => 'TF_LOOKER_WRITER_TEMP',
                    '#password' => 'password',
                    'warehouse' => 'DEV',
                    'schema' => 'TF_LOOKER_123456',
                ],
                'looker' => [
                    'credentialsId' => 'nCn6YssWw3HTSwkR2Y3t',
                    '#token' => 'hxchnB2kcjnTRHt6csY9GXXq',
                ],
            ],
        ];
    }
}
