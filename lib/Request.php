<?php

/**
 * manner: convert troff man pages to semantic HTML
 * Copyright (C) 2024  Jackson Pauls
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Manner;

use Exception;
use Manner\Block\TabTable;
use Manner\Request\Unhandled;
use Manner\Roff\Alias;
use Manner\Roff\Comment;
use Manner\Roff\Macro;
use Manner\Roff\Skipped;
use Manner\Roff\StringRequest;
use Manner\Roff\Template;

class Request
{

    public static function getArgChars(string $argString): array
    {
        $argString = ltrim($argString);

        return preg_split('//u', $argString, -1, PREG_SPLIT_NO_EMPTY);
    }

    public static function getNextArgument(array &$chars, bool $ignoreQuotes): ?string
    {
        if (count($chars) === 0) {
            return null;
        }

        $thisArg  = '';
        $inQuotes = false;
        $lastChar = null;
        while (count($chars)) {
            $char = array_shift($chars);
            if ($char === '\\') {
                // Take this char and the next
                $thisArg .= $char . array_shift($chars);
            } elseif ($char === ' ' && !$inQuotes) {
                return $thisArg;
            } elseif ($char === '"' && !$ignoreQuotes) {
                if ($inQuotes && $chars[0] === '"') {
                    // When in quotes, "" produces a quote.
                    $thisArg .= '"';
                    array_shift($chars);
                } elseif ((is_null($lastChar) || $lastChar === ' ') && !$inQuotes) {
                    $inQuotes = true;
                } elseif ($inQuotes) {
                    return $thisArg;
                } else {
                    $thisArg .= '"';
                }
            } else {
                $thisArg .= $char;
            }
            $lastChar = $char;
        }

        return $thisArg;
    }

    private static function parseArguments(string $argString, bool $ignoreQuotes): array
    {
        $argString = ltrim($argString);
        // TODO: Could also trim on paired backslashes here:
        $argString = preg_replace('~([^\\\\])\s+$~u', '$1', $argString);

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
                if ($lastChar === ' ') {
                    continue; // ignore double spaces outside quotes
                }
                // New arg
                $args[]  = $thisArg;
                $thisArg = '';
            } elseif ($char === '"' && !$ignoreQuotes) {
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
        // Replace 2 backslashes with 1 backslash, do not use str_replace as replacements can themselves be replaced: e.g. \\\
        return Replace::preg('~(\\\\){2}~u', '\\', $macroLine);
    }

    public static function peepAt(?string $line): array
    {
        $return = ['name' => null, 'raw_arg_string' => ''];
        if (is_null($line)) {
            // We hit an end of macro marker.
            return $return;
        }
        $man          = Man::instance();
        $controlChars = preg_quote($man->control_char, '~') . '|' . preg_quote($man->control_char_2, '~');
        if (preg_match(
          '~^(?:\\\\?' . $controlChars . ')\s*([^\s\\\\]*)((?:\s+|\\\\).*)?$~ui',
          $line,
          $matches
        )
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
     * @throws Exception
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

        $man = Man::instance();

        if (!is_null($man->escape_char)) {
            // Continuations
            while (
              count($lines) > 1 &&
              mb_substr($lines[0], -1, 1) === '\\' &&
              ($lines[0] === '\\' || mb_substr($lines[0], -2, 1) !== '\\')) {
                $lineWithContinuationsExpanded = mb_substr($lines[0], 0, -1) . $lines[1];
                array_shift($lines);
                array_shift($lines);
                array_unshift($lines, $lineWithContinuationsExpanded);
            }
        }

        // Do comments first
        if (Comment::checkLine($lines)) { // Comment::checkLine() can alter $lines
            // We want another look at the same line:
            return self::getLine($lines, $callerArguments);
        }

        $controlChars = preg_quote($man->control_char, '~') . '|' . preg_quote($man->control_char_2, '~');

        $lines[0] = StringRequest::substitute($lines[0]);

        $return = [
          'request'        => null,
          'raw_line'       => $lines[0],
          'arguments'      => [],
          'arg_string'     => '',
          'raw_arg_string' => '',
        ];

        if (preg_match(
          '~^(?:\\\\?' . $controlChars . ')\s*([^\s\\\\]*)((?:\s+|\\\\).*)?$~ui',
          $return['raw_line'],
          $matches
        )
        ) {
            $return['request'] = Alias::check($matches[1]);
            if (array_key_exists(2, $matches) && !is_null($matches[2])) {
                $return['raw_arg_string'] = ltrim($matches[2]);
                $return['arg_string']     = $man->applyAllReplacements(Request::massageLine($return['raw_arg_string']));
                $return['arguments']      = Request::parseArguments(
                  $return['arg_string'],
                  in_array($return['request'], ['if', 'ie'])
                );
            }

            if (Skipped::skip($return['request'])) {
                array_shift($lines);

                return self::getLine($lines, $callerArguments);
            }

            $macros = $man->getMacros();
            if (isset($macros[$return['request']])) {
                $man->setRegister('.$', (string)count($return['arguments']));
                foreach ($return['arguments'] as $k => $arg) {
                    $return['arguments'][$k] = Macro::applyReplacements($arg, $callerArguments);
                }

                // Make copies of arrays:
                $macroLines           = $macros[$return['request']];
                $macroCallerArguments = $return['arguments'];
                $evaluatedMacroLines  = [];

                while (count($macroLines)) {
                    $evaluatedMacroLine = Request::getLine($macroLines, $macroCallerArguments);
                    if (!is_null($evaluatedMacroLine) && !is_null($evaluatedMacroLine['line'])) {
                        $evaluatedMacroLines[] = $evaluatedMacroLine['line'];
                    }
                    array_shift($macroLines);
                }
                $evaluatedMacroLines[] = null; // Marker for end of macro
                array_splice($lines, 0, 1, $evaluatedMacroLines);

                return self::getLine($lines, $callerArguments);
            }

            $className = $man->getRoffRequestClass($return['request']);
            if ($className) {
                /** @var Template $className */
                $className::evaluate($return, $lines, $callerArguments);

                return self::getLine($lines, $callerArguments);
            }
        }

        $return['line'] = Macro::applyReplacements($return['raw_line'], $callerArguments, true);

        return $return;
    }

    /**
     * @param array $request
     * @param array $lines
     * @return string
     * @throws Exception
     */
    public static function getClass(array $request, array $lines): string
    {
        if ($request['raw_line'] === '' && !\Manner\Block\Text::$interruptTextProcessing) {
            // See https://www.gnu.org/software/groff/manual/html_node/Implicit-Line-Breaks.html
            // Exception if text processing has been interrupted, in which case we let \Manner\Block\Text handle it.
            return '\Manner\Inline\VerticalSpace';
        } elseif (!is_null($request['request'])) {
            $class = Man::instance()->getRequestClass($request['request']);
            if (!is_null($class)) {
                return $class;
            } elseif (in_array($request['request'], Unhandled::requests)) {
                throw new Exception('Unhandled request ' . $request['raw_line']);
            } else {
                return '\Manner\Request\Skippable';
            }
        } elseif (TabTable::isStart($lines)) {
            return '\Manner\Block\TabTable';
        } else {
            return '\Manner\Block\Text';
        }
    }

}
