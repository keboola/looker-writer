<?php

declare(strict_types=1);

namespace Keboola\LookerWriter\JobRunner;

use Keboola\LookerWriter\Exception\LookerWriterException;
use Keboola\Syrup\Client;

class SyrupJobRunner extends BaseJobRunner
{
    public function runJob(string $componentId, array $data): array
    {
        return $this->getSyrupClient()->runJob(
            $componentId,
            ['configData' => $data]
        );
    }

    public function runTestConnection(string $componentId, array $data): array
    {
        return $this->getSyrupClient(1)->runSyncAction(
            $this->getDockerRunnerUrl(),
            $componentId,
            'testConnection',
            $data
        );
    }

    public function processingJobResult(array $jobResult): void
    {
        if ($jobResult['status'] === 'error') {
            throw new LookerWriterException(sprintf(
                'Writer job failed with following message: "%s"',
                $jobResult['result']['message']
            ));
        } elseif ($jobResult['status'] !== 'success') {
            throw new LookerWriterException(sprintf(
                'Writer job failed with status "%s" and message: "%s"',
                $jobResult['status'],
                $jobResult['result']['message'] ?? 'No message'
            ));
        }

        $this->logger->info(sprintf('Writer job "%d" succeeded', $jobResult['id']));
    }

    private function getSyrupClient(?int $backoffMaxTries = null): Client
    {
        $config = [
            'token' => $this->config->getStorageApiToken(),
            'url' => $this->getSyrupUrl(),
            'super' => 'docker',
            'runId' => $this->config->getRunId(),
        ];

        if ($backoffMaxTries) {
            $config['backoffMaxTries'] = $backoffMaxTries;
        }

        return new Client($config);
    }

    private function getSyrupUrl(): string
    {
        return $this->getServiceUrl('syrup');
    }

    private function getDockerRunnerUrl(): string
    {
        return $this->getServiceUrl('docker-runner');
    }
}
