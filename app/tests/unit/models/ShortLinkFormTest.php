<?php

namespace tests\unit\models;

use app\models\ShortLinkForm;

class ShortLinkFormTest extends \Codeception\Test\Unit
{
    public function testWhitespaceOnlyUrlIsRejected(): void
    {
        $model = new ShortLinkForm(['url' => '   ']);

        verify($model->validate())->false();
        verify($model->errors)->arrayHasKey('url');
    }

    public function testUrlIsTrimmedBeforeValidation(): void
    {
        $model = new ShortLinkForm(['url' => '  https://example.com/path  ']);

        verify($model->validate())->true();
        verify($model->url)->equals('https://example.com/path');
    }
}
