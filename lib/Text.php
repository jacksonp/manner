<?php

declare(strict_types=1);

namespace Manner;

class Text
{

    public const ZERO_WIDTH_SPACE_UTF8 = "\xE2\x80\x8B";
    public const ZERO_WIDTH_SPACE_HTML = '&#8203;';

    public static function trim(string $str): string
    {
        return preg_replace('~(^\s+)|(\s+$)~u', '', $str);
    }

    public static function trimAndRemoveZWSUTF8(?string $str): string
    {
        if (is_null($str)) {
            return "";
        }
        return Text::trim(str_replace(Text::ZERO_WIDTH_SPACE_UTF8, '', $str));
    }

}
