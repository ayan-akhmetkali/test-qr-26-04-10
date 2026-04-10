<?php

namespace app\services;

use app\models\Link;
use app\models\LinkClickLog;
use app\services\Exception\LinkNotFoundException;
use Yii;

class RedirectService
{
    /**
     * @return string URL for redirect.
     */
    public function resolveAndTrack(string $shortCode, string $ip, ?string $userAgent = null, ?string $referer = null): string
    {
        $link = Link::findActiveByShortCode($shortCode);
        if ($link === null) {
            throw new LinkNotFoundException('Короткая ссылка не найдена.');
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $log = new LinkClickLog([
                'link_id' => $link->id,
                'ip' => $ip,
                'user_agent' => $userAgent,
                'referer' => $referer,
            ]);

            if (!$log->save()) {
                throw new \RuntimeException('Не удалось сохранить лог перехода.');
            }

            Link::updateAllCounters(['click_count' => 1], ['id' => $link->id]);
            $transaction->commit();
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }

        return $link->original_url;
    }
}
