<?php


class Blocks
{

    /**
     * Utility function to avoid duplicating code.
     *
     * @param int $i
     * @param array $lines
     * @return array
     */
    private static function getDDBlock(int $i, array $lines)
    {

        $numLines   = count($lines);
        $blockLines = [];
        $rsLevel    = 0;

        for ($i = $i + 1; $i < $numLines; ++$i) {
            $line = $lines[$i];

            if (preg_match('~^\.RS~u', $line)) {
                ++$rsLevel;
            } elseif (preg_match('~^\.RE~u', $line)) {
                --$rsLevel;
            }

            $hitIP      = false;
            $hitBlankIP = false;
            if (preg_match('~^\.IP ?(.*)$~u', $line, $nextIPMatches)) {
                $hitIP      = true;
                $nextIPArgs = Macro::parseArgString($nextIPMatches[1]);
                $hitBlankIP = is_null($nextIPArgs) || trim($nextIPArgs[0]) === '';
            }

            // <= 0 for stray .REs
            if ($rsLevel <= 0) {
                if (preg_match('~^\.[HTLP]?P~u', $line) || ($hitIP && !$hitBlankIP)) {
                    --$i;
                    break;
                }
            }

            if ($hitBlankIP) {
                $blockLines[] = ''; // Empty creates new paragraph in block, see dir.1
            } else {
                if ($i < $numLines - 1 or $line !== '') {
                    $blockLines[] = $line;
                }
            }
        }

        return [$i, $blockLines];

    }


