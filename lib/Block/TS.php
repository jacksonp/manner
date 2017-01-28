<?php


class Block_TS implements Block_Template
{

    private static function parseRowFormats(array &$lines): array
    {

        $rowFormats  = [];
        $formatsDone = false;
        while (count($lines)) {
            $line = array_shift($lines);
            if (mb_substr(trim($line), -1, 1) === '.') {
                $line        = rtrim($line, '.');
                $formatsDone = true;
            }
            if (preg_match('~^-+$~u', $line)) {
                $rowFormats[] = '---';
            } else {
                // Ignore vertical bars for now:
                $line    = str_replace('|', '', $line);
                $colDefs = preg_split('~[\s]+~', $line);
                if (count($colDefs) === 1) {
                    $colDefs = str_split($colDefs[0]);
                }
                foreach ($colDefs as $k => $v) {
                    $colDefs[$k] = strtr($v, ['l' => '', 'L' => '']);
                }
                $rowFormats[] = $colDefs;
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

        $dom = $parentNode->ownerDocument;

        $columnSeparator = "\t";

        if (mb_substr(trim($lines[0]), -1, 1) === ';') {
            if (preg_match('~tab\s?\((.)\)~u', $lines[0], $matches)) {
                $columnSeparator = $matches[1];
            }
            array_shift($lines);
        }

        $rowFormats = self::parseRowFormats($lines);

        $table = $dom->createElement('table');
        $table->setAttribute('class', 'tbl');
        $tableRowNum  = 0;
        $formatRowNum = 0;
        $tr           = false;

        while ($request = Request::getLine($lines)) {
            array_shift($lines);

            if (in_array($request['request'], ['TE', 'SH', 'SS'])) {
                break;
            } elseif ($request['raw_line'] === '.T&') {
                $rowFormats   = self::parseRowFormats($lines);
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
            } else {
                $tr           = $dom->createElement('tr');
                $cols         = explode($columnSeparator, $request['raw_line']);
                $totalColSpan = 0;

                for ($j = 0; $j < count($cols); ++$j) { // NB: $cols can grow more elements with T{...
                    if (isset($rowFormats[$formatRowNum])) {
                        $thisRowFormat = $rowFormats[$formatRowNum];
                        if (is_string($thisRowFormat) && $thisRowFormat === '---') {
                            $tr->setAttribute('class', 'border-top');
                            $thisRowFormat = @$rowFormats[$formatRowNum + 1];
                        }
                    }

                    $tdClass = @$thisRowFormat[$totalColSpan];

                    // Ignore for now:
                    // * equal-width columns
                    $tdClass = str_replace(['e', 'E'], '', $tdClass);

                    $tdClass = Replace::preg('~[fF]?[bB]~u', '', $tdClass, -1, $numReplaced);
                    $bold    = $numReplaced > 0;

                    if ($tableRowNum === 0 && $bold) {
                        $cell = $dom->createElement('th');
                    } else {
                        $cell = $dom->createElement('td');
                        if ($bold) {
                            $tdClass = trim($tdClass . ' bold');
                        }
                    }
                    if (!empty($tdClass)) {
                        $cell->setAttribute('class', $tdClass);
                    }
                    $colspan = 1;
                    for ($k = $totalColSpan + 1; $k < count($thisRowFormat); ++$k) {
                        if (@$thisRowFormat[$k] === 's') {
                            ++$colspan;
                        } else {
                            break;
                        }
                    }
                    if ($colspan > 1) {
                        $cell->setAttribute('colspan', $colspan);
                    }
                    $totalColSpan += $colspan;

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
                                Blocks::trim($tBlockLines);
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
                    $tr->appendChild($cell);
                }
                ++$tableRowNum;
                ++$formatRowNum;
                $table->appendBlockIfHasContent($tr);
            }

        }

        $parentNode->appendBlockIfHasContent($table);

        return null;

    }


}
