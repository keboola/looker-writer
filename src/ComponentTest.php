<?php declare(strict_types = 1);

namespace Keboola\LookerWriter;

use Keboola\Component\Logger;
use PHPUnit\Framework\TestCase;

class ComponentTest extends TestCase
{

    public function testWillConnect(): void
    {
        $component = new Component(new Logger());
        $component->execute();
    }
}
