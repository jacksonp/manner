<?php


class Request
{

    private static $classMap = [
      'SH'  => 'Block_SH',
      'SS'  => 'Block_SS',
      'SY'  => 'Block_SY',
      'P'   => 'Block_P',
      'LP'  => 'Block_P',
      'PP'  => 'Block_P',
      'HP'  => 'Block_P',
      'IP'  => 'Block_IP',
      'TP'  => 'Block_TP',
      'TQ'  => 'Block_TP',
      'ti'  => 'Block_ti',
      'RS'  => 'Block_RS',
      'EX'  => 'Block_EX',
      'Vb'  => 'Block_Vb',
      'ce'  => 'Block_ce',
      'nf'  => 'Block_nf',
      'TS'  => 'Block_TS',
      'TH'  => 'Block_TH',
      'URL' => 'Inline_Link',
      'UR'  => 'Inline_Link',
      'MT'  => 'Inline_Link',
      'R'   => 'Inline_FontOneInputLine',
      'I'   => 'Inline_FontOneInputLine',
      'B'   => 'Inline_FontOneInputLine',
      'SB'  => 'Inline_FontOneInputLine',
      'SM'  => 'Inline_FontOneInputLine',
      'BI'  => 'Inline_AlternatingFont',
      'BR'  => 'Inline_AlternatingFont',
      'IB'  => 'Inline_AlternatingFont',
      'IR'  => 'Inline_AlternatingFont',
      'RB'  => 'Inline_AlternatingFont',
      'RI'  => 'Inline_AlternatingFont',
      'ft'  => 'Inline_ft',
      'br'  => 'Inline_VerticalSpace',
      'sp'  => 'Inline_VerticalSpace',
      'ne'  => 'Inline_VerticalSpace',
    ];

    static function isEmptyRequest(string $line)
    {
        return in_array($line, ['.', '\'', '\\.']);
    }

    static function canSkip(string $line)
    {
        // Ignore:
        // stray .RE macros,
        // .ad macros that haven't been trimmed as in middle of $lines...
        return
          preg_match('~^\.(RE|fi|ad|Sh|\s*$)~u', $line) or
          self::isEmptyRequest($line) or
          in_array($line, [
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

    public static function getClass(array $lines, int $i): array
    {

        if ($lines[$i] === '') {
            // empty lines cause a new paragraph, see sar.1
            // See https://www.gnu.org/software/groff/manual/html_node/Implicit-Line-Breaks.html
            return ['class' => 'Block_P', 'request' => null, 'arguments' => null];
        } elseif (self::isEmptyRequest($lines[$i])) {
            return ['class' => 'Request_Skippable', 'request' => null, 'arguments' => null];
        } elseif (self::canSkip($lines[$i])) {
            return ['class' => 'Request_Skippable', 'request' => null, 'arguments' => null];
        } elseif (preg_match('~^(?:\\\\?\.|\')\s*([a-zA-Z]{1,3})(.*)$~u', $lines[$i], $matches)) {
            if (array_key_exists($matches[1], self::$classMap)) {
                return [
                  'class'     => self::$classMap[$matches[1]],
                  'request'   => $matches[1],
                  'arguments' => Request::parseArguments(Request::massageLine($matches[2])),
                ];
            } elseif (!preg_match('~^[\.]~u', $lines[$i])) {
                // Lenient with things starting with ' to match pre-refactor output...
                // TODO: eventually just skip requests we don't know, whether they start with . or '
                return ['class' => 'Block_Text', 'request' => null, 'arguments' => null];
            } else {
                return ['class' => 'Request_Unknown', 'request' => $matches[1], 'arguments' => null];
            }
        } elseif (Block_TabTable::isStart($lines, $i)) {
            return ['class' => 'Block_TabTable', 'request' => null, 'arguments' => null];
        } elseif (!preg_match('~^[\.]~u', $lines[$i])) {
            return ['class' => 'Block_Text', 'request' => null, 'arguments' => null];
        } else {
            throw new Exception('Could not determine request class: "' . $lines[$i] . '"');
        }

    }

}
