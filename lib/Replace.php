<?php
declare(strict_types=1);

class Replace
{

    static function preg($pattern, $replacement, string $subject, $limit = -1, &$count = null)
    {

        $newStr = preg_replace($pattern, $replacement, $subject, $limit, $count);

        if (is_null($newStr)) {
            return self::preg($pattern, $replacement, self::ignoreBadChars($subject), $limit, $count);
        }

        return $newStr;

    }

    static function pregCallback($pattern, callable $callback, string $subject, $limit = -1, &$count = null)
    {

        $newStr = preg_replace_callback($pattern, $callback, $subject, $limit, $count);

        if (is_null($newStr)) {
            return (self::pregCallback($pattern, $callback, self::ignoreBadChars($subject), $limit, $count));
        }

        return $newStr;

    }

    private static function ignoreBadChars(string $string)
    {
        return iconv('UTF-8', 'UTF-8//IGNORE', $string);
    }

}
