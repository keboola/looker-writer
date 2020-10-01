<?php

declare(strict_types=1);

namespace Keboola\LookerWriter\FunctionalTests;

use Keboola\DatadirTests\DatadirTestCase;
use Keboola\DatadirTests\Exception\DatadirTestsException;
use Keboola\StorageApi\Client;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class DatadirTest extends DatadirTestCase
{
    public const COMPONENT_ID = 'keboola.wr-looker-v2';

    private Client $client;

    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        // Create client
        $this->client = new Client([
            'token' => (string) getenv('KBC_TOKEN'),
            'url' => (string) getenv('KBC_URL'),
        ]);

        // Load Snowflake configuration
        /** @var array $snowflakeConfig */
        $snowflakeConfig = $this->client->apiGet(sprintf(
            'storage/components/%s/configs/%s',
            self::COMPONENT_ID,
            (string) getenv('SNOWFLAKE_BACKEND_CONFIG_ID')
        ));
        $snowflakeParameters = $snowflakeConfig['configuration']['parameters'];
        // Replace encrypted password with decrypted
        $snowflakeParameters['db']['#password'] = (string) getenv('SNOWFLAKE_BACKEND_DB_PASSWORD');
        $snowflakeDb = $snowflakeParameters['db'];
        putenv('SNOWFLAKE_HOST=' . $snowflakeDb['host']);
        putenv('SNOWFLAKE_PORT=' . $snowflakeDb['port']);
        putenv('SNOWFLAKE_DATABASE=' . $snowflakeDb['database']);
        putenv('SNOWFLAKE_SCHEMA=' . $snowflakeDb['schema']);
        putenv('SNOWFLAKE_WAREHOUSE=' . $snowflakeDb['warehouse']);
        putenv('SNOWFLAKE_USER=' . $snowflakeDb['user']);
        putenv('SNOWFLAKE_PASSWORD=' . $snowflakeDb['#password']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Generate KBC_RUNID
        putenv('KBC_RUNID=' . $this->client->generateRunId());
    }

    protected function runScript(string $datadirPath): Process
    {
        $fs = new Filesystem();

        $script = $this->getScript();
        if (!$fs->exists($script)) {
            throw new DatadirTestsException(sprintf(
                'Cannot open script file "%s"',
                $script
            ));
        }

        $runCommand = [
            'php',
            $script,
        ];
        $runProcess = new Process($runCommand);
        $runProcess->setEnv([
            'KBC_DATADIR' => $datadirPath,
            'KBC_TOKEN' => (string) getenv('KBC_TOKEN'),
            'KBC_URL' => (string) getenv('KBC_URL'),
            'KBC_RUNID' => (string) getenv('KBC_RUNID'),
        ]);
        $runProcess->setTimeout(0.0);
        $runProcess->run();
        return $runProcess;
    }
}
