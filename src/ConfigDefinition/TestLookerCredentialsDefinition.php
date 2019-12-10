<?php

declare(strict_types=1);

namespace Keboola\LookerWriter\ConfigDefinition;

use Keboola\Component\Config\BaseConfigDefinition;
use Keboola\LookerWriter\ConfigDefinition\Node\DbNodeDefinition;
use Keboola\LookerWriter\ConfigDefinition\Node\LookerNodeDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class TestLookerCredentialsDefinition extends BaseConfigDefinition
{
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersDefinition = parent::getParametersDefinition();
        $parametersDefinition
            ->children()
                ->append((new LookerNodeDefinition())->isRequired())
            ->end()
        ->end()
        ;
        return $parametersDefinition;
    }
}
