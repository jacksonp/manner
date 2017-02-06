<?php
declare(strict_types = 1);

class Replace
{

    static function preg($pattern, $replacement, $subject, $limit = -1, &$count = null)
    {

        $newStr = preg_replace($pattern, $replacement, $subject, $limit, $count);

        if (is_null($newStr)) {
            throw new Exception('preg_replace error on this string: "' . $subject . '".');
        }

        return $newStr;

    }

    static function pregCallback($pattern, $callback, $subject, $limit = -1, &$count = null)
    {

        $newStr = preg_replace_callback($pattern, $callback, $subject, $limit, $count);

        if (is_null($newStr)) {
            throw new Exception('preg_replace_callback error on this string: "' . $subject . '".');
        }

        return $newStr;

    }

}
