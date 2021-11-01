<?php

declare(strict_types=1);

namespace Keboola\LookerWriter\JobRunner;

use Keboola\LookerWriter\Config;
use Keboola\LookerWriter\Exception\LookerWriterException;
use Keboola\StorageApi\Client;
use Psr\Log\LoggerInterface;

abstract class BaseJobRunner
{
    protected LoggerInterface $logger;

    protected Config $config;

    private ?array $services = null;

    public function __construct(Config $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    abstract public function runJob(string $componentId, array $data): array;

    abstract public function processingJobResult(array $jobResult): void;

    protected function getServiceUrl(string $serviceId): string
    {
        $foundServices = array_values(array_filter($this->getServices(), function ($service) use ($serviceId) {
            return $service['id'] === $serviceId;
        }));
        if (empty($foundServices)) {
            throw new LookerWriterException(sprintf('%s service not found', $serviceId));
        }
        return $foundServices[0]['url'];
    }

    private function getServices(): array
    {
        if (!$this->services) {
            $storageClient = new Client([
                'token' => $this->config->getStorageApiToken(),
                'url' => $this->config->getStorageApiUrl(),
            ]);
            $this->services = $storageClient->indexAction()['services'];
        }
        return $this->services;
    }
}
