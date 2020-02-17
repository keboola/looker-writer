<?php

declare(strict_types=1);

namespace Keboola\LookerWriter\Tests\ConfigDefinition;

use Keboola\LookerWriter\ConfigDefinition\Node\DbNodeDefinition;

use PHPUnit\Framework\TestCase;

class DbNodeDefinitionTest extends TestCase
{
    /** @dataProvider provideIdentifiersAndValidity */
    public function testIsValidSnowflakeUnquotedIdentifier(string $identifier, bool $expectedValidity): void
    {
        $this->assertSame(
            $expectedValidity,
            DbNodeDefinition::isValidSnowflakeUnquotedIdentifier($identifier)
        );
    }

    public function provideIdentifiersAndValidity(): array
    {
        return [
            'MYIDENTIFIER' => ['MYIDENTIFIER', true],
            'MYIDENTIFIER1' => ['MYIDENTIFIER1', true],
            'MY$IDENTIFIER' => ['MY$IDENTIFIER', true],
            '_MY_IDENTIFIER' => ['_MY_IDENTIFIER', true],
            'myidentifier' => ['myidentifier', false],
            'my.identifier' => ['my.identifier', false],
            'my identifier' => ['my identifier', false],
            'My \'Identifier\'' => ['My \'Identifier\'', false],
            '3rd_identifier' => ['3rd_identifier', false],
            '$Identifier' => ['$Identifier', false],
            'идентификатор' => ['идентификатор', false],
        ];
    }
}
