<?php

namespace app\services\Dto;

class ShortLinkResult
{
    public function __construct(
        public string $shortCode,
        public string $shortUrl,
        public string $qrUrl,
        public int $linkId
    ) {
    }
}
