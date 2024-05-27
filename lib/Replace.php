<?php

/**
 * manner: convert troff man pages to semantic HTML
 * Copyright (C) 2024  Jackson Pauls
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Manner;

class Replace
{

    public static function preg($pattern, $replacement, string $subject, $limit = -1, &$count = null): array|string
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
    private static function ignoreBadChars(string $string): string
    {
        return iconv('UTF-8', 'UTF-8//IGNORE', $string);
    }

}
