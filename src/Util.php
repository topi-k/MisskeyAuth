<?php
declare(strict_types=1);

namespace Topi\MisskeyAuth;

class Util
{
    /**
     * Generated UUID for authenting MiAuth
     * 
     * @return string
     */
    const PATTERN = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx';
    public static function GenerateUUID(): string
    {
        $chars = str_split(self::PATTERN);

        foreach ($chars as $i => $char) {
            if ($char === 'x') {
                $chars[$i] = dechex(random_int(0, 15));
            } elseif ($char === 'y') {
                $chars[$i] = dechex(random_int(8, 11));
            }
        }

        return implode('', $chars);
    }
}
