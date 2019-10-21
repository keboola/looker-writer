<?php

declare(strict_types=1);

namespace Keboola\LookerWriter\ConfigDefinition\Node;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class DbNodeDefinition extends ArrayNodeDefinition
{
    public function __construct(string $nodeName)
    {
        parent::__construct($nodeName);
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $this
            ->validate()
                ->always(function ($v) {
                    $issetPlain = isset($v['password']);
                    $issetEncrypted = isset($v['#password']);
                    if ($issetEncrypted && $issetPlain) {
                        throw new InvalidConfigurationException(
                            'Cannot set both encrypted and unencrypted password'
                        );
                    }
                    if (!$issetPlain && !$issetEncrypted) {
                        throw new InvalidConfigurationException(
                            'Either encrypted or plain password must be supplied'
                        );
                    }
                    return $v;
                })
            ->end()
            ->children()
                ->scalarNode('driver')->end() // ignored, but supplied by UI
                ->scalarNode('host')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('port')->end()
                ->scalarNode('warehouse')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('database')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('schema')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('user')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('#password')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('password')
                    ->cannotBeEmpty()
                ->end()
            ->end();
        // @formatter:on
    }
}
