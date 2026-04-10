<?php

namespace tests\unit\models;

use app\models\ClickStatsSearch;

class ClickStatsSearchTest extends \Codeception\Test\Unit
{
    public function testEmptyMinIpClicksDoesNotCauseTypeErrorAndIsNormalizedToNull(): void
    {
        $model = new ClickStatsSearch();

        $loaded = $model->load([
            'ClickStatsSearch' => [
                'short_code' => 'abc',
                'ip' => '127.0.0.1',
                'min_ip_clicks' => '',
            ],
        ]);

        verify($loaded)->true();
        verify($model->validate())->true();
        verify($model->min_ip_clicks)->null();
    }
}
