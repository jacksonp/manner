<?php


class Macro
{

    static function parseArgString($argString)
    {
        // sometimes get double spaces, see e.g. samba_selinux.8:
        $argString = Replace::preg('~\s+~', ' ', $argString);

        $argString = trim($argString);

        if ($argString === '') {
            return null;
        } else {
            return str_getcsv($argString, ' ');
        }
    }

    static function simplifyRequest(string $string)
    {

        $known = [];

        $known['C++'] = <<<'ROFF'
C\v'-.1v'\h'-1p'\s-2+\h'-1p'+\s0\v'.1v'\h'-1p'
ROFF;

        $known['e'] = <<<'ROFF'
\\k:\h'-(\\n(.wu*8/10-\*(#H+.1m+.3m)'\v'-.8m'\z.\h'.2m+.3m'.\h'|\\n:u'\v'.8m'
ROFF;

        $known['a'] = <<<'ROFF'
\\k:\h'-(\\n(.wu+\w'\(de'u-\*(#H)/2u'\v'-.3n'\f1\z\(de\v'.3n'\h'|\\n:u'\fP
ROFF;

        $known['ð'] = <<<'ROFF'
\h'\*(#H'\(pd\h'-\w'~'u'\v'-.25m'\f2\(hy\fP\v'.25m'\h'-\*(#H'
ROFF;

        $known['Ð'] = <<<'ROFF'
D\\k:\h'-\w'D'u'\v'-.11m'\z\(hy\v'.11m'\h'|\\n:u'
ROFF;

        $known['Æ'] = <<<'ROFF'
A\h'-(\w'A'u*4/10)'E
ROFF;

        $known['æ'] = <<<'ROFF'
a\h'-(\w'a'u*4/10)'e
ROFF;

        return array_search($string, $known) ?: $string;


    }

    public static function massageLine(string $macroLine)
    {
        $macroLine = str_replace(['\\\\'], ['\\'], $macroLine);
        $macroLine = Replace::preg('~^\.\s+~', '.', $macroLine);

        return Replace::preg('~^\.nop ~u', '', $macroLine);
    }

}
