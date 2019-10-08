<?php

declare(strict_types=1);

namespace Keboola\LookerWriter;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ConfigDefinition extends BaseConfigDefinition
{
    public function getDbConnectionNode(string $nodeName): ArrayNodeDefinition
    {
        $dbNode = new ArrayNodeDefinition($nodeName);
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $dbNode
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
        return $dbNode;
    }
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $parametersNode
            ->children()
                ->booleanNode('forceUpdateConnection')
                    ->defaultFalse()
                ->end()
                ->append($this->getDbConnectionNode('db')->isRequired())
                ->append($this->getDbConnectionNode('db_cache'))
                ->arrayNode('looker')
                    ->isRequired()
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
                ->end()
                ->arrayNode('tables')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('tableId')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('dbName')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->booleanNode('incremental')
                                ->defaultValue(false)
                            ->end()
                            ->booleanNode('export')
                                ->defaultValue(true)
                            ->end()
                            ->arrayNode('primaryKey')
                                ->prototype('scalar')
                                ->end()
                            ->end()
                            ->arrayNode('items')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('name')
                                            ->isRequired()
                                            ->cannotBeEmpty()
                                        ->end()
                                        ->scalarNode('dbName')
                                            ->isRequired()
                                            ->cannotBeEmpty()
                                        ->end()
                                        ->scalarNode('type')
                                            ->isRequired()
                                            ->cannotBeEmpty()
                                        ->end()
                                        ->scalarNode('size')
                                        ->end()
                                        ->scalarNode('nullable')
                                        ->end()
                                        ->scalarNode('default')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
        // @formatter:on
        return $parametersNode;
    }
}
