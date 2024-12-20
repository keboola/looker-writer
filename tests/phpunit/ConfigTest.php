<?php

declare(strict_types=1);

namespace Keboola\LookerWriter\Tests;

use Keboola\LookerWriter\Config;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ConfigTest extends TestCase
{
    private const TEST_PASSWORD = 'l33tpa$$';

    public function testGetDbAccount(): void
    {
        $config = new Config(
            [
                'parameters' => [
                    'db' => [
                        'host' => 'kebooladev.snowflakecomputing.com',
                    ],
                ],
            ],
            $this->getDummyConfigDefintion()
        );
        $this->assertSame('kebooladev', $config->getDbAccount());
    }

    public function testGetPlainPasswordCorrectly(): void
    {
        $config = new Config(
            [
                'parameters' => [
                    'db' => [
                        'password' => self::TEST_PASSWORD,
                    ],
                ],
            ],
            $this->getDummyConfigDefintion()
        );
        $this->assertSame(self::TEST_PASSWORD, $config->getDbPassword());
    }

    public function testGetEncryptedPasswordCorrectly(): void
    {
        $config = new Config(
            [
                'parameters' => [
                    'db' => [
                        '#password' => self::TEST_PASSWORD,
                    ],
                ],
            ],
            $this->getDummyConfigDefintion()
        );
        $this->assertSame(self::TEST_PASSWORD, $config->getDbPassword());
    }

    public function testGetPlainCacheDbPasswordCorrectly(): void
    {
        $config = new Config(
            [
                'parameters' => [
                    'db_cache' => [
                        'password' => self::TEST_PASSWORD,
                    ],
                ],
            ],
            $this->getDummyConfigDefintion()
        );
        $this->assertSame(self::TEST_PASSWORD, $config->getCacheDbPassword());
    }

    public function testGetEncryptedCacheDbPasswordCorrectly(): void
    {
        $config = new Config(
            [
                'parameters' => [
                    'db_cache' => [
                        '#password' => self::TEST_PASSWORD,
                    ],
                ],
            ],
            $this->getDummyConfigDefintion()
        );
        $this->assertSame(self::TEST_PASSWORD, $config->getCacheDbPassword());
    }

    public function testGetConnectionName(): void
    {
        $config = new Config(
            [
                'parameters' => [
                    'looker' => [
                        'connectionName' => 'test-connection-name',
                    ],
                ],
            ],
            $this->getDummyConfigDefintion()
        );
        $this->assertSame('test-connection-name', $config->getConnectionName());
    }

    public function testGetLocation(): void
    {
        $config = new Config(
            [
                'parameters' => [
                    'db' => [
                        'location' => 'us-east',
                    ],
                ],
            ],
            $this->getDummyConfigDefintion()
        );

        $this->assertSame('us-east', $config->getLocation());
    }

    public function testGetEmptyConnectionName(): void
    {
        $config = new Config(
            [
                'parameters' => [],
            ],
            $this->getDummyConfigDefintion()
        );
        $this->assertNull($config->getConnectionName());
    }

    private function getDummyConfigDefintion(): ConfigurationInterface
    {
        return new class implements ConfigurationInterface
        {
            public function getConfigTreeBuilder(): TreeBuilder
            {
                $treeBuilder = new TreeBuilder('root');
                $treeBuilder->getRootNode()->ignoreExtraKeys(false);
                return $treeBuilder;
            }
        };
    }
}
