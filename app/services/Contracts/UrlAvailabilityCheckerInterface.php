<?php

namespace app\services\Contracts;

interface UrlAvailabilityCheckerInterface
{
    public function isAvailable(string $url): bool;
}
