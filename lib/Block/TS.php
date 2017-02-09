<?php
declare(strict_types = 1);

class Block_TS implements Block_Template
{

    private static $rowFormatKeyCharacters = [
        'A',
        'C',
        'L',
        'N',
        'R',
        '^',
        '_',
        '-',
        '=',
        '|'
    ];

    private static function getFormatChar(string $line, int $i): string
    {
        $char      = mb_substr($line, $i, 1);
        $upperChar = mb_strtoupper($char);
        return in_array($upperChar, self::$rowFormatKeyCharacters) ? $upperChar : $char;
    }

    private static function parseRowFormat(string $line): array
    {
        $formats    = [];
        $format     = self::getFormatChar($line, 0);
        $lineLength = mb_strlen($line);
        for ($i = 1; $i < $lineLength; ++$i) {
            $char = self::getFormatChar($line, $i);
            if (in_array($char, self::$rowFormatKeyCharacters)) {
                $formats[] = trim($format);
                $format    = '';
            }
            $format .= $char;
            if (in_array($char, ['f', 'F'])) {
                // Either of these specifiers may be followed by a font name (either one or two characters long), font
                // number (a single digit), or long name in parentheses (the last form is a GNU tbl extension). A
                // one-letter font name must be separated by one or more blanks from whatever follows.
                $charAfterF = self::getFormatChar($line, ++$i);
                if (preg_match('~\d~', $charAfterF)) {
                    $format .= $charAfterF;
                } elseif (preg_match('~[A-Z]~', $charAfterF)) {
                    $charAfterAfterF = self::getFormatChar($line, ++$i);
                    $format .= $charAfterF . $charAfterAfterF;
                } elseif ($charAfterF === '(') {
                    $format .= $charAfterF;
                    while ($i < $lineLength) {
                        $nextChar = self::getFormatChar($line, ++$i);
                        $format .= $nextChar;
                        if ($nextChar === ')') {
                            break;
                        }
                    }
                }
            }
        }
        $formats[] = trim($format);
        return $formats;
    }

    private static function parseRowFormats(array &$lines): ?array
    {

        $rowFormats  = [];
        $formatsDone = false;
        while (count($lines)) {
            $request = Request::getLine($lines);
            $line    = array_shift($lines);
            if ($request['request'] === 'TE') {
                return null; // Format is garbage, skip content.
            }
            if (mb_substr(trim($line), -1, 1) === '.') {
                $line        = rtrim($line, '.');
                $formatsDone = true;
            }
            // Ignore vertical bars for now:
            $line = str_replace('|', ' ', $line);
            if (preg_match('~^[-_\s]+$~u', $line)) {
                $rowFormats[] = '---';
            } else {
                $rowFormats[] = self::parseRowFormat($line);
            }

            if ($formatsDone) {
                break;
            }

        }

        return $rowFormats;

    }

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        $parentNode = Blocks::getBlockContainerParent($parentNode);

        $dom = $parentNode->ownerDocument;

        $columnSeparator = "\t";

        $tableClasses = ['tbl'];

        if (mb_substr(trim($lines[0]), -1, 1) === ';') {
            $globalOptions = rtrim($lines[0], ';');
            array_shift($lines);
            // Treat this separately as a space after "tab" is accepted:
            Replace::pregCallback('~tab\s?\((.)\)~u', function ($matches) use (&$columnSeparator) {
                $columnSeparator = $matches[1];
                return '';
            }, $globalOptions);
            $globalOptions = preg_split('~[\\t ,]~', $globalOptions);
            foreach ($globalOptions as $globalOption) {
                if (in_array($globalOption, ['box'])) {
                    $tableClasses[] = $globalOption;
                } elseif ($globalOption === 'center') {
                    $tableClasses[] = 'center-table';
                }
            }
        }

        $rowFormats = self::parseRowFormats($lines);
        if (is_null($rowFormats)) {
            return null; // We hit a .TE
        }

        $table = $dom->createElement('table');
        $table->setAttribute('class', implode(' ', $tableClasses));
        $tableRowNum  = 0;
        $formatRowNum = 0;
        $tr           = false;
        $nextRowBold  = false;

        $table = $parentNode->appendChild($table);

