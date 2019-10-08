<?php

declare(strict_types=1);

namespace Keboola\LookerWriter;

use Keboola\Component\Logger;
use Keboola\StorageApi\Client;
use PHPUnit\Framework\TestCase;

class ComponentTest extends TestCase
{
    /** @var Client */
    private $client;

    public function setUp(): void
    {
        $config = new Config([]);
        $this->client = new Client([
            'token' => $config->getStorageApiToken(),
            'url' => $config->getStorageApiUrl(),
        ]);
    }

    public function testWillConnect(): void
    {
        putenv('KBC_RUNID=' . $this->client->generateRunId());
        putenv('KBC_CONFIGID=12345');
        $component = new Component(new Logger());
        $component->execute();
        $this->expectNotToPerformAssertions();
    }
}
