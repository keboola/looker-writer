<?php

declare(strict_types=1);

namespace Keboola\LookerWriter\ConfigDefinition;

use Keboola\Component\Config\BaseConfigDefinition;
use Keboola\LookerWriter\ConfigDefinition\Node\DbNodeDefinition;
use Keboola\LookerWriter\ConfigDefinition\Node\LookerNodeDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class RegisterToLookerDefinition extends BaseConfigDefinition
{
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersDefinition = parent::getParametersDefinition();
        $parametersDefinition
            ->children()
                ->booleanNode('forceUpdateConnection')
                    ->defaultFalse()
                ->end()
                ->append(new LookerNodeDefinition())
                ->append((new DbNodeDefinition('db'))->isRequired())
                ->append((new DbNodeDefinition('db_cache')))
            ->end()
        ->end()
        ;
        return $parametersDefinition;
    }
}
