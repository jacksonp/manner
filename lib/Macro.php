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

}
