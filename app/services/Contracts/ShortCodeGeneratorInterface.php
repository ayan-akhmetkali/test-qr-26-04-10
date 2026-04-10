<?php

namespace app\services\Contracts;

interface ShortCodeGeneratorInterface
{
    public function generate(int $length = 8): string;
}
