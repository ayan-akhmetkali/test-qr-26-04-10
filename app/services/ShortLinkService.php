<?php

namespace app\services;

use app\models\Link;
use app\services\Contracts\ShortCodeGeneratorInterface;
use app\services\Contracts\UrlAvailabilityCheckerInterface;
use app\services\Dto\ShortLinkResult;
use app\services\Exception\LinkCreationException;
use Da\QrCode\QrCode;
use Yii;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

class ShortLinkService
{
    private const MAX_GENERATION_ATTEMPTS = 5;

    public function __construct(
        private ShortCodeGeneratorInterface $shortCodeGenerator,
        private UrlAvailabilityCheckerInterface $urlAvailabilityChecker,
        private string $shortUrlPrefix = '/r/',
        private string $qrDirectoryAlias = '@webroot/uploads/qr',
        private string $qrPublicPrefix = '/uploads/qr'
    ) {
    }

    public function create(string $originalUrl): ShortLinkResult
    {
        $url = trim($originalUrl);
        if ($url === '') {
            throw new InvalidArgumentException('URL не может быть пустым.');
        }

        if (!$this->urlAvailabilityChecker->isAvailable($url)) {
            throw new LinkCreationException('Данный URL не доступен.');
        }

        $link = new Link();
        $link->original_url = $url;
        $link->normalized_url = $url;
        $link->short_code = $this->generateUniqueShortCode();

        if (!$link->save()) {
            throw new LinkCreationException('Не удалось сохранить ссылку: ' . json_encode($link->getFirstErrors(), JSON_UNESCAPED_UNICODE));
        }

        $shortUrl = rtrim(Yii::$app->request->hostInfo, '/') . $this->shortUrlPrefix . $link->short_code;
        $qrUrl = $this->generateQr($shortUrl, $link->short_code);

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

    private function generateQr(string $payload, string $shortCode): string
    {
        $directory = Yii::getAlias($this->qrDirectoryAlias);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new InvalidConfigException('Не удалось создать директорию для QR-кодов.');
        }

        $filename = $shortCode . '.png';
        $path = $directory . DIRECTORY_SEPARATOR . $filename;
        (new QrCode($payload))->writeFile($path);

        return rtrim($this->qrPublicPrefix, '/') . '/' . $filename;
    }
}
