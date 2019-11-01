<?php

declare(strict_types=1);

namespace Keboola\LookerWriter\ConfigDefinition\Node;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class LookerNodeDefinition extends ArrayNodeDefinition
{
    public function __construct()
    {
        parent::__construct('looker');
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $this
            ->children()
                ->scalarNode('credentialsId')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('#token')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
            ->end()
        ->end();
        // @formatter:on
    }
}
