<?php
declare(strict_types = 1);

class Request
{

    private static function parseArguments(string $argString): array
    {

        // sometimes get double spaces, see e.g. samba_selinux.8:
        $argString = Replace::preg('~(?<!\\\\) +~', ' ', $argString);

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

    public static function massageLine(string $macroLine): string
    {
        return str_replace('\\\\', '\\', $macroLine);
    }

    public static function peepAt($line): array
    {
        $return       = ['name' => null, 'raw_arg_string' => ''];
        $man          = Man::instance();
        $controlChars = preg_quote($man->control_char, '~') . '|' . preg_quote($man->control_char_2, '~');
        if (preg_match(
            '~^(?:\\\\?' . $controlChars . ')\s*([^\s\\\\]*)((?:\s+|\\\\).*)?$~ui',
            $line, $matches)
        ) {
            $return['name'] = $matches[1];
            if (array_key_exists(2, $matches) && !is_null($matches[2])) {
                $return['raw_arg_string'] = ltrim($matches[2]);
            }
        }
        return $return;
    }

    /**
     *
     * NB: we don't skip empty requests as e.g. a "." is needed to detected the end of row formats in a .TS macro.
     *
     * @param array $lines
     * @param array $callerArguments
     * @return array|null
     */
    public static function getLine(array &$lines, array &$callerArguments = []): ?array
    {

        if (!count($lines)) {
            return null;
        }

        if (is_null($lines[0])) {
            // We hit an end of macro marker.
            array_shift($lines);
            return self::getLine($lines, $callerArguments);
        }

        $return = [
            'request' => null,
            'raw_line' => $lines[0],
            'arguments' => [],
            'arg_string' => '',
            'raw_arg_string' => ''
        ];

        // Do comments first
        if (Roff_Comment::checkLine($lines)) { // Roff_Comment::checkLine() can alter $lines
            // We want another look at the same line:
            return self::getLine($lines, $callerArguments);
        }

        $man          = Man::instance();
        $controlChars = preg_quote($man->control_char, '~') . '|' . preg_quote($man->control_char_2, '~');

        $lines[0]           = Roff_String::substitute($lines[0]);
        $return['raw_line'] = Roff_String::substitute($return['raw_line']);

        if (preg_match(
            '~^(?:\\\\?' . $controlChars . ')\s*([^\s\\\\]*)((?:\s+|\\\\).*)?$~ui',
            $return['raw_line'], $matches)
        ) {
            $return['request'] = Roff_Alias::check($matches[1]);
            if (array_key_exists(2, $matches) && !is_null($matches[2])) {
                $return['raw_arg_string'] = ltrim($matches[2]);
                $return['arg_string']     = $man->applyAllReplacements(Request::massageLine($return['raw_arg_string']));
                $return['arguments']      = Request::parseArguments($return['arg_string']);
            }

            if (Roff_Skipped::skip($return['request'])) {
                array_shift($lines);
                return self::getLine($lines, $callerArguments);
            }

            $macros = $man->getMacros();
            if (isset($macros[$return['request']])) {
                $man->setRegister('.$', (string)count($return['arguments']));
                foreach ($return['arguments'] as $k => $arg) {
                    $return['arguments'][$k] = Roff_Macro::applyReplacements($arg, $callerArguments);
                }

                // Make copies of arrays:
                $macroLines           = $macros[$return['request']];
                $macroCallerArguments = $return['arguments'];
                $evaluatedMacroLines  = [];

                while (count($macroLines)) {
                    $evaluatedMacroLine = Request::getLine($macroLines, $macroCallerArguments)['raw_line'];
                    if (!is_null($evaluatedMacroLine)) {
                        $evaluatedMacroLines[] = $evaluatedMacroLine;
                    }
                    array_shift($macroLines);
                }
                $evaluatedMacroLines[] = null; // Marker for end of macro
                array_splice($lines, 0, 1, $evaluatedMacroLines);
                return self::getLine($lines, $callerArguments);
            }

            $className = $man->getRoffRequestClass($return['request']);
            if ($className) {
                $result = $className::evaluate($return, $lines, $callerArguments);
                if ($result !== false) {
                    return self::getLine($lines, $callerArguments);
                }
            }

        }

        // For text, and above call: Request::getLine($macroLines, $macroCallerArguments)['raw_line'];
        if ($return['raw_line']) {
            $return['raw_line'] = Roff_Macro::applyReplacements($return['raw_line'], $callerArguments, true);
        }

        return $return;
    }

    public static function getNextClass(array &$lines): ?array
    {

        $request = self::getLine($lines);

        if (!$request) {
            return null;
        }

        $return = [
            'class' => null,
            'request' => null,
            'raw_line' => $lines[0],
            'arguments' => [],
            'arg_string' => '',
            'raw_arg_string' => ''
        ];

        $line = $request['raw_line'];

        $man = Man::instance();

        if ($line === '') {
            // empty lines cause a new paragraph, see sar.1
            // See https://www.gnu.org/software/groff/manual/html_node/Implicit-Line-Breaks.html
            $return['request'] = 'sp';
            $return['class']   = 'Inline_VerticalSpace';
        } elseif (!is_null($request['request'])) {
            $class = $man->getRequestClass($request['request']);
            if ($class !== false) {
                $return          = $request;
                $return['class'] = $class;
            } elseif (in_array($request['request'], Request_Unhandled::requests)) {
                throw new exception('Unhandled request ' . $line);
            } elseif (preg_match('~^(' . preg_quote($man->control_char_2, '~') . '.*\w|\\\\\..)~u', $line)) {
                // Lenient with things starting with:
                // \. if there's anything after it, and
                // '  if there's a "word" char after it.
                // TODO: eventually just skip requests we don't know, whether they start with . or '
                $return['request'] = null;
                $return['class']   = 'Block_Text';
            } else {
                $return['class'] = 'Request_Skippable';
            }
        } elseif (Block_TabTable::isStart($lines)) {
            $return['class'] = 'Block_TabTable';
        } elseif (!preg_match('~^' . preg_quote($man->control_char, '~') . '~u', $line)) {
            $return['class'] = 'Block_Text';
        } else {
            $return['class'] = 'Request_Skippable';
        }

        return $return;

    }

}
