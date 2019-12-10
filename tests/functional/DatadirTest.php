<?php

declare(strict_types=1);

namespace Keboola\LookerWriter\FunctionalTests;

use Keboola\DatadirTests\DatadirTestCase;

class DatadirTest extends DatadirTestCase
{
    //@todo add test for query tagging
    public function testQueryTagging(): void
    {
//        $queries = $masterConnection->fetchAll(
//            '
//                SELECT
//                    QUERY_TEXT, QUERY_TAG
//                FROM
//                    TABLE(INFORMATION_SCHEMA.QUERY_HISTORY_BY_SESSION())
//                WHERE QUERY_TEXT = \'SELECT current_date;\'
//                ORDER BY START_TIME DESC
//                LIMIT 1
//            '
//        );
//        $runId = sprintf('{"runId":"%s"}', getenv('KBC_RUNID'));
//        $this->assertEquals($runId, $queries[0]['QUERY_TAG']);
    }
}
