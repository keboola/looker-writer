<?php

declare(strict_types=1);

namespace Keboola\LookerWriter;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Keboola\Component\BaseComponent;
use Swagger\Client\Api\ApiAuthApi;
use Swagger\Client\Api\ConnectionApi;
use Swagger\Client\Model\AccessToken;
use Swagger\Client\Model\DBConnection;
use Swagger\Client\Model\DBConnectionOverride;

class Component extends BaseComponent
{
    /** @var AccessToken */
    private $lookerAccessToken;

    protected function run(): void
    {
        $this->lookerApiLogin();
        $this->ensureConnectionExists();
    }

    public function ensureConnectionExists(): ?DBConnection
    {
        $dbCredentialsClient = new ConnectionApi(
            $this->getAuthenticatedClient($this->lookerAccessToken->getAccessToken())
        );
        $foundConnections = array_filter(
            $dbCredentialsClient->allConnections('name'),
            function (DBConnection $connection) {
                return $connection->getName() === $this->getLookerConnectionName();
            }
        );
        if (count($foundConnections) === 0) {
            $this->getLogger()->info('Creating connection');
            return $dbCredentialsClient->createConnection($this->createDbConnectionApiObject());
        }

        if ($this->getAppConfig()->isForceUpdateConnection()) {
            $this->getLogger()->info('Forced connection update is in effect, updating');
            return $dbCredentialsClient->updateConnection(
                $this->getLookerConnectionName(),
                $this->createDbConnectionApiObject()
            );
        }

        $this->getLogger()->info('Connection already exists');
        return  null;
    }

    private function createDbConnectionApiObject(): DBConnection
    {
        $dbConnection = new DBConnection();
        $config = $this->getAppConfig();
        $dbConnection->setDialectName('snowflake');
        $dbConnection->setName($this->getLookerConnectionName());
        $dbConnection->setHost($config->getDbHost());
        $dbConnection->setUsername($config->getDbUsername());
        $dbConnection->setPassword($config->getDbPassword());
        $dbConnection->setJdbcAdditionalParams(
            sprintf(
                'account=%s&warehouse=%s',
                $config->getDbAccount(),
                $config->getDbWarehouse()
            )
        );
        $dbConnection->setDatabase($config->getDbDatabase());
        $pdtContextOverride = new DBConnectionOverride();
        $pdtContextOverride->setContext('pdt');
        $pdtContextOverride->setHost($config->getCacheDbHost());
        $pdtContextOverride->setUsername($config->getCacheDbUsername());
        $pdtContextOverride->setPassword($config->getCacheDbPassword());
        $pdtContextOverride->setJdbcAdditionalParams(
            sprintf(
                'account=%s&warehouse=%s',
                $config->getCacheDbAccount(),
                $config->getCacheDbWarehouse()
            )
        );
        $pdtContextOverride->setDatabase($config->getCacheDbDatabase());
        $dbConnection->setPdtContextOverride($pdtContextOverride);
        return $dbConnection;
    }

    protected function getAuthenticatedClient(string $accessToken): Client
    {
        $stack = HandlerStack::create();
        $client = new Client(
            [
                'headers' => [
                    'Authorization' => 'token ' . $accessToken,
                ],
                'handler' => $stack,
            ]
        );
        return $client;
    }

    private function getLookerConnectionName(): string
    {
        return 'wr_looker_' . $this->getAppConfig()->getConfigId();
    }

    private function lookerApiLogin(): void
    {
        $config = $this->getAppConfig();
        $client = new ApiAuthApi();
        $this->lookerAccessToken = $client->login(
            $config->getLookerCredentialsId(),
            $config->getLookerToken()
        );
        $this->getLogger()->info('Successfully athenticated with Looker API');
    }

    public function getAppConfig(): Config
    {
        /** @var Config $config */
        $config = $this->getConfig();
        return $config;
    }

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    protected function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }
}
