<?php

declare(strict_types=1);

namespace Keboola\LookerWriter\ConfigDefinition\Node;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class DbNodeDefinition extends ArrayNodeDefinition
{
    public const DRIVER_SNOWFLAKE = 'snowflake';
    public const DRIVER_BIGQUERY = 'bigquery';
    public const SNOWFLAKE_REQUIRED_NODES = ['host', 'warehouse', 'database', 'schema', 'user'];
    public const BIGQUERY_REQUIRED_NODES = ['service_account'];

    public function __construct(string $nodeName)
    {
        parent::__construct($nodeName);
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $this
            ->validate()
                ->always(function (array $v) {
                    if ($v['driver'] === self::DRIVER_SNOWFLAKE) {
                        $v = self::validateSnowflakeDriver($v);
                    } elseif ($v['driver'] === self::DRIVER_BIGQUERY) {
                        $v = self::validateBigQueryDriver($v);
                    } else {
                        throw new InvalidConfigurationException(sprintf(
                            'Unexpected driver "%s".',
                            $v['driver'] ?? ''
                        ));
                    }

                    return $v;
                })
            ->end();

        $this
            ->children()
                ->enumNode('driver')
                    ->values([self::DRIVER_SNOWFLAKE, self::DRIVER_BIGQUERY])
                    ->defaultValue(self::DRIVER_SNOWFLAKE)
                ->end();
        // @formatter:on

        $this->addSnowflakeNodes($this->children());
        $this->addBigQueryNodes($this->children());
    }

    private function addSnowflakeNodes(NodeBuilder $builder): void
    {
        // Required keys are defined in: self::SNOWFLAKE_REQUIRED_NODES
        // @formatter:off
        $builder
            ->scalarNode('host')
                ->cannotBeEmpty()
            ->end()
            ->scalarNode('port')->end()
            ->scalarNode('warehouse')
                ->cannotBeEmpty()
            ->end()
            ->scalarNode('database')
                ->cannotBeEmpty()
                ->validate()
                    ->ifTrue(function ($v) {
                        if (!$v) {
                            return false;
                        }
                        // looker does not use quoted identifiers
                        // https://docs.snowflake.net/manuals/sql-reference/identifiers-syntax.html#identifier-requirements
                        return !self::isValidSnowflakeUnquotedIdentifier($v);
                    })
                    ->thenInvalid(
                        'Database name %s is not a valid unquoted Snowflake indentifier and will not '
                        . 'work with Looker.'
                    )
                ->end()
            ->end()
            ->scalarNode('schema')
                ->cannotBeEmpty()
            ->end()
            ->scalarNode('user')
                ->cannotBeEmpty()
            ->end()
            ->scalarNode('#password')
                ->cannotBeEmpty()
            ->end()
            ->scalarNode('password')
                ->cannotBeEmpty()
            ->end();
        // @formatter:on
    }

    private function addBigQueryNodes(NodeBuilder $builder): void
    {
        // Required keys are defined in: self::BIGQUERY_REQUIRED_NODES
        // @formatter:off
        $builder
            ->arrayNode('service_account')
            ->children()
                // All these values are part of BigQuery JSON certificate file
                // and are used to create the Looker connection
                ->scalarNode('type')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('project_id')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('private_key_id')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('#private_key')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('client_email')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('client_id')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('auth_uri')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('token_uri')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('auth_provider_x509_cert_url')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('client_x509_cert_url')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end();
        // @formatter:on
    }


    public static function isValidSnowflakeUnquotedIdentifier(string $identifier): bool
    {
        // based on https://docs.snowflake.net/manuals/sql-reference/identifiers-syntax.html#identifier-requirements
        $regex = '/^[A-Za-z_][A-Za-z0-9\$_]*$/';

        if (preg_match($regex, $identifier) === 0) {
            // does not match unquoted identifier as defined in docs
            return false;
        }

        if (strtoupper($identifier) !== $identifier) {
            // would be changed by uppercasing by SNFLK
            return false;
        };

        return true;
    }

    public static function validateSnowflakeDriver(array $v): array
    {
        // Validate required
        foreach (self::SNOWFLAKE_REQUIRED_NODES as $key) {
            if (!isset($v[$key])) {
                throw new InvalidConfigurationException(sprintf(
                    'The child node "%s" at path "root.parameters.db" ' .
                    'must be configured for "snowflake" driver',
                    $key
                ));
            }
        }

        // Valida password
        $issetPlain = isset($v['password']);
        $issetEncrypted = isset($v['#password']);
        if ($issetEncrypted && $issetPlain) {
            throw new InvalidConfigurationException(
                'Cannot set both encrypted and unencrypted password'
            );
        }
        if (!$issetPlain && !$issetEncrypted) {
            throw new InvalidConfigurationException(
                'Either encrypted or plain password must be supplied'
            );
        }

        return $v;
    }

    public static function validateBigQueryDriver(array $v): array
    {
        // Validate required
        foreach (self::BIGQUERY_REQUIRED_NODES as $key) {
            if (!isset($v[$key])) {
                throw new InvalidConfigurationException(sprintf(
                    'The child node "%s" at path "root.parameters.db" ' .
                    'must be configured for "bigquery" driver',
                    $key
                ));
            }
        }

        return $v;
    }
}
