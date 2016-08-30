<?php


class Request
{

    static function isEmptyRequest(string $line)
    {
        return in_array(rtrim($line), ['.', '\'', '\\.']);
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

    static function parseArguments(string $argString)
    {

        // sometimes get double spaces, see e.g. samba_selinux.8:
        $argString = Replace::preg('~(?<!\\\\)\s+~', ' ', $argString);

        $argString = ltrim($argString);

        if ($argString === '') {
            return [];
        }

        $args         = [];
        $thisArg      = '';
        $foundQuote   = false;
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
                $foundQuote = true;
                if ($inQuotes and $i < $stringLength - 1 and mb_substr($argString, $i + 1, 1) === '"') {
                    // When in quotes, "" produces a quote.
                    $thisArg .= '"';
                    ++$i;
                } elseif (($i === 0 or $lastChar === ' ') and !$inQuotes) {
                    $inQuotes = true;
                } elseif ($inQuotes) {
                    if ($i < $stringLength - 1 and mb_substr($argString, $i + 1, 1) !== ' ') {
                        // New arg
                        $args[]  = $thisArg;
                        $thisArg = '';
                    }
                    $inQuotes = false;
                } else {
                    $thisArg .= '"';
                }
            } else {
                $thisArg .= $char;
            }
            $lastChar = $char;
        }

        if ($thisArg !== '' or $foundQuote) { // Want return an empty string, e.g. for .SH ""
            $args[] = $thisArg;
        }

        return $args;

    }

    static function simplifyRequest(string $string)
    {

        $known = [];

        $known['C++'] = <<<'ROFF'
C\v'-.1v'\h'-1p'+\h'-1p'+\v'.1v'\h'-1p'
ROFF;

        $known['ð'] = <<<'ROFF'
d\h'-1'\(ga
ROFF;

        $known['Ð'] = <<<'ROFF'
D\h'-1'\(hy
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

        return Replace::preg('~^\.nop ~u', '', $macroLine);
    }

    public static function is(string $line, $requests):bool
    {
        return in_array(Request::get($line)['request'], (array)$requests);
    }

    public static function get(string $line): array
    {
        $return = ['request' => null, 'arguments' => [], 'arg_string' => ''];
        if (preg_match('~^(?:\\\\?\.|\')\s*([-\w]+)(?:\s+(.*))?$~ui', $line, $matches)) {
            $return['request'] = $matches[1];
            if (array_key_exists(2, $matches) and !is_null($matches[2])) {
                $return['arg_string'] = Request::massageLine($matches[2]);
                $return['arguments']  = Request::parseArguments($return['arg_string']);
            }
        }

        return $return;
    }

    public static function getClass(array $lines, int $i): array
    {
        $return = ['class' => null, 'request' => null, 'arguments' => []];

        $request = self::get($lines[$i]);

        if ($lines[$i] === '') {
            // empty lines cause a new paragraph, see sar.1
            // See https://www.gnu.org/software/groff/manual/html_node/Implicit-Line-Breaks.html
            $return['class'] = 'Block_P';
        } elseif (self::isEmptyRequest($lines[$i])) {
            $return['class'] = 'Request_Skippable';
        } elseif (self::canSkip($lines[$i])) {
            $return['class'] = 'Request_Skippable';
        } elseif (!is_null($request['request'])) {
            $man   = Man::instance();
            $class = $man->getRequestClass($request['request']);
            if ($class !== false) {
                $return          = $request;
                $return['class'] = $class;
            } elseif (in_array($request['request'], Request_Unhandled::requests)) {
                throw new exception('Unhandled request ' . $lines[$i]);
            } elseif (!preg_match('~^[\.]~u', $lines[$i])) {
                // Lenient with things starting with ' to match pre-refactor output...
                // TODO: eventually just skip requests we don't know, whether they start with . or '
                $return['class'] = 'Block_Text';
            } else {
                $return['class'] = 'Request_Skippable';
            }
        } elseif (Block_TabTable::isStart($lines, $i)) {
            $return['class'] = 'Block_TabTable';
        } elseif (!preg_match('~^[\.]~u', $lines[$i])) {
            $return['class'] = 'Block_Text';
        } else {
            $return['class'] = 'Request_Skippable';
        }

        return $return;

    }

}
