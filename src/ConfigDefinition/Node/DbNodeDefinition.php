<?php

declare(strict_types=1);

namespace Keboola\LookerWriter\ConfigDefinition\Node;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class DbNodeDefinition extends ArrayNodeDefinition
{
    public function __construct(string $nodeName)
    {
        parent::__construct($nodeName);
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $this
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
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
            ->end();
        // @formatter:on
    }
}
