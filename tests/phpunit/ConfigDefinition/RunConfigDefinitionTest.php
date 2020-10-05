<?php

declare(strict_types=1);

namespace Keboola\LookerWriter\Tests\ConfigDefinition;

use Generator;
use Keboola\LookerWriter\Config;
use Keboola\LookerWriter\ConfigDefinition\RunConfigDefinition;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class RunConfigDefinitionTest extends TestCase
{
    /**
     * @dataProvider provideValidConfigs
     */
    public function testValidConfig(array $rawConfig): void
    {
        $config = new Config($rawConfig, new RunConfigDefinition());
        $this->assertInstanceOf(Config::class, $config);
    }

    public function provideValidConfigs(): Generator
    {
        $fullConfig = $this->getFullConfigSnowflake();
        yield 'full config' => [$fullConfig];

        $updated = $fullConfig;
        unset($updated['parameters']['db_cache']);
        yield 'db_cache is not required' => [$updated];
    }

    /**
     * @dataProvider provideInvalidConfigsSnowflake
     */
    public function testInvalidConfigSnowflake(string $expectedExceptionMessage, array $rawConfig): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        new Config($rawConfig, new RunConfigDefinition());
    }

    /**
     * @dataProvider provideInvalidConfigsBigQuery
     */
    public function testInvalidConfigBigQuery(string $expectedExceptionMessage, array $rawConfig): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        new Config($rawConfig, new RunConfigDefinition());
    }

    public function provideInvalidConfigsSnowflake(): Generator
    {
        $fullConfig = $this->getFullConfigSnowflake();

        $updated = $fullConfig;
        unset($updated['parameters']['tables']);
        yield 'tables are required' => [
            'The child node "tables" at path "root.parameters" must be configured.',
            $updated,
        ];

        $updated = $fullConfig;
        unset($updated['parameters']['looker']);
        yield 'looker is required' => [
            'The child node "looker" at path "root.parameters" must be configured.',
            $updated,
        ];

        $updated = $fullConfig;
        unset($updated['parameters']['db']);
        yield 'db is required' => [
            'The child node "db" at path "root.parameters" must be configured.',
            $updated,
        ];

        $updated = $fullConfig;
        $updated['parameters']['tables'] = [];
        yield 'tables must not be empty' => [
            'The path "root.parameters.tables" should have at least 1 element(s) defined.',
            $updated,
        ];

        $updated = $fullConfig;
        $updated['parameters']['tables'] = [
            [
                'dbName' => 'customers',
                'export' => true,
                'primaryKey' => [
                ],
            ],
        ];
        yield 'tables items are validated' => [
            'The child node "tableId" at path "root.parameters.tables.0" must be configured.',
            $updated,
        ];

        $updated = $fullConfig;
        $updated['parameters']['db']['database'] = strtolower($updated['parameters']['db']['database']);
        yield 'database is lowercase' => [
            'Invalid configuration for path "root.parameters.db.database": Database name "tf_looker_writer_temp" is not'
            . ' a valid unquoted Snowflake indentifier and will not work with Looker.',
            $updated,
        ];
    }

    public function provideInvalidConfigsBigQuery(): Generator
    {
        $fullConfig = $this->getFullConfigBigQuery();

        $updated = $fullConfig;
        unset($updated['parameters']['tables']);
        yield 'tables are required' => [
            'The child node "tables" at path "root.parameters" must be configured.',
            $updated,
        ];

        $updated = $fullConfig;
        unset($updated['parameters']['looker']);
        yield 'looker is required' => [
            'The child node "looker" at path "root.parameters" must be configured.',
            $updated,
        ];

        $updated = $fullConfig;
        unset($updated['parameters']['db']);
        yield 'db is required' => [
            'The child node "db" at path "root.parameters" must be configured.',
            $updated,
        ];

        $updated = $fullConfig;
        unset($updated['parameters']['db']['json_cert']['type']);
        yield 'db.json_cert.type is required' => [
            'The child node "type" at path "root.parameters.db.json_cert" must be configured.',
            $updated,
        ];
    }

    public function getFullConfigSnowflake(): array
    {
        return [
            'storage' => [
                'input' => [
                    'tables' => [
                        [
                            'source' => 'in.c-keboola-ex-db-snowflake-350887115.customers',
                            'destination' => 'in.c-keboola-ex-db-snowflake-350887115.customers.csv',
                            'columns' => [
                                'id',
                                'name',
                                'country',
                                'country_iso3',
                            ],
                        ],
                        [
                            'source' => 'in.c-keboola-ex-db-snowflake-350887115.orders',
                            'destination' => 'in.c-keboola-ex-db-snowflake-350887115.orders.csv',
                            'columns' => [
                                'id',
                                'customer_id',
                                'employee_id',
                                'product_id',
                                'date_sold',
                                'discount',
                                'discount_value',
                                'amount',
                                'paid_price',
                            ],
                        ],
                        [
                            'source' => 'in.c-keboola-ex-db-snowflake-350887115.employees',
                            'destination' => 'in.c-keboola-ex-db-snowflake-350887115.employees.csv',
                            'columns' => [
                                'id',
                                'first_name',
                                'last_name',
                                'team_id',
                            ],
                        ],
                        [
                            'source' => 'in.c-keboola-ex-db-snowflake-350887115.products',
                            'destination' => 'in.c-keboola-ex-db-snowflake-350887115.products.csv',
                            'columns' => [
                                'id',
                                'name',
                                'main_category',
                                'category',
                                'subcategory',
                                'manufacturer',
                                'url',
                                'url_image',
                                'main_price',
                            ],
                        ],
                    ],
                ],
            ],
            'parameters' => [
                'db' => [
                    'host' => 'kebooladev.snowflakecomputing.com',
                    'database' => 'TF_LOOKER_WRITER_TEMP',
                    'user' => 'TF_LOOKER_WRITER_TEMP',
                    '#password' => 'password',
                    'warehouse' => 'DEV',
                    'schema' => 'TF_LOOKER_123456',
                ],
                'db_cache' => [
                    'host' => 'kebooladev.snowflakecomputing.com',
                    'database' => 'TF_LOOKER_WRITER_TEMP',
                    'user' => 'TF_LOOKER_WRITER_TEMP',
                    '#password' => 'password',
                    'warehouse' => 'DEV',
                    'schema' => 'TF_LOOKER_123456',
                ],
                'looker' => [
                    'credentialsId' => 'nCn6YssWw3HTSwkR2Y3t',
                    '#token' => 'hxchnB2kcjnTRHt6csY9GXXq',
                    'host' => 'https://keboolads.api.looker.com/api/3.1',
                ],
                'tables' => [
                    [
                        'dbName' => 'customers',
                        'export' => true,
                        'tableId' => 'in.c-keboola-ex-db-snowflake-350887115.customers',
                        'items' => [
                            [
                                'name' => 'id',
                                'dbName' => 'ID',
                                'type' => 'varchar',
                                'size' => '255',
                                'nullable' => false,
                                'default' => '',
                            ],
                            [
                                'name' => 'name',
                                'dbName' => 'NAME',
                                'type' => 'varchar',
                                'size' => '255',
                                'nullable' => false,
                                'default' => '',
                            ],
                            [
                                'name' => 'country',
                                'dbName' => 'COUNTRY',
                                'type' => 'varchar',
                                'size' => '255',
                                'nullable' => false,
                                'default' => '',
                            ],
                            [
                                'name' => 'country_iso3',
                                'dbName' => 'COUNTRY_ISO3',
                                'type' => 'varchar',
                                'size' => '255',
                                'nullable' => false,
                                'default' => '',
                            ],
                        ],
                        'primaryKey' => [
                        ],
                    ],
                    [
                        'dbName' => 'orders',
                        'export' => true,
                        'items' => [
                            [
                                'dbName' => 'id',
                                'default' => '',
                                'name' => 'id',
                                'nullable' => false,
                                'size' => '255',
                                'type' => 'varchar',
                            ],
                            [
                                'dbName' => 'customer_id',
                                'default' => '',
                                'name' => 'customer_id',
                                'nullable' => false,
                                'size' => '255',
                                'type' => 'varchar',
                            ],
                            [
                                'dbName' => 'employee_id',
                                'default' => '',
                                'name' => 'employee_id',
                                'nullable' => false,
                                'size' => '255',
                                'type' => 'varchar',
                            ],
                            [
                                'dbName' => 'product_id',
                                'default' => '',
                                'name' => 'product_id',
                                'nullable' => false,
                                'size' => '255',
                                'type' => 'varchar',
                            ],
                            [
                                'dbName' => 'date_sold',
                                'default' => '',
                                'name' => 'date_sold',
                                'nullable' => false,
                                'size' => '255',
                                'type' => 'varchar',
                            ],
                            [
                                'dbName' => 'discount',
                                'default' => '',
                                'name' => 'discount',
                                'nullable' => false,
                                'size' => '255',
                                'type' => 'varchar',
                            ],
                            [
                                'dbName' => 'discount_value',
                                'default' => '',
                                'name' => 'discount_value',
                                'nullable' => false,
                                'size' => '255',
                                'type' => 'varchar',
                            ],
                            [
                                'dbName' => 'amount',
                                'default' => '',
                                'name' => 'amount',
                                'nullable' => true,
                                'size' => '',
                                'type' => 'integer',
                            ],
                            [
                                'dbName' => 'paid_price',
                                'default' => '',
                                'name' => 'paid_price',
                                'nullable' => false,
                                'size' => '255',
                                'type' => 'varchar',
                            ],
                        ],
                        'primaryKey' => [
                        ],
                        'tableId' => 'in.c-keboola-ex-db-snowflake-350887115.orders',
                    ],
                    [
                        'dbName' => 'employees',
                        'export' => true,
                        'tableId' => 'in.c-keboola-ex-db-snowflake-350887115.employees',
                        'items' => [
                            [
                                'name' => 'id',
                                'dbName' => 'ID',
                                'type' => 'varchar',
                                'size' => '255',
                                'nullable' => false,
                                'default' => '',
                            ],
                            [
                                'name' => 'first_name',
                                'dbName' => 'FIRST_NAME',
                                'type' => 'varchar',
                                'size' => '255',
                                'nullable' => false,
                                'default' => '',
                            ],
                            [
                                'name' => 'last_name',
                                'dbName' => 'LAST_NAME',
                                'type' => 'varchar',
                                'size' => '255',
                                'nullable' => false,
                                'default' => '',
                            ],
                            [
                                'name' => 'team_id',
                                'dbName' => 'ID',
                                'type' => 'varchar',
                                'size' => '255',
                                'nullable' => false,
                                'default' => '',
                            ],
                        ],
                        'primaryKey' => [
                        ],
                    ],
                    [
                        'dbName' => 'products',
                        'export' => true,
                        'tableId' => 'in.c-keboola-ex-db-snowflake-350887115.products',
                        'items' => [
                            [
                                'name' => 'id',
                                'dbName' => 'ID',
                                'type' => 'varchar',
                                'size' => '255',
                                'nullable' => false,
                                'default' => '',
                            ],
                            [
                                'name' => 'name',
                                'dbName' => 'NAME',
                                'type' => 'varchar',
                                'size' => '2550',
                                'nullable' => false,
                                'default' => '',
                            ],
                            [
                                'name' => 'main_category',
                                'dbName' => 'MAIN_CATEGORY',
                                'type' => 'varchar',
                                'size' => '255',
                                'nullable' => false,
                                'default' => '',
                            ],
                            [
                                'name' => 'category',
                                'dbName' => 'CATEGORY',
                                'type' => 'varchar',
                                'size' => '255',
                                'nullable' => false,
                                'default' => '',
                            ],
                            [
                                'name' => 'subcategory',
                                'dbName' => 'SUBCATEGORY',
                                'type' => 'varchar',
                                'size' => '255',
                                'nullable' => false,
                                'default' => '',
                            ],
                            [
                                'name' => 'manufacturer',
                                'dbName' => 'MANUFACTURER',
                                'type' => 'varchar',
                                'size' => '255',
                                'nullable' => false,
                                'default' => '',
                            ],
                            [
                                'name' => 'url',
                                'dbName' => 'URL',
                                'type' => 'varchar',
                                'size' => '255',
                                'nullable' => false,
                                'default' => '',
                            ],
                            [
                                'name' => 'url_image',
                                'dbName' => 'URL_IMAGE',
                                'type' => 'varchar',
                                'size' => '255',
                                'nullable' => false,
                                'default' => '',
                            ],
                            [
                                'name' => 'main_price',
                                'dbName' => 'MAIN_PRICE',
                                'type' => 'varchar',
                                'size' => '255',
                                'nullable' => false,
                                'default' => '',
                            ],
                        ],
                        'primaryKey' => [
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getFullConfigBigQuery(): array
    {
        $config = $this->getFullConfigSnowflake();
        $config['parameters']['db'] = [
            'driver' => 'bigquery',
            'json_cert' => [
                'type' => 'service_account',
                'project_id' => 'looker-writer-bigquery',
                'private_key_id' => '12345',
                '#private_key' => "-----BEGIN PRIVATE KEY-----\n....\n-----END PRIVATE KEY-----\n",
                'client_email' => 'looker-writer-bigquery-test@looker-writer-bigquery.iam.gserviceaccount.com',
                'client_id' =>  '103729776760399992476',
                'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                'token_uri' =>  'https://oauth2.googleapis.com/token',
                'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
                'client_x509_cert_url' =>  'https://www.googleapis.com/robot/...',
            ],
        ];
        return $config;
    }
}
