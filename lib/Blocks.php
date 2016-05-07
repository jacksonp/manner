<?php


class Blocks
{

    private static function getDDBlock($i, $blockNode)
    {

        $numLines   = count($blockNode->manLines);
        $blockLines = [];
        $rsLevel    = 0;

        for ($i = $i + 1; $i < $numLines; ++$i) {
            $line = $blockNode->manLines[$i];

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
                $blockLines[] = $line;
            }
        }

        return [$i, $blockLines];

    }


    static function handle(HybridNode $blockNode)
    {

        $dom = $blockNode->ownerDocument;

        /** @var HybridNode[] $blocks */
        $blocks   = [];
        $blockNum = 0;

        $numLines = count($blockNode->manLines);
        for ($i = 0; $i < $numLines; ++$i) {
            $line = $blockNode->manLines[$i];

            $canAppendNextText = true;

            // empty lines cause a new para also, see sar.1
            if (preg_match('~^\.([LP]?P$|HP)~u', $line)) {
                // If this is last line, or the next line is .RS, this would be an empty paragraph: don't bother.
                if ($i !== $numLines - 1
                  && !preg_match('~^\.RS ?(.*)$~u', $blockNode->manLines[$i + 1])
                ) {
                    $blocks[++$blockNum] = $dom->createElement('p');
                }
                continue;
            }

            // See https://www.gnu.org/software/groff/manual/html_node/Implicit-Line-Breaks.html
            if (mb_strlen($line) === 0) {
                // Add a paragraph unless this is the last line in block, or the next line is also empty.
                if ($i !== $numLines - 1 and mb_strlen($blockNode->manLines[$i + 1]) !== 0) {
                    $blocks[++$blockNum] = $dom->createElement('p');
                }
                continue;
            }

            // TODO $matches[1] will contain the indentation level, try to use this to handle nested dls?
            if (preg_match('~^\.TP ?(.*)$~u', $line, $matches)) {
                // if this is the last line in a section, it's a bug in the man page, just ignore.
                if ($i === $numLines - 1) {
                    continue;
                }
                $dtLine = $blockNode->manLines[++$i];
                while (in_array($dtLine, ['.fi', '.B'])) { // cutter.1
                    $dtLine = $blockNode->manLines[++$i];
                }
                if (in_array($dtLine, ['.br', '.sp'])) { // e.g. albumart-qt.1, ipmitool.1
                    $line = $dtLine; // i.e. skip the .TP line
                } else {
                    if (empty($blocks) || $blocks[$blockNum]->tagName !== 'dl') {
                        $blocks[++$blockNum] = $dom->createElement('dl');
                    }
                    $dt = $dom->createElement('dt');
                    TextContent::interpretAndAppendCommand($dt, $dtLine);
                    $blocks[$blockNum]->appendChild($dt);

                    for ($i = $i + 1; $i < $numLines; ++$i) {
                        $line = $blockNode->manLines[$i];
                        if (preg_match('~^\.TQ$~u', $line)) {
                            $dtLine = $blockNode->manLines[++$i];
                            $dt     = $dom->createElement('dt');
                            TextContent::interpretAndAppendCommand($dt, $dtLine);
                            $blocks[$blockNum]->appendChild($dt);
                        } else {
                            --$i;
                            break;
                        }
                    }

                    list ($i, $blockLines) = self::getDDBlock($i, $blockNode);

                    // Skip empty block
                    if (trim(implode('', $blockLines)) === '') {
                        continue;
                    }

                    $dd           = $dom->createElement('dd');
                    $dd->manLines = $blockLines;
                    self::handle($dd);
                    $blocks[$blockNum]->appendChild($dd);

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
                    if (empty($blocks) || $blocks[$blockNum]->tagName !== 'dl') {
                        $blocks[++$blockNum] = $dom->createElement('dl');
                        if (count($ipArgs) > 1) {
                            $blocks[$blockNum]->setAttribute('class', 'indent-' . $ipArgs[1]);
                        }
                    }
                    $dt = $dom->createElement('dt');
                    TextContent::interpretAndAppendCommand($dt, $ipArgs[0]);
                    $blocks[$blockNum]->appendChild($dt);

                    list ($i, $blockLines) = self::getDDBlock($i, $blockNode);

                    // Skip empty block
                    if (trim(implode('', $blockLines)) === '') {
                        continue;
                    }

                    $dd           = $dom->createElement('dd');
                    $dd->manLines = $blockLines;
                    self::handle($dd);
                    $blocks[$blockNum]->appendChild($dd);

                    continue;
                }

                if (empty($blocks) || $blocks[$blockNum]->tagName === 'pre') {
                    $blocks[++$blockNum] = $dom->createElement('p');
                    continue;
                } elseif ($blocks[$blockNum]->tagName === 'p') {
                    $blocks[++$blockNum] = $dom->createElement('blockquote');
                } elseif ($blocks[$blockNum]->tagName === 'blockquote') {
                    // Already in previous .IP,
                    $blocks[$blockNum]->appendChild($dom->createElement('br'));
                } else {
                    throw new Exception($line . ' - unexpected .IP in ' . $blocks[$blockNum]->tagName . ' at line ' . $i . '. Last line was "' . $blockNode->manLines[$i - 1] . '"');
                }
                continue;
            }

            // .ti = temporary indent
            if (preg_match('~^\.ti ?(.*)$~u', $line, $matches)) {
                if ($i === $numLines - 1) {
                    continue;
                }
                $line = $blockNode->manLines[++$i];
                if ($blockNum > 0 && $blocks[$blockNum]->tagName === 'blockquote') {
                    $blocks[$blockNum]->appendChild($dom->createElement('br'));
                } else {
                    $blocks[++$blockNum] = $dom->createElement('blockquote');
                }
            }

            if (preg_match('~^\.RS ?(.*)$~u', $line, $matches)) {
                $rsLevel = 1;
                $rsLines = [];
                for ($i = $i + 1; $i < $numLines; ++$i) {
                    $line = $blockNode->manLines[$i];
                    if (preg_match('~^\.RS~u', $line)) {
                        ++$rsLevel;
                    } elseif (preg_match('~^\.RE~u', $line)) {
                        --$rsLevel;
                        if ($rsLevel === 0) {

                            if (trim(implode('', $rsLines)) === '') {
                                // Skip empty .RS blocks
                                continue 2;
                            }
                            $blocks[++$blockNum] = $dom->createElement('div');
                            $className           = 'indent';
                            if (!empty($matches[1])) {
                                $className .= '-' . trim($matches[1]);
                            }
                            $blocks[$blockNum]->setAttribute('class', $className);
                            $rsBlock = $blocks[$blockNum];

                            $rsBlock->manLines = $rsLines;
                            self::handle($rsBlock);
                            continue 2; //End of block
                        }
                    }
                    $rsLines[] = $line;
                }
                throw new Exception($line . '.RS without corresponding .RE ending at line ' . $i . '. Prev line is "' . @$blockNode->manLines[$i - 2] . '"');
            }

            if (preg_match('~^\.RE~u', $line)) {
                // Ignore .RE macros without corresponding .RS
                continue;
            }

            if (preg_match('~^\.EX~u', $line)) {
                $blockLines = [];
                for ($i = $i + 1; $i < $numLines; ++$i) {
                    $line = $blockNode->manLines[$i];
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

                $blocks[++$blockNum]         = $dom->createElement('pre');
                $blocks[$blockNum]->manLines = $blockLines;
                BlockPreformatted::handle($blocks[$blockNum]);
                continue; //End of block
            }

            if ($line === '.EE') {
                // Strays
                if ($blockNum > 0) {
                    $blocks[$blockNum]->appendChild($dom->createElement('br'));
                }
                continue;
            }

            if (preg_match('~^\.UR <?(.*?)>?$~u', $line, $matches)) {
                $anchor = $dom->createElement('a');
                $url    = TextContent::interpretString(Macro::parseArgString($matches[1])[0]);
                if (filter_var($url, FILTER_VALIDATE_EMAIL)) {
                    $url = 'mailto:' . $url;
                }
                $anchor->setAttribute('href', $url);
                if ($blockNum === 0) {
                    $blocks[++$blockNum] = $dom->createElement('p');
                }
                $blocks[$blockNum]->appendChild($anchor);
                for ($i = $i + 1; $i < $numLines; ++$i) {
                    $line = $blockNode->manLines[$i];
                    if (preg_match('~^\.UE~u', $line)) {
                        continue 2;
                    }
                    TextContent::interpretAndAppendCommand($anchor, $line);
                }
                throw new Exception('.UR with no corresponding .UE');
            }

            if (preg_match('~^\.nf~u', $line)) {
                $preLines = [];
                for ($i = $i + 1; $i < $numLines; ++$i) {
                    $line = $blockNode->manLines[$i];
                    if (preg_match('~^\.(fi|ad [nb])~u', $line)) {
                        break;
                    } else {
                        $preLines[] = $line;
                    }
                }

                $pre = $dom->createElement('pre');

                if (count($preLines) === 0) {
                    continue;
                }

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

                $pre->manLines = $preLines;
                BlockPreformatted::handle($pre);
                $blocks[++$blockNum] = $pre;
                continue; //End of block
            }

            if (preg_match('~^\.TS~u', $line)) {
                $table = $dom->createElement('table');
                $table->setAttribute('class', 'tbl');
                $blocks[++$blockNum] = $table;

                $columnSeparator = "\t";

                $line = $blockNode->manLines[++$i];
                if (mb_substr($line, -1, 1) === ';') {
                    if (preg_match('~tab\s?\((.)\)~', $line, $matches)) {
                        $columnSeparator = $matches[1];
                    }
                    $line = $blockNode->manLines[++$i];
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
                    $line = $blockNode->manLines[++$i];
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

                            $tdClass = preg_replace('~[fF]?[bB]~', '', $tdClass, -1, $numReplaced);
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
                                    $tBlockLine = $blockNode->manLines[$i];
                                    if (mb_strpos($tBlockLine, 'T}') === 0) {
                                        if (mb_strlen($tBlockLine) > 2) {
                                            $restOfLine = mb_substr($tBlockLine, 3); // also take out separator
                                            $cols       = array_merge($cols, explode($columnSeparator, $restOfLine));
                                        }
                                        $cell->manLines = $tBlockLines;
                                        self::handle($cell);
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

                    $line = $blockNode->manLines[++$i];
                    if (preg_match('~^\.TE~u', $line)) {
                        continue 2;
                    }
                }

                throw new Exception($line . ' - .TS without .TE in ' . $blocks[$blockNum]->tagName . ' at line ' . $i . '. Last line was "' . $blockNode->manLines[$i - 1] . '"');

            }

            // Make tables out of tab-separated lines
            // strpos() > 0: avoid indented stuff
            if ($i < $numLines - 1
              && mb_strlen($line) > 0
              && $line[0] !== '.'
              && strpos($line, "\t") > 0
              && (
                strpos($blockNode->manLines[$i + 1], "\t") > 0
                || (
                  in_array($blockNode->manLines[$i + 1], ['.br', ''])
                  && $i < $numLines - 2
                  && strpos($blockNode->manLines[$i + 2], "\t") > 0
                )
              )
            ) {
                $table               = $dom->createElement('table');
                $blocks[++$blockNum] = $table;
                for (; ; ++$i) {

                    $bits = preg_split('~\t+~', $line);
                    $tr   = $table->appendChild($dom->createElement('tr'));
                    foreach ($bits as $tdLine) {
                        $cell = $dom->createElement('td');
                        TextContent::interpretAndAppendText($cell, $tdLine);
                        $tr->appendChild($cell);
                    }

                    if ($i === $numLines - 1) {
                        break 2;
                    }

                    $line = $blockNode->manLines[$i + 1];

                    if (in_array($line, ['.br', ''])) {
                        ++$i;
                        if ($i === $numLines - 1) {
                            break 2;
                        }
                        $line = $blockNode->manLines[$i + 1];
                    }

                    if (strpos($line, "\t") === false) {
                        continue 2; // Done with table.
                    }

                }
            }

            if ($blockNum === 0) {
                $blocks[++$blockNum] = $dom->createElement('p');
            } else {
                if (in_array($blocks[$blockNum]->tagName, ['div', 'pre', 'code', 'table'])) {
                    // Start a new paragraph after certain blocks
                    $blocks[++$blockNum] = $dom->createElement('p');
                }
            }

            $parentForLine = $blocks[$blockNum];

            if (preg_match('~^\.([RBI][RBI]?|ft (?:[123RBIP]|C[WR]))$~u', $line)) {
                if ($i === $numLines - 1
                  || $line === '.ft R'
                  || $blockNode->manLines[$i + 1] === '.IP http://www.gnutls.org/manual/'
                  || strpos($blockNode->manLines[$i + 1], '.B') === 0
                  || strpos($blockNode->manLines[$i + 1], '.I') === 0
                ) {
                    continue;
                }
                $nextLine = $blockNode->manLines[++$i];
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

            if ($canAppendNextText
              && !in_array(mb_substr($line, 0, 1), ['.', ' '])
              && (mb_strlen($line) < 2 || mb_substr($line, 0, 2) !== '\\.')
              && !preg_match('~\\\\c$~', $line)
            ) {
                while ($i < $numLines - 1) {
                    $nextLine = $blockNode->manLines[$i + 1];
                    if (mb_strlen($nextLine) === 0 || mb_substr($nextLine, 0, 1) === '.'
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

        // Add the blocks
        foreach ($blocks as $block) {
            if ($block->hasChildNodes()) {
                if ($block->childNodes->length > 1 || trim($block->firstChild->textContent) !== '') {
                    $blockNode->appendChild($block);
                }
            }
        }

    }

}
