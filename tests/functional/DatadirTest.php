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

    protected function setUp(): void
    {
        parent::setUp();

        // Create client
        $this->client = new Client([
            'token' => (string) getenv('KBC_TOKEN'),
            'url' => (string) getenv('KBC_URL'),
            'backoffMaxTries' => 1,
        ]);

        // Generate KBC_RUNID
        putenv('KBC_RUNID=' . $this->client->generateRunId());

        // Generate KBC_STAGING_FILE_PROVIDER
        putenv('KBC_STAGING_FILE_PROVIDER=aws');

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

        // Load BigQuery configuration
        /** @var array $snowflakeConfig */
        $snowflakeConfig = $this->client->apiGet(sprintf(
            'storage/components/%s/configs/%s',
            self::COMPONENT_ID,
            (string) getenv('BIGQUERY_BACKEND_CONFIG_ID')
        ));
        $bigQueryParameters = $snowflakeConfig['configuration']['parameters'];
        // Replace encrypted private key with decrypted
        $bigQueryParameters['db']['service_account']['#private_key'] =
            (string) getenv('BIGQUERY_BACKEND_PRIVATE_KEY');
        $bigQueryDb = $bigQueryParameters['db'];
        putenv('BIGQUERY_DATASET=' . $bigQueryDb['dataset']);
        putenv('BIGQUERY_SA_TYPE=' . $bigQueryDb['service_account']['type']);
        putenv('BIGQUERY_SA_PROJECT_ID=' . $bigQueryDb['service_account']['project_id']);
        putenv('BIGQUERY_SA_PRIVATE_KEY_ID=' . $bigQueryDb['service_account']['private_key_id']);
        putenv('BIGQUERY_SA_PRIVATE_KEY=' . $bigQueryDb['service_account']['#private_key']);
        putenv('BIGQUERY_SA_CLIENT_EMAIL=' . $bigQueryDb['service_account']['client_email']);
        putenv('BIGQUERY_SA_CLIENT_ID=' . $bigQueryDb['service_account']['client_id']);
        putenv('BIGQUERY_SA_AUTH_URI=' . $bigQueryDb['service_account']['auth_uri']);
        putenv('BIGQUERY_SA_TOKEN_URI=' . $bigQueryDb['service_account']['token_uri']);
        putenv('BIGQUERY_SA_AUTH_X509_CERT_URL=' . $bigQueryDb['service_account']['auth_provider_x509_cert_url']);
        putenv('BIGQUERY_SA_CLIENT_X509_CERT_URL=' . $bigQueryDb['service_account']['client_x509_cert_url']);
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
            'KBC_STAGING_FILE_PROVIDER' => (string) getenv('KBC_STAGING_FILE_PROVIDER'),
        ]);
        $runProcess->setTimeout(60.0);
        $runProcess->run();
        return $runProcess;
    }
}
