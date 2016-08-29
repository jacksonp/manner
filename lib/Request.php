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

        if (count($args) === 0) {
            return null;
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
        $macroLine = Replace::preg('~^\.\s+~u', '.', $macroLine);

        return Replace::preg('~^\.nop ~u', '', $macroLine);
    }

    public static function is(string $line, $requests):bool
    {
        if (preg_match('~^(?:\\\\?\.|\')\s*([a-zA-Z]{1,3})~u', $line, $matches)) {
            return in_array($matches[1], (array)$requests);
        }

        return false;
    }

    public static function getClass(array $lines, int $i): array
    {
        $return = ['class' => null, 'request' => null, 'arguments' => null];

        if ($lines[$i] === '') {
            // empty lines cause a new paragraph, see sar.1
            // See https://www.gnu.org/software/groff/manual/html_node/Implicit-Line-Breaks.html
            $return['class'] = 'Block_P';
        } elseif (self::isEmptyRequest($lines[$i])) {
            $return['class'] = 'Request_Skippable';
        } elseif (self::canSkip($lines[$i])) {
            $return['class'] = 'Request_Skippable';
        } elseif (preg_match('~^(?:\\\\?\.|\')\s*([a-zA-Z]{1,3})(.*)$~u', $lines[$i], $matches)) {
            $requestName = $matches[1];
            $man         = Man::instance();
            $class       = $man->getRequestClass($requestName);
            if ($class !== false) {
                $return['class']     = $class;
                $return['request']   = $requestName;
                $return['arguments'] = Request::parseArguments(Request::massageLine($matches[2]));
            } elseif (in_array($requestName, Request_Unhandled::requests)) {
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
