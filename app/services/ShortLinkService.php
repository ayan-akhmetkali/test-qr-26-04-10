<?php

namespace app\services;

use app\models\Link;
use app\services\Contracts\ShortCodeGeneratorInterface;
use app\services\Contracts\UrlAvailabilityCheckerInterface;
use app\services\Dto\ShortLinkResult;
use app\services\Exception\LinkCreationException;
use Da\QrCode\QrCode;
use Yii;

class ShortLinkService
{
    private const MAX_GENERATION_ATTEMPTS = 5;
    private const QR_SIZE = 260;

    public function __construct(
        private ShortCodeGeneratorInterface $shortCodeGenerator,
        private UrlAvailabilityCheckerInterface $urlAvailabilityChecker,
        private string $shortUrlPrefix = '/r/'
    ) {
    }

    public function create(string $originalUrl): ShortLinkResult
    {
        if (!$this->urlAvailabilityChecker->isAvailable($originalUrl)) {
            throw new LinkCreationException('Данный URL не доступен.');
        }

        $link = new Link();
        $link->original_url = $originalUrl;
        $link->normalized_url = $originalUrl;
        $link->short_code = $this->generateUniqueShortCode();

        if (!$link->save()) {
            throw new LinkCreationException('Не удалось сохранить ссылку: ' . json_encode($link->getFirstErrors(), JSON_UNESCAPED_UNICODE));
        }

        $shortUrl = rtrim(Yii::$app->request->hostInfo, '/') . $this->shortUrlPrefix . $link->short_code;
        $qrUrl = $this->generateQr($shortUrl);

        return new ShortLinkResult(
            shortCode: $link->short_code,
            shortUrl: $shortUrl,
            qrUrl: $qrUrl,
            linkId: (int) $link->id,
        );
    }

    private function generateUniqueShortCode(int $length = 8): string
    {
        for ($attempt = 0; $attempt < self::MAX_GENERATION_ATTEMPTS; $attempt++) {
            $code = $this->shortCodeGenerator->generate($length);
            $exists = Link::find()->where(['short_code' => $code])->exists();
            if (!$exists) {
                return $code;
            }
        }

        throw new LinkCreationException('Не удалось сгенерировать уникальный short code.');
    }

    private function generateQr(string $payload): string
    {
        $qrCode = new QrCode($payload);
        return $qrCode
            ->setSize(self::QR_SIZE)
            ->setMargin(2)
            ->writeDataUri();
    }
}
