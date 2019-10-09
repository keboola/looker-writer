<?php

declare(strict_types=1);

namespace Keboola\LookerWriter\ConfigDefinition;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class TestConnectionDefinition extends BaseConfigDefinition
{
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersDefinition = parent::getParametersDefinition();
        $parametersDefinition
            ->append((new Node\DbNodeDefinition('db'))->isRequired());
        return $parametersDefinition;
    }
}
