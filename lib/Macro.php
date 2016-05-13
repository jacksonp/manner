<?php


class Macro
{

    static function parseArgString($argString)
    {
        // sometimes get double spaces, see e.g. samba_selinux.8:
        $argString = preg_replace('~\s+~', ' ', $argString);

        $argString = trim($argString);

        if (mb_strlen($argString) === 0) {
            return null;
        } else {
            return str_getcsv($argString, ' ');
        }
    }

    static function addStringDefToReplacementArray(string $string, string $val, array &$replacements)
    {

        if (mb_strlen($string) === 1) {
            $replacements['\*' . $string] = $val;
        }
        if (mb_strlen($string) <= 2) {
            $replacements['\\(' . $string]  = $val;
            $replacements['\\*(' . $string] = $val;
        }
        $replacements['\\[' . $string . ']']  = $val;
        $replacements['\\*[' . $string . ']'] = $val;

    }

    static function simplifyRequest(string $string)
    {

        $known = [];

        $known['C++'] = <<<'ROFF'
C\v'-.1v'\h'-1p'\s-2+\h'-1p'+\s0\v'.1v'\h'-1p'
ROFF;

        $known['e'] = <<<'ROFF'
\\k:\h'-(\\n(.wu*8/10-\*(#H+.1m+\*(#F)'\v'-\*(#V'\z.\h'.2m+\*(#F'.\h'|\\n:u'\v'\*(#V'
ROFF;

        $known['a'] = <<<'ROFF'
\\k:\h'-(\\n(.wu+\w'\(de'u-\*(#H)/2u'\v'-.3n'\*(#[\z\(de\v'.3n'\h'|\\n:u'\*(#]
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
        $macroLine = preg_replace('~^\.\s+~', '.', $macroLine);

        return preg_replace('~^\.nop ~u', '', $macroLine);
    }

}
