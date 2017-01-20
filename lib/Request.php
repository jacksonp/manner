<?php


class Request
{

    static function isEmptyRequest(string $line)
    {
        return in_array(rtrim($line), ['.', '\'', '\\.']);
    }

    static function canSkip(string $line, array $request)
    {
        // Ignore:
        // stray .RE macros,
        // .ad macros that haven't been trimmed as in middle of $lines...
        // '..' Could be the end bit of an "if <> .ig\n[...]\n.." construct, where the .ig doesn't fire.
        // .R man page trying to set font to Regular? (not an actual macro, not needed)
        return
            $request['request'] === 'br.' ||
            in_array($request['request'], [
                'RE',
                'fi',
                'ad',
                'Sh',
                'ns',  // TODO: Hack: see groff_mom.7 - this should be already skipped, but maybe not as in .TQ macro
                'EE',  // strays
                // .man page bugs:
                'sp,',
                'sp2',
                'pp', // spurious, in *_selinux.8 pages
                'RH',
                'Sp',
                'Sp ',
                'TC',
                'TR',
            ]) ||
            (in_array($request['request'], ['R', 'BR', 'TH']) && count($request['arguments']) === 0) || // Empty only
            preg_match('~^\.\.?\s*$~u', $line) ||
            self::isEmptyRequest($line);
    }

    private static function parseArguments(string $argString)
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
            } elseif ($char === ' ' && !$inQuotes) {
                // New arg
                $args[]  = $thisArg;
                $thisArg = '';
            } elseif ($char === '"') {
                $foundQuote = true;
                if ($inQuotes && $i < $stringLength - 1 && mb_substr($argString, $i + 1, 1) === '"') {
                    // When in quotes, "" produces a quote.
                    $thisArg .= '"';
                    ++$i;
                } elseif (($i === 0 || $lastChar === ' ') && !$inQuotes) {
                    $inQuotes = true;
                } elseif ($inQuotes) {
                    if ($i < $stringLength - 1 && mb_substr($argString, $i + 1, 1) !== ' ') {
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

        if ($thisArg !== '' || $foundQuote) { // Want return an empty string, e.g. for .SH ""
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
        return str_replace('\\\\', '\\', $macroLine);
    }

    public static function getLine(array &$lines, int $i, &$callerArguments = null): array
    {
        $man    = Man::instance();
        $return = ['request' => null, 'arguments' => [], 'arg_string' => '', 'raw_arg_string' => ''];
        if (preg_match(
            '~^(?:\\\\?' . preg_quote($man->control_char, '~') . '|\')\s*([^\s\\\\]+)((?:\s+|\\\\).*)?$~ui',
            $lines[$i], $matches)
        ) {
            $return['request'] = $matches[1];
            if (array_key_exists(2, $matches) && !is_null($matches[2])) {
                $return['raw_arg_string'] = ltrim($matches[2]);
                $return['arg_string']     = $man->applyAllReplacements(Request::massageLine($return['raw_arg_string']));
                $return['arguments']      = Request::parseArguments($return['arg_string']);
            }

            $macros = $man->getMacros();
            if (isset($macros[$return['request']])) {
                $man->setRegister('.$', count($return['arguments']));
                if (!is_null($callerArguments)) {
                    foreach ($return['arguments'] as $k => $v) {
                        $return['arguments'][$k] = Roff_Macro::applyReplacements($return['arguments'][$k],
                            $callerArguments);
                    }
                }

                // Make copies of arrays:
                $macroLines           = $macros[$return['request']];
                $macroCallerArguments = $return['arguments'];
//                    Roff::parse($parentNode, $macroLines, $macroCallerArguments);
                foreach ($macroLines as $k => $l) {
                    $macroLines[$k] = Roff_Macro::applyReplacements($l, $macroCallerArguments);
                }
                array_splice($lines, $i, 1, $macroLines);
                return self::getLine($lines, $i, $callerArguments);
            }

        }

        return $return;
    }

    public static function getClass(array $lines, int $i): array
    {
        $return = ['class' => null, 'request' => null, 'arguments' => [], 'arg_string' => '', 'raw_arg_string' => ''];

        $request = self::getLine($lines, $i);

        if ($lines[$i] === '') {
            // empty lines cause a new paragraph, see sar.1
            // See https://www.gnu.org/software/groff/manual/html_node/Implicit-Line-Breaks.html
            if ($i === 0) {
                $return['class'] = 'Request_Skippable';
            } else {
                $return['request'] = 'sp';
                $return['class']   = 'Inline_VerticalSpace';
            }
        } elseif (self::isEmptyRequest($lines[$i])) {
            $return['class'] = 'Request_Skippable';
        } elseif (self::canSkip($lines[$i], $request)) {
            $return['class'] = 'Request_Skippable';
        } elseif (!is_null($request['request'])) {
            $man   = Man::instance();
            $class = $man->getRequestClass($request['request']);
            if ($class !== false) {
                $return          = $request;
                $return['class'] = $class;
            } elseif (in_array($request['request'], Request_Unhandled::requests)) {
                throw new exception('Unhandled request ' . $lines[$i]);
            } elseif (!preg_match('~^[' . preg_quote($man->control_char, '~') . ']~u', $lines[$i])) {
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
