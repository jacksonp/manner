<?php


class Block_TS implements Block_Template
{

    private static function parseRowFormats(array &$lines, int $i): array
    {

        $rowFormats  = [];
        $formatsDone = false;
        for (; $i < count($lines) - 1; ++$i) {
            $line = $lines[$i];
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

        return [$i, $rowFormats];

    }

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        int $i,
        ?array $arguments = null,
        ?string $request = null,
        $needOneLineOnly = false
    ) {

        $dom      = $parentNode->ownerDocument;

        $columnSeparator = "\t";

        $line = $lines[++$i];
        if (mb_substr(trim($line), -1, 1) === ';') {
            if (preg_match('~tab\s?\((.)\)~u', $line, $matches)) {
                $columnSeparator = $matches[1];
            }
            ++$i;
        }

        list($i, $rowFormats) = self::parseRowFormats($lines, $i);

        $table = $dom->createElement('table');
        $table->setAttribute('class', 'tbl');
        $tableRowNum  = 0;
        $formatRowNum = 0;
        $tr           = false;

        for ($i = $i + 1; $i < count($lines); ++$i) {
            $request = Request::getLine($lines, $i);

            if ($request['request'] === 'TE') {
                break;
            } elseif ($lines[$i] === '.T&') {
                list($i, $rowFormats) = self::parseRowFormats($lines, $i + 1);
                $formatRowNum = 0;
                continue;
            } elseif ($lines[$i] === '_') {
                if ($tr) {
                    $tr->setAttribute('class', 'border-bottom');
                }
            } elseif ($lines[$i] === '=') {
                if ($tr) {
                    $tr->setAttribute('class', 'border-bottom-double');
                }
            } elseif (in_array($lines[$i], ['.ft CW', '.ft R', '.P', '.PP'])) {
                // Do nothing for now - see sox.1
            } else {
                $tr           = $dom->createElement('tr');
                $cols         = explode($columnSeparator, $lines[$i]);
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
                        for ($i = $i + 1; $i < count($lines); ++$i) {
                            $tBlockLine = $lines[$i];
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

        return $i;

    }


}