        while ($request = Request::getLine($lines)) {
            array_shift($lines);

            if ($request['raw_line'] === '') {
                continue;
            } elseif (in_array($request['request'], ['TE', 'SH', 'SS'])) {
                break;
            } elseif ($request['raw_line'] === '.T&') {
                $rowFormats = self::parseRowFormats($lines);
                if (is_null($rowFormats)) {
                    return null; // We hit a .TE
                }
                $formatRowNum = 0;
                continue;
            } elseif ($request['raw_line'] === '_') {
                if ($tr) {
                    $tr->setAttribute('class', 'border-bottom');
                }
            } elseif ($request['raw_line'] === '=') {
                if ($tr) {
                    $tr->setAttribute('class', 'border-bottom-double');
                }
            } elseif (in_array($request['raw_line'], ['.ft CW', '.ft R', '.P', '.PP'])) {
                // Do nothing for now - see sox.1
            } elseif (in_array($request['raw_line'], ['.B'])) {
                $nextRowBold = true;
            } elseif (!is_null($request['request']) && $request['request'] !== '') {
                continue;
            } else {
                $tr = $dom->createElement('tr');

                if (isset($rowFormats[$formatRowNum])) {
                    $thisRowFormat = $rowFormats[$formatRowNum];
                    if (is_string($thisRowFormat) && $thisRowFormat === '---') {
                        if ($table->lastChild) {
                            $table->lastChild->setAttribute('class', 'border-bottom');
                        } else {
                            $tr->setAttribute('class', 'border-top');
                        }
                        ++$formatRowNum;
                        $thisRowFormat = @$rowFormats[$formatRowNum];
                    }
                }

                $tr           = $table->appendChild($tr);
                $cols         = explode($columnSeparator, $request['raw_line']);
                $totalColSpan = 0;

                for ($j = 0; $j < count($cols); ++$j) { // NB: $cols can grow more elements with T{...

                    $tdClass = @$thisRowFormat[$totalColSpan];

                    // Ignore for now:
                    // * equal-width columns,
                    // * setting the font to Regular
                    $tdClass = str_replace(['e', 'E', 'f(R)'], '', $tdClass);

                    $tdClass = str_replace(['f(CW)'], ' code ', $tdClass);

                    $tdClass = Replace::preg('~[fF]?[bB]~u', '', $tdClass, -1, $numReplaced);
                    if ($nextRowBold) {
                        $bold = true;
                    } else {
                        $bold = $numReplaced > 0;
                    }

                    /* @var DomElement $cell */
                    if ($tableRowNum === 0 && $bold) {
                        $cell = $dom->createElement('th');
                    } else {
                        $cell = $dom->createElement('td');
                        if ($bold) {
                            $tdClass = trim($tdClass . ' bold');
                        }
                    }

                    $tdClass = Replace::preg('~^L(.*)$~', '$1', $tdClass);
                    $tdClass = Replace::preg('~^C(.*)$~', 'center $1', $tdClass);
                    $tdClass = Replace::preg('~^[RN](.*)$~', 'right-align $1', $tdClass);
                    $tdClass = trim($tdClass);

                    if ($tdClass === 'f') {
                        $tdClass = '';
                    }

                    if (mb_strlen($tdClass) > 0) {
                        $cell->setAttribute('class', $tdClass);
                    }
                    $thisColspan = 1;
                    while (in_array(@$thisRowFormat[$totalColSpan + $thisColspan + 1],
                        ['s', 'S'])) { // While we span right
                        ++$thisColspan;
                    }
                    if ($thisColspan > 1) {
                        $cell->setAttribute('colspan', (string)$thisColspan);
                    }
                    $totalColSpan += $thisColspan;

                    $cell = $tr->appendChild($cell);

                    $tdContents = $cols[$j];

                    if ($tdContents === '_') {
                        $cell->appendChild($dom->createElement('hr'));
                    } elseif (in_array($tdContents, ['.TH', '.TC'])) {
                        continue; //  Material up to the ".TH" is placed at the top of each page of table; the remaining lines in the table are placed on several pages as required. Note that this is not a feature of tbl, but of the ms layout macros.
                    } elseif (mb_strpos($tdContents, 'T{') === 0) {
                        $tBlockLines = [];
                        if ($tdContents !== 'T{') {
                            $tBlockLines[] = mb_substr($tdContents, 2);
                        }
                        while (count($lines)) {
                            if (mb_strpos($lines[0], '.TE') === 0) { // bug in latex2man.1
                                break;
                            }
                            $tBlockLine = array_shift($lines);
                            if (mb_strpos($tBlockLine, 'T}') === 0) {
                                if ($tBlockLine !== 'T}') {
                                    $restOfLine = mb_substr($tBlockLine, 3); // also take out separator
                                    $cols       = array_merge($cols, explode($columnSeparator, $restOfLine));
                                }
                                Roff::parse($cell, $tBlockLines);
                                break;
                            } else {
                                $tBlockLines[] = $tBlockLine;
                            }
                        }

                    } else {
                        // This fails e.g. in ed.1p on ";!. ; $" where ! is $columnSeparator
                        //Roff::parse($cell, [$tdContents]);
                        if (Inline_VerticalSpace::check($tdContents) === false) {
                            TextContent::interpretAndAppendText($cell, $tdContents);
                        }
                    }
                }

                ++$tableRowNum;
                ++$formatRowNum;
                $nextRowBold = false;
            }

        }

        return $parentNode;

    }


}