    static function handle(HybridNode $parentNode, array $lines)
    {

        // Right trim $lines
        for ($i = count($lines) - 1; $i >= 0; --$i) {
            if (in_array($lines[$i], ['', '.br'])) {
                unset($lines[$i]);
            } else {
                break;
            }
        }

        $dom = $parentNode->ownerDocument;

        $numLines = count($lines);
        for ($i = 0; $i < $numLines; ++$i) {

            $line = $lines[$i];

            $canAppendNextText = true;

            // empty lines cause a new para also, see sar.1
            // See https://www.gnu.org/software/groff/manual/html_node/Implicit-Line-Breaks.html
            if ($line === '' or preg_match('~^\.([LP]?P$|HP)~u', $line)) {
                if ($line === '' and $i < $numLines - 3 and mb_strpos($lines[$i + 1], "\t") > 0 and
                  (mb_strpos($lines[$i + 2], "\t") > 0 or $lines[$i + 2] === '') and
                  mb_strpos($lines[$i + 3], "\t") > 0
                ) {
                    // Looks like a table next, we detect that lower down, do nothing for now
                    continue;
                } else {
                    $blockLines = [];
                    while ($i < $numLines) {
                        if ($i === $numLines - 1) {
                            break;
                        }
                        $nextLine = $lines[$i + 1];
                        if ($nextLine === '' or preg_match('~^\.([LP]?P$|HP|TP|IP|ti|RS|EX|ce|nf|TS)~u', $nextLine)) {
                            break;
                        }
                        $blockLines[] = $nextLine;
                        ++$i;
                    }
                    $p = $dom->createElement('p');
                    self::handle($p, $blockLines);
                    $parentNode->appendBlockIfHasContent($p);
                    continue;
                }
            }

            // TODO $matches[1] will contain the indentation level, try to use this to handle nested dls?
            if (preg_match('~^\.TP ?(.*)$~u', $line, $matches)) {
                // if this is the last line in a section, it's a bug in the man page, just ignore.
                if ($i === $numLines - 1 or $lines[$i + 1] === '.TP') {
                    continue;
                }
                $dtLine = $lines[++$i];
                while ($i < $numLines - 1 && in_array($dtLine, ['.fi', '.B'])) { // cutter.1
                    $dtLine = $lines[++$i];
                }
                if (in_array($dtLine, ['.br', '.sp', '.B'])) { // e.g. albumart-qt.1, ipmitool.1, blackbox.1
                    $line = $dtLine; // i.e. skip the .TP line
                } else {
                    if (!$parentNode->hasChildNodes() or $parentNode->lastChild->tagName !== 'dl') {
                        $dl = $dom->createElement('dl');
                        $parentNode->appendChild($dl);
                    } else {
                        $dl = $parentNode->lastChild;
                    }
                    $dt = $dom->createElement('dt');
                    TextContent::interpretAndAppendCommand($dt, $dtLine);
                    $dl->appendChild($dt);

                    for ($i = $i + 1; $i < $numLines; ++$i) {
                        $line = $lines[$i];
                        if (preg_match('~^\.TQ$~u', $line)) {
                            $dtLine = $lines[++$i];
                            $dt     = $dom->createElement('dt');
                            TextContent::interpretAndAppendCommand($dt, $dtLine);
                            $dl->appendChild($dt);
                        } else {
                            --$i;
                            break;
                        }
                    }

                    list ($i, $blockLines) = self::getDDBlock($i, $lines);

                    $dd = $dom->createElement('dd');
                    self::handle($dd, $blockLines);
                    $dl->appendBlockIfHasContent($dd);

                    continue;
                }
            }

            // TODO:  --group-directories-first in ls.1 - separate para rather than br?
            // TODO $matches[1] will contain the indentation level, try to use this to handle nested dls?
            if (preg_match('~^\.IP ?(.*)$~u', $line, $matches)) {

                $ipArgs = Macro::parseArgString($matches[1]);

                // 2nd bit: If there's a "designator" - otherwise preg_match hit empty double quotes.
                if (!is_null($ipArgs) && trim($ipArgs[0]) !== '') {
                    // Copied from .TP:
                    if (!$parentNode->hasChildNodes() or $parentNode->lastChild->tagName !== 'dl') {
                        $dl = $dom->createElement('dl');
                        $parentNode->appendChild($dl);
                        if (count($ipArgs) > 1) {
                            $dl->setAttribute('class', 'indent-' . $ipArgs[1]);
                        }
                    } else {
                        $dl = $parentNode->lastChild;
                    }
                    $dt = $dom->createElement('dt');
                    TextContent::interpretAndAppendCommand($dt, $ipArgs[0]);
                    $dl->appendChild($dt);

                    list ($i, $blockLines) = self::getDDBlock($i, $lines);

                    $dd = $dom->createElement('dd');
                    self::handle($dd, $blockLines);
                    $dl->appendBlockIfHasContent($dd);

                    continue;
                }

                if (!$parentNode->hasChildNodes() or $parentNode->lastChild->tagName === 'pre') {
                    $p = $dom->createElement('p');
                    $parentNode->appendChild($p);
                } elseif (in_array($parentNode->lastChild->tagName, ['p', 'h2'])) {
                    $parentNode->appendChild($dom->createElement('blockquote'));
                } elseif ($parentNode->lastChild->tagName === 'blockquote') {
                    // Already in previous .IP,
                    $parentNode->lastChild->appendChild($dom->createElement('br'));
                } else {
                    throw new Exception($line . ' - unexpected .IP in ' . $parentNode->lastChild->tagName . ' at line ' . $i . '. Last line was "' . $lines[$i - 1] . '"');
                }
                continue;
            }

            // .ti = temporary indent
            if (preg_match('~^\.ti ?(.*)$~u', $line, $matches)) {
                if ($i === $numLines - 1) {
                    continue;
                }
                $line = $lines[++$i];
                if ($parentNode->hasChildNodes() > 0 && $parentNode->lastChild->tagName === 'blockquote') {
                    $parentNode->lastChild->appendChild($dom->createElement('br'));
                } else {
                    $parentNode->appendChild($dom->createElement('blockquote'));
                }
            }

            if (preg_match('~^\.RS ?(.*)$~u', $line, $matches)) {
                $rsLevel = 1;
                $rsLines = [];
                for ($i = $i + 1; $i < $numLines; ++$i) {
                    $line = $lines[$i];
                    if (preg_match('~^\.RS~u', $line)) {
                        ++$rsLevel;
                    } elseif (preg_match('~^\.RE~u', $line)) {
                        --$rsLevel;
                        if ($rsLevel === 0) {

                            if (trim(implode('', $rsLines)) === '') {
                                // Skip empty .RS blocks
                                continue 2;
                            }
                            $rsBlock   = $dom->createElement('div');
                            $className = 'indent';
                            if (!empty($matches[1])) {
                                $className .= '-' . trim($matches[1]);
                            }
                            $rsBlock->setAttribute('class', $className);

                            self::handle($rsBlock, $rsLines);

                            $parentNode->appendBlockIfHasContent($rsBlock);
                            continue 2; //End of block
                        }
                    }
                    $rsLines[] = $line;
                }
                throw new Exception($line . '.RS without corresponding .RE ending at line ' . $i . '. Prev line is "' . @$lines[$i - 2] . '"');
            }

            if (preg_match('~^\.RE~u', $line)) {
                // Ignore .RE macros without corresponding .RS
                continue;
            }

            if (preg_match('~^\.EX~u', $line)) {
                $blockLines = [];
                for ($i = $i + 1; $i < $numLines; ++$i) {
                    $line = $lines[$i];
                    if (preg_match('~^\.EE~u', $line)) {
                        break;
                    } elseif (preg_match('~^\.(nf|fi)~u', $line)) {
                        // .EX already marks block as preformatted, just ignore
                        continue;
                    } else {
                        $blockLines[] = $line;
                    }
                }

                // Skip empty block
                if (trim(implode('', $blockLines)) === '') {
                    continue;
                }

                $block = $dom->createElement('pre');
                BlockPreformatted::handle($block, $blockLines);
                $parentNode->appendBlockIfHasContent($block);
                continue; //End of block
            }

            if ($line === '.EE') {
                // Strays
                if ($parentNode->hasChildNodes()) {
                    $parentNode->appendChild($dom->createElement('br'));
                }
                continue;
            }

            if (preg_match('~^\.ce ?(\d*)$~u', $line, $matches)) {
                $blockLines       = [];
                $numLinesToCenter = empty($matches[1]) ? 1 : (int)$matches[1];
                $centerLinesUpTo  = min($i + $numLinesToCenter, $numLines - 1);
                for (; $i < $centerLinesUpTo; ++$i) {
                    $nextLine = $lines[$i + 1];
                    if (mb_strpos($nextLine, '.ce') === 0) {
                        break;
                    }
                    $blockLines[] = $nextLine;
                    $blockLines[] = '.br';
                }
                $block = $dom->createElement('div');
                $block->setAttribute('class', 'center');
                self::handle($block, $blockLines);
                $parentNode->appendBlockIfHasContent($block);
                continue;
            }

            if (preg_match('~^\.nf~u', $line)) {
                $preLines = [];
                for ($i = $i + 1; $i < $numLines; ++$i) {
                    $line = $lines[$i];
                    if (preg_match('~^\.(fi|ad [nb])~u', $line)) {
                        break;
                    } else {
                        if ($i < $numLines - 1 or $line !== '') {
                            $preLines[] = $line;
                        }
                    }
                }

                if (count($preLines) === 0) {
                    continue;
                }

                if (count($preLines) > 1) {
                    $isTable = true;
                    foreach ($preLines as $preLine) {
                        $firstTab = mb_strpos($preLine, "\t");
                        if ($firstTab === false || $firstTab === 0) {
                            $isTable = false;
                            break;
                        }
                    }

                    if ($isTable) {
                        $table = $parentNode->appendChild($dom->createElement('table'));
                        foreach ($preLines as $preLine) {
                            if (in_array($preLine, ['.br', ''])) {
                                continue;
                            }
                            $request = '';
                            if (mb_substr($preLine, 0, 1) === '.') {
                                preg_match('~^(\.\w+ )"?(.*?)"?$~u', $preLine, $matches);
                                $request = $matches[1];
                                $preLine = $matches[2];
                            }
                            $tds = preg_split('~\t+~u', $preLine);
                            $tr  = $table->appendChild($dom->createElement('tr'));
                            foreach ($tds as $tdLine) {
                                $cell     = $dom->createElement('td');
                                $codeNode = $cell->appendChild($dom->createElement('code'));
                                if (empty($request)) {
                                    TextContent::interpretAndAppendText($codeNode, $tdLine);
                                } else {
                                    TextContent::interpretAndAppendCommand($codeNode, $request . $tdLine);
                                }
                                $tr->appendChild($cell);
                            }
                        }
                        continue;
                    }
                }

                $pre = $dom->createElement('pre');

                if (preg_match('~^\.RS ?(.*)$~u', $preLines[0], $matches)) {
                    if (!preg_match('~^\.RE~u', array_pop($preLines))) {
                        throw new Exception('.nf block contains initial .RS but not final .RE');
                    }
                    array_shift($preLines);
                    $className = 'indent';
                    if (!empty($matches[1])) {
                        $className .= '-' . trim($matches[1]);
                    }
                    $pre->setAttribute('class', $className);
                }

                // Skip empty block
                if (trim(implode('', $preLines)) === '') {
                    continue;
                }

                BlockPreformatted::handle($pre, $preLines);
                $parentNode->appendBlockIfHasContent($pre);
                continue; //End of block
            }

            if (preg_match('~^\.TS~u', $line)) {
                $table = $dom->createElement('table');
                $table->setAttribute('class', 'tbl');
                $parentNode->appendChild($table);

                $columnSeparator = "\t";

                $line = $lines[++$i];
                if (mb_substr($line, -1, 1) === ';') {
                    if (preg_match('~tab\s?\((.)\)~', $line, $matches)) {
                        $columnSeparator = $matches[1];
                    }
                    $line = $lines[++$i];
                }

                $rowFormats  = [];
                $formatsDone = false;
                while ($i < $numLines - 1 && !$formatsDone) {
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
                    $line = $lines[++$i];
                }

                $tableRowNum = 0;
                $tr          = false;

                while ($i < $numLines - 1) {
                    if ($line === '_') {
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

                        for ($j = 0; $j < count($cols); ++$j) { // NB: $cols can get more elements with T{...
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

                            $tdClass = Replace::preg('~[fF]?[bB]~', '', $tdClass, -1, $numReplaced);
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
                                if (mb_strlen($tdContents) > 2) {
                                    $tBlockLines[] = mb_substr($tdContents, 2);
                                }
                                for ($i = $i + 1; $i < $numLines; ++$i) {
                                    $tBlockLine = $lines[$i];
                                    if (mb_strpos($tBlockLine, 'T}') === 0) {
                                        if (mb_strlen($tBlockLine) > 2) {
                                            $restOfLine = mb_substr($tBlockLine, 3); // also take out separator
                                            $cols       = array_merge($cols, explode($columnSeparator, $restOfLine));
                                        }
                                        self::handle($cell, $tBlockLines);
                                        break;
                                    } else {
                                        $tBlockLines[] = $tBlockLine;
                                    }
                                }

                            } else {
                                TextContent::interpretAndAppendCommand($cell, $tdContents);
                            }
                            $tr->appendChild($cell);
                        }
                        ++$tableRowNum;
                    }

                    $line = $lines[++$i];
                    if (preg_match('~^\.TE~u', $line)) {
                        continue 2;
                    }
                }

                throw new Exception($line . ' - .TS without .TE in ' . $parentNode->lastChild->tagName . ' at line ' . $i . '. Last line was "' . $lines[$i - 1] . '"');

            }

            //<editor-fold desc="Make tables out of tab-separated lines">
            // mb_strpos() > 0: avoid indented stuff
            if ($i < $numLines - 1
              and mb_strlen($line) > 0
              and $line[0] !== '.'
              and mb_strpos($line, "\t") > 0
              and (
                mb_strpos($lines[$i + 1], "\t") > 0
                || (
                  in_array($lines[$i + 1], ['.br', ''])
                  and $i < $numLines - 2
                  and mb_strpos($lines[$i + 2], "\t") > 0
                )
              )
            ) {
                $table = $dom->createElement('table');
                $parentNode->appendChild($table);
                for (; ; ++$i) {

                    $tds = preg_split('~\t+~u', $line);
                    $tr  = $table->appendChild($dom->createElement('tr'));
                    foreach ($tds as $tdLine) {
                        $cell = $dom->createElement('td');
                        TextContent::interpretAndAppendText($cell, $tdLine);
                        $tr->appendChild($cell);
                    }

                    if ($i === $numLines - 1) {
                        break 2;
                    }

                    $line = $lines[$i + 1];

                    if (in_array($line, ['.br', ''])) {
                        ++$i;
                        if ($i === $numLines - 1) {
                            break 2;
                        }
                        $line = $lines[$i + 1];
                    }

                    if (mb_strpos($line, "\t") === false) {
                        continue 2; // Done with table.
                    }

                }
            }
            //</editor-fold>

            $parentNodeLastBlock = $parentNode->getLastBlock();

            if (is_null($parentNodeLastBlock)) {
                if ($parentNode->tagName === 'p') {
                    $parentForLine = $parentNode;
                } else {
                    $parentForLine = $parentNode->appendChild($dom->createElement('p'));
                }
            } else {
                if (in_array($parentNodeLastBlock->tagName, ['div', 'pre', 'code', 'table', 'h2'])) {
                    // Start a new paragraph after certain blocks
                    $parentForLine = $parentNode->appendChild($dom->createElement('p'));
                } else {
                    $parentForLine = $parentNodeLastBlock;
                }
            }

            if (preg_match('~^\.UR <?(.*?)>?$~u', $line, $matches)) {
                $anchor = $dom->createElement('a');
                $url    = TextContent::interpretString(Macro::parseArgString($matches[1])[0]);
                if (filter_var($url, FILTER_VALIDATE_EMAIL)) {
                    $url = 'mailto:' . $url;
                }
                $anchor->setAttribute('href', $url);
                $parentForLine->appendChild($anchor);
                for ($i = $i + 1; $i < $numLines; ++$i) {
                    $line = $lines[$i];
                    if (preg_match('~^\.UE~u', $line)) {
                        continue 2;
                    }
                    TextContent::interpretAndAppendCommand($anchor, $line);
                }
                throw new Exception('.UR with no corresponding .UE');
            }

            if (preg_match('~^\.([RBI][RBI]?|ft (?:[123RBIP]|C[WR]))$~u', $line)) {
                if ($i === $numLines - 1
                  || $line === '.ft R'
                  || $lines[$i + 1] === '.IP http://www.gnutls.org/manual/'
                  || mb_strpos($lines[$i + 1], '.B') === 0
                  || mb_strpos($lines[$i + 1], '.I') === 0
                ) {
                    continue;
                }
                $nextLine = $lines[++$i];
                if (mb_strlen($nextLine) === 0) {
                    continue;
                } else {
                    if ($nextLine[0] === '.') {
                        if (in_array($line, ['.ft 1', '.ft P', '.ft CR']) || $nextLine === '.nf') {
                            --$i;
                            continue;
                        }
                        throw new Exception($nextLine . ' - ' . $line . ' followed by non-text');
                    } else {
                        if ($line === '.B' || $line === '.ft B' || $line === '.ft 3') {
                            $parentForLine = $parentForLine->appendChild($dom->createElement('strong'));
                            $line          = $nextLine;
                        } elseif ($line === '.I' || $line === '.ft I' || $line === '.ft 2') {
                            $parentForLine = $parentForLine->appendChild($dom->createElement('em'));
                            $line          = $nextLine;
                        } elseif ($line === '.ft CW') {
                            $parentForLine = $parentForLine->appendChild($dom->createElement('code'));
                            $line          = $nextLine;
                        } else {
                            $line .= ' ' . $nextLine;
                        }
                        $canAppendNextText = false;
                    }
                }
            }

            if ($line === '.SM') {
                if ($i === $numLines - 1) {
                    continue;
                }
                $nextLine = $lines[++$i];
                if ($nextLine === '') {
                    continue;
                }
                $parentForLine     = $parentForLine->appendChild($dom->createElement('small'));
                $line              = $nextLine;
                $canAppendNextText = false;
            }

            if ($line === '.SB') {
                if ($i === $numLines - 1) {
                    continue;
                }
                $nextLine = $lines[++$i];
                if ($nextLine === '') {
                    continue;
                }
                $small             = $parentForLine->appendChild($dom->createElement('small'));
                $parentForLine     = $small->appendChild($dom->createElement('strong'));
                $line              = $nextLine;
                $canAppendNextText = false;
            }

            if ($canAppendNextText
              && !in_array(mb_substr($line, 0, 1), ['.', ' '])
              && (mb_strlen($line) < 2 || mb_substr($line, 0, 2) !== '\\.')
              && !preg_match('~\\\\c$~', $line)
            ) {
                while ($i < $numLines - 1) {
                    $nextLine = $lines[$i + 1];
                    if (mb_strlen($nextLine) === 0 || in_array(mb_substr($nextLine, 0, 1), ['.', ' '])
                      || (mb_strlen($nextLine) > 1 && mb_substr($nextLine, 0, 2) === '\\.')
                    ) {
                        break;
                    }
                    $line .= ' ' . $nextLine;
                    ++$i;
                }
            }

            if (preg_match('~^\\\\?\.br~u', $line)) {
                if ($parentForLine->hasChildNodes() && $i !== $numLines - 1) {
                    // Only bother if this isn't the first node.
                    $parentForLine->appendChild($dom->createElement('br'));
                }
            } elseif (preg_match('~^\.(sp|ne)~u', $line)) {
                if ($parentForLine->hasChildNodes() && $i !== $numLines - 1) {
                    // Only bother if this isn't the first node.
                    $parentForLine->appendChild($dom->createElement('br'));
                    $parentForLine->appendChild($dom->createElement('br'));
                }
            } else {

                // Implicit line break: "A line that begins with a space causes a break and the space is output at the beginning of the next line. Note that this space isn't adjusted, even in fill mode."
                if (mb_substr($line, 0, 1) === ' '
                  && $parentForLine->hasChildNodes()
                  && ($parentForLine->lastChild->nodeType !== XML_ELEMENT_NODE || $parentForLine->lastChild->tagName !== 'br')
                ) {
                    $parentForLine->appendChild($dom->createElement('br'));
                }

                TextContent::interpretAndAppendCommand($parentForLine, $line);
            }

        }

    }

}
