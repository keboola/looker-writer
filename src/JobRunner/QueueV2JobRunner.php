<?php

declare(strict_types=1);

namespace Keboola\LookerWriter\JobRunner;

use Keboola\JobQueueClient\Client;
use Keboola\JobQueueClient\JobData;
use Keboola\LookerWriter\Exception\LookerWriterException;

class QueueV2JobRunner extends BaseJobRunner
{
    public function runJob(string $componentId, array $data): array
    {
        $jobData = new JobData($componentId, null, $data);
        $response = $this->getQueueClient()->createJob($jobData);

        $finished = false;
        while (!$finished) {
            $job = $this->getQueueClient()->getJob($response['id']);
            $finished = $job['isFinished'];
            sleep(10);
        }

        return $job;
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

    private function getQueueClient(): Client
    {
        return new Client(
            $this->logger,
            $this->getQueueUrl(),
            $this->config->getStorageApiToken()
        );
    }

    private function getQueueUrl(): string
    {
        return $this->getServiceUrl('queue');
    }
}
