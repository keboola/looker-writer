<?php

declare(strict_types=1);

namespace Keboola\LookerWriter;

use Keboola\Component\Config\BaseConfig;

class Config extends BaseConfig
{

    /**
     * @return string[]
     */
    public function getLookerCredentials(): array
    {
        return $this->getValue(
            [
                'parameters',
                'looker',
            ]
        );
    }
}
