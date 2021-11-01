<?php

declare(strict_types=1);

namespace Keboola\LookerWriter\Tests\ConfigDefinition;

use Generator;
use Keboola\LookerWriter\Config;
use Keboola\LookerWriter\ConfigDefinition\TestLookerCredentialsDefinition;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class TestLookerCredentialsDefinitionTest extends TestCase
{
    /**
     * @dataProvider provideValidConfigs
     */
    public function testValidConfig(array $rawConfig): void
    {
        $config = new Config($rawConfig, new TestLookerCredentialsDefinition());
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
        new Config($rawConfig, new TestLookerCredentialsDefinition());
    }

    public function provideInvalidConfigs(): Generator
    {
        $fullConfig = $this->getFullConfig();

        $updated = $fullConfig;
        unset($updated['parameters']['looker']['host']);
        yield 'missing host 1' => [
            'The child config "host" under "root.parameters.looker" must be configured.',
            $updated,
        ];

        $updated = $fullConfig;
        unset($updated['parameters']['looker']['credentialsId']);
        yield 'missing host 2' => [
            'The child config "credentialsId" under "root.parameters.looker" must be configured.',
            $updated,
        ];

        $updated = $fullConfig;
        unset($updated['parameters']['looker']['#token']);
        yield 'missing host 3' => [
            'The child config "#token" under "root.parameters.looker" must be configured.',
            $updated,
        ];

        $updated = $fullConfig;
        unset($updated['parameters']['looker']);
        yield 'missing looker' => [
            'The child config "looker" under "root.parameters" must be configured.',
            $updated,
        ];
    }

    public function getFullConfig(): array
    {
        return [
            'parameters' => [
                'looker' => [
                    'credentialsId' => 'nCn6YssWw3HTSwkR2Y3t',
                    '#token' => 'hxchnB2kcjnTRHt6csY9GXXq',
                    'host' => 'https://keboolads.looker.com/api/3.1',
                ],
            ],
        ];
    }
}
