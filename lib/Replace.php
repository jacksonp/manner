<?php

declare(strict_types=1);

namespace Manner;

class Replace
{

    public static function preg($pattern, $replacement, string $subject, $limit = -1, &$count = null)
    {
        $newStr = preg_replace($pattern, $replacement, $subject, $limit, $count);

        if (is_null($newStr)) {
            return self::preg($pattern, $replacement, self::ignoreBadChars($subject), $limit, $count);
        }

        return $newStr;
    }

    public static function pregCallback($pattern, callable $callback, string $subject, $limit = -1, &$count = null)
    {
        $newStr = preg_replace_callback($pattern, $callback, $subject, $limit, $count);

        if (is_null($newStr)) {
            return (self::pregCallback($pattern, $callback, self::ignoreBadChars($subject), $limit, $count));
        }

        return $newStr;
    }

    /**
     * See https://stackoverflow.com/a/3742879
     * @param string $string
     * @return string
     */
    private static function ignoreBadChars(string $string)
    {
        return iconv('UTF-8', 'UTF-8//IGNORE', $string);
    }

}
