<?php

namespace app\components;

use app\services\Contracts\UrlAvailabilityCheckerInterface;

class UrlAvailabilityChecker implements UrlAvailabilityCheckerInterface
{
    public function __construct(
        private int $timeout = 5,
        private int $maxRedirects = 3
    ) {
    }

    public function isAvailable(string $url): bool
    {
        $curl = curl_init($url);
        if ($curl === false) {
            return false;
        }

        curl_setopt_array($curl, [
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => $this->maxRedirects,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_RETURNTRANSFER => true,
        ]);

        curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $hasError = curl_errno($curl) !== 0;
        curl_close($curl);

        return !$hasError && $statusCode >= 200 && $statusCode < 400;
    }
}
