<?php

namespace app\components;

use app\services\Contracts\ShortCodeGeneratorInterface;

class SecureShortCodeGenerator implements ShortCodeGeneratorInterface
{
    private const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function generate(int $length = 8): string
    {
        $length = max(4, $length);
        $alphabetLength = strlen(self::ALPHABET);

        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= self::ALPHABET[random_int(0, $alphabetLength - 1)];
        }

        return $result;
    }
}
