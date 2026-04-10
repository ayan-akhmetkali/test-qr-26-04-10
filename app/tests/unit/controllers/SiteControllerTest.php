<?php

namespace tests\unit\controllers;

use app\controllers\SiteController;
use app\services\Dto\ShortLinkResult;
use app\services\Exception\LinkCreationException;
use app\services\ShortLinkService;
use Yii;

class SiteControllerTest extends \Codeception\Test\Unit
{
    protected function _after(): void
    {
        Yii::$app->request->setBodyParams([]);
    }

    public function testCreateLinkReturnsUnifiedValidationErrorPayload(): void
    {
        $service = $this->createMock(ShortLinkService::class);
        $service->expects($this->never())->method('create');

        $controller = new SiteController('site', Yii::$app, $service);
        Yii::$app->request->setBodyParams(['url' => 'invalid-url']);

        $response = $controller->actionCreateLink();

        verify($response['success'])->false();
        verify($response)->arrayHasKey('message');
        verify($response)->arrayHasKey('errors');
        verify($response['errors'])->arrayHasKey('url');
    }

    public function testCreateLinkReturnsDomainErrorPayloadFromService(): void
    {
        $service = $this->createMock(ShortLinkService::class);
        $service->method('create')->willThrowException(new LinkCreationException('Данный URL не доступен.'));

        $controller = new SiteController('site', Yii::$app, $service);
        Yii::$app->request->setBodyParams(['url' => 'https://example.com']);

        $response = $controller->actionCreateLink();

        verify($response['success'])->false();
        verify($response['message'])->equals('Данный URL не доступен.');
        verify($response['errors'])->equals([]);
    }

    public function testCreateLinkReturnsSuccessPayload(): void
    {
        $service = $this->createMock(ShortLinkService::class);
        $service->method('create')->willReturn(new ShortLinkResult(
            shortCode: 'abc123',
            shortUrl: 'https://host/r/abc123',
            qrUrl: '/uploads/qr/abc123.png',
            linkId: 1
        ));

        $controller = new SiteController('site', Yii::$app, $service);
        Yii::$app->request->setBodyParams(['url' => 'https://example.com']);

        $response = $controller->actionCreateLink();

        verify($response['success'])->true();
        verify($response['short_url'])->equals('https://host/r/abc123');
        verify($response['qr_url'])->equals('/uploads/qr/abc123.png');
        verify($response['short_code'])->equals('abc123');
    }
}
