<?php


class Macro
{

    static function parseArgString(string $argString)
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

}
