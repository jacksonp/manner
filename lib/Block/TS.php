<?php


class Block_TS
{

    private static function parseRowFormats(array $lines, int $i): array
    {

        $numLines = count($lines);

        $rowFormats  = [];
        $formatsDone = false;
        for (; $i < $numLines - 1; ++$i) {
            $line = $lines[$i];
            if (mb_substr($line, -1, 1) === '.') {
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

        return [$i + 1, $rowFormats];

    }

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        if (!preg_match('~^\.TS~u', $lines[$i])) {
            return false;
        }

        $dom      = $parentNode->ownerDocument;
        $numLines = count($lines);

        $table = $dom->createElement('table');
        $table->setAttribute('class', 'tbl');
        $table = $parentNode->appendChild($table);

        $columnSeparator = "\t";

        $line = $lines[++$i];
        if (mb_substr($line, -1, 1) === ';') {
            if (preg_match('~tab\s?\((.)\)~u', $line, $matches)) {
                $columnSeparator = $matches[1];
            }
            ++$i;
        }

        list($i, $rowFormats) = self::parseRowFormats($lines, $i);

        $tableRowNum = 0;
        $tr          = false;

        while ($i < $numLines - 1) {
            $line = $lines[$i];
            if ($line === '.T&') {
                list($i, $rowFormats) = self::parseRowFormats($lines, $i + 1);
                continue;
            } elseif ($line === '_') {
                if ($tr) {
                    $tr->setAttribute('class', 'border-bottom');
                }
            } elseif ($line === '=') {
                if ($tr) {
                    $tr->setAttribute('class', 'border-bottom-double');
                }
            } elseif (in_array($line, ['.ft CW', '.ft R'])) {
                // Do nothing for now - see sox.1
            } else {
                $tr   = $table->appendChild($dom->createElement('tr'));
                $cols = explode($columnSeparator, $line);

                for ($j = 0; $j < count($cols); ++$j) { // NB: $cols can grow more elements with T{...
                    if (isset($rowFormats[$tableRowNum])) {
                        $thisRowFormat = $rowFormats[$tableRowNum];
                        if (is_string($thisRowFormat) && $thisRowFormat === '---') {
                            $tr->setAttribute('class', 'border-top');
                            $thisRowFormat = @$rowFormats[$tableRowNum + 1];
                        }
                    }

                    $tdClass = @$thisRowFormat[$j];

                    // Ignore for now:
                    // * equal-width columns
                    $tdClass = str_replace(['e', 'E'], '', $tdClass);

                    $tdClass = Replace::preg('~[fF]?[bB]~u', '', $tdClass, -1, $numReplaced);
                    $bold    = $numReplaced > 0;

                    if ($tableRowNum === 0 and $bold) {
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
                    for ($k = $j + 1; $k < count($thisRowFormat); ++$k) {
                        if (@$thisRowFormat[$k] === 's') {
                            ++$colspan;
                        } else {
                            break;
                        }
                    }
                    if ($colspan > 1) {
                        $cell->setAttribute('colspan', $colspan);
                    }

                    $tdContents = $cols[$j];

                    if ($tdContents === '_') {
                        $cell->appendChild($dom->createElement('hr'));
                    } elseif (mb_strpos($tdContents, 'T{') === 0) {
                        $tBlockLines = [];
                        if ($tdContents !== 'T{') {
                            $tBlockLines[] = mb_substr($tdContents, 2);
                        }
                        for ($i = $i + 1; $i < $numLines; ++$i) {
                            $tBlockLine = $lines[$i];
                            if (mb_strpos($tBlockLine, 'T}') === 0) {
                                if ($tBlockLine !== 'T}') {
                                    $restOfLine = mb_substr($tBlockLine, 3); // also take out separator
                                    $cols       = array_merge($cols, explode($columnSeparator, $restOfLine));
                                }
                                Blocks::handle($cell, $tBlockLines);
                                break;
                            } else {
                                $tBlockLines[] = $tBlockLine;
                            }
                        }

                    } else {
                        Blocks::handle($cell, [$tdContents]);
                    }
                    $tr->appendChild($cell);
                }
                ++$tableRowNum;
            }

            $line = $lines[++$i];
            if (preg_match('~^\.TE~u', $line)) {
                return $i;
            }
        }

        throw new Exception($line . ' - .TS without .TE in ' . $parentNode->lastChild->tagName . ' at line ' . $i . '. Last line was "' . $lines[$i - 1] . '"');

    }


}
