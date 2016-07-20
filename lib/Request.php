<?php


class Request
{

    static function canSkip(string $line)
    {
        // Ignore:
        // stray .RE macros,
        // .ad macros that haven't been trimmed as in middle of $lines...
        return
          preg_match('~^\.(RE|fi|ad|Sh|\s*$)~u', $line) or
          in_array($line, [
            '\'',   // empty request
            '..',   // Could be the end bit of an "if <> .ig\n[...]\n.." construct, where the .ig doesn't fire.
            '.ns',  // TODO: Hack: see groff_mom.7 - this should be already skipped, but maybe not as in .TQ macro
            '.EE',  // strays
            '.BR',  // empty
            '.R',   // man page trying to set font to Regular? (not an actual macro, not needed)
              // .man page bugs:
            '.sp,',
            '.sp2',
            '.br.',
            '.pp', // spurious, in *_selinux.8 pages
            '.RH',
            '.Sp',
            '.Sp ',
            '.TH', // Empty .THs only
            '.TC',
            '.TR',
          ]);
    }

    static function parseArguments($argString)
    {

        // sometimes get double spaces, see e.g. samba_selinux.8:
        $argString = Replace::preg('~(?<!\\\\)\s+~', ' ', $argString);

        $argString = ltrim($argString);

        if ($argString === '') {
            return null;
        }

        $args         = [];
        $thisArg      = '';
        $inQuotes     = false;
        $stringLength = mb_strlen($argString);
        $lastChar     = '';
        for ($i = 0; $i < $stringLength; ++$i) {
            $char = mb_substr($argString, $i, 1);
            if ($char === '\\') {
                // Take this char and the next
                $thisArg .= $char . mb_substr($argString, ++$i, 1);
            } elseif ($char === ' ' and !$inQuotes) {
                // New arg
                $args[]  = $thisArg;
                $thisArg = '';
            } elseif ($char === '"') {
                if ($inQuotes and $i < $stringLength - 1 and mb_substr($argString, $i + 1, 1) === '"') {
                    // When in quotes, "" produces a quote.
                    $thisArg .= '"';
                    ++$i;
                } elseif (($i === 0 or $lastChar === ' ') and !$inQuotes) {
                    $inQuotes = true;
                } elseif ($inQuotes) {
                    $inQuotes = false;
                } else {
                    $thisArg .= '"';
                }
            } else {
                $thisArg .= $char;
            }
            $lastChar = $char;
        }

        if ($thisArg !== '') {
            $args[] = $thisArg;
        }

        if (count($args) === 0) {
            return null;
        }

        return $args;

    }

    static function simplifyRequest(string $string)
    {

        $known = [];

        $known['C++'] = <<<'ROFF'
C  + +  
ROFF;

        $known['ð'] = <<<'ROFF'
d \(ga
ROFF;

        $known['Ð'] = <<<'ROFF'
D \(hy
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
        $macroLine = Replace::preg('~^\.\s+~u', '.', $macroLine);

        return Replace::preg('~^\.nop ~u', '', $macroLine);
    }

}
