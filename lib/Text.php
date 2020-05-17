<?php

declare(strict_types=1);

class Text
{

    const ZERO_WIDTH_SPACE_UTF8 = "\xE2\x80\x8B";
    const ZERO_WIDTH_SPACE_HTML = '&#8203;';

    static function trim(string $str): string
    {
        return preg_replace('~(^\s+)|(\s+$)~u', '', $str);
    }

    static function trimAndRemoveZWSUTF8(?string $str): string
    {
        return Text::trim(str_replace(Text::ZERO_WIDTH_SPACE_UTF8, '', $str));
    }

}
