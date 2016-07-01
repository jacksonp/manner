<?php


class Macro
{

    static function parseArgString($argString, $csv = true)
    {
        // sometimes get double spaces, see e.g. samba_selinux.8:
        $argString = Replace::preg('~\s+~', ' ', $argString);

        $argString = trim($argString);

        if ($argString === '') {
            return null;
        } else {
            if ($csv) {
                return str_getcsv($argString, ' ');
            } else {
                // TODO: revisit using this for rc.1
                return explode(' ', $argString);
            }
        }
    }

    static function simplifyRequest(string $string)
    {

        $known = [];

        $known['C++'] = <<<'ROFF'
C\v'-.1v'\h'-1p'\s-2+\h'-1p'+\s0\v'.1v'\h'-1p'
ROFF;

        $known['ð'] = <<<'ROFF'
d\h'-1'`
ROFF;

        $known['Ð'] = <<<'ROFF'
D\h'-1'‐
ROFF;
        $known['Þ'] = <<<'ROFF'
\o'bp'
ROFF;

        $known['þ'] = <<<'ROFF'
\o'LP'
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
