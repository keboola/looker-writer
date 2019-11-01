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
        $fullConfig = $this->getFullConfig();
        yield 'full config' => [$fullConfig];
    }

    /**
     * @dataProvider provideInvalidConfigs
     */
    public function testInvalidConfig(string $expectedExceptionMessage, array $rawConfig): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $config = new Config($rawConfig, new RunConfigDefinition());
    }

    public function provideInvalidConfigs(): Generator
    {
        $fullConfig = $this->getFullConfig();

        $updated = $fullConfig;
        unset($updated['parameters']['tables']);
        yield 'tables are required' => [
            'The child node "tables" at path "root.parameters" must be configured.',
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
    }

    public function getFullConfig(): array
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
}
