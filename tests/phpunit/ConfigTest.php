<?php

declare(strict_types=1);

namespace Keboola\LookerWriter\Tests;

use Keboola\LookerWriter\Config;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ConfigTest extends TestCase
{
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
                        'password' => 'l33tpa$$',
                    ],
                ],
            ],
            $this->getDummyConfigDefintion()
        );
        $this->assertSame('l33tpa$$', $config->getDbPassword());
    }

    public function testGetEncryptedPasswordCorrectly(): void
    {
        $config = new Config(
            [
                'parameters' => [
                    'db' => [
                        '#password' => 'l33tpa$$',
                    ],
                ],
            ],
            $this->getDummyConfigDefintion()
        );
        $this->assertSame('l33tpa$$', $config->getDbPassword());
    }

    public function testGetPlainCacheDbPasswordCorrectly(): void
    {
        $config = new Config(
            [
                'parameters' => [
                    'db_cache' => [
                        'password' => 'l33tpa$$',
                    ],
                ],
            ],
            $this->getDummyConfigDefintion()
        );
        $this->assertSame('l33tpa$$', $config->getCacheDbPassword());
    }

    public function testGetEncryptedCacheDbPasswordCorrectly(): void
    {
        $config = new Config(
            [
                'parameters' => [
                    'db_cache' => [
                        '#password' => 'l33tpa$$',
                    ],
                ],
            ],
            $this->getDummyConfigDefintion()
        );
        $this->assertSame('l33tpa$$', $config->getCacheDbPassword());
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
