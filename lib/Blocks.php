<?php


class Blocks
{

    static function handle(HybridNode $parentSectionNode)
    {

        $dom = $parentSectionNode->ownerDocument;

        /** @var HybridNode[] $blocks */
        $blocks   = [];
        $blockNum = 0;

        // Now we have no more sections in manLines, do definition lists because .TP is a bit special in that we need to keep the 1st line separate for the definition and not merge text as we would otherwise.
        $numLines = count($parentSectionNode->manLines);
        for ($i = 0; $i < $numLines; ++$i) {
            $line = $parentSectionNode->manLines[$i];

            $canAppendNextText = true;

            // empty lines cause a new para also, see sar.1
            if (preg_match('~^\.([LP]?P$|HP)~u', $line)) {
                ++$blockNum;
                $blocks[$blockNum] = $dom->createElement('p');
                continue;
            }

            // See https://www.gnu.org/software/groff/manual/html_node/Implicit-Line-Breaks.html
            if (mb_strlen($line) === 0) {
                if ($i < $numLines - 1 && mb_strlen($parentSectionNode->manLines[$i + 1]) === 0) {
                    continue; // next line is also empty
                }

                if ($blockNum > 0) {
                    if ($blocks[$blockNum]->tagName === 'dl') {
                        $lastBlockChild = $blocks[$blockNum]->lastChild;
                        // Not sure how to handle new paragraph blocks in dls yet, trying this for now:
                        if ($lastBlockChild->tagName === 'dd') {
                            if ($i < $numLines - 1
                              && !in_array(substr($parentSectionNode->manLines[$i + 1], 0, 3), ['.TP', '.SH'])
                            ) {
                                $lastBlockChild->appendChild($dom->createElement('br'));
                                $lastBlockChild->appendChild($dom->createElement('br'));
                            }
                        }
                    } else {
                        ++$blockNum;
                        $blocks[$blockNum] = $dom->createElement('p');
                    }
                }
                continue;
            }

            // TODO $matches[1] will contain the indentation level, try to use this to handle nested dls?
            if (preg_match('~^\.TP ?(.*)$~u', $line, $matches)) {
                // if this is the last line in a section, it's a bug in the man page, just ignore.
                if ($i === $numLines - 1) {
                    continue;
                }
                if (empty($blocks) || $blocks[$blockNum]->tagName !== 'dl') {
                    ++$blockNum;
                    $blocks[$blockNum] = $dom->createElement('dl');
                }
                $dtLine = $parentSectionNode->manLines[++$i];
                $dt     = $dom->createElement('dt');
                TextContent::interpretAndAppendCommand($dt, $dtLine);
                $blocks[$blockNum]->appendChild($dt);
                continue;
            }

            if (preg_match('~^\.TQ$~u', $line)) {
                if (empty($blocks) || $blocks[$blockNum]->tagName !== 'dl' || $blocks[$blockNum]->lastChild->tagName !== 'dt') {
                    throw new Exception($line . ' - unexpected .TQ not after <dt>');
                }
                $dtLine = $parentSectionNode->manLines[++$i];
                $dt     = $dom->createElement('dt');
                TextContent::interpretAndAppendCommand($dt, $dtLine);
                $blocks[$blockNum]->appendChild($dt);
                continue;
            }

            // TODO:  --group-directories-first in ls.1 - separate para rather than br?
            // TODO $matches[1] will contain the indentation level, try to use this to handle nested dls?
            if (preg_match('~^\.IP ?(.*)$~u', $line, $matches)) {
                if (!empty($matches[1])) {

                    $bits = str_getcsv($matches[1], ' ');
                    // Copied from .TP:
                    if (empty($blocks) || $blocks[$blockNum]->tagName !== 'dl') {
                        ++$blockNum;
                        $blocks[$blockNum] = $dom->createElement('dl');
                        if (count($bits) > 1) {
                            $blocks[$blockNum]->setAttribute('class', 'indent-' . $bits[1]);
                        }
                    }
                    $dt = $dom->createElement('dt');
                    TextContent::interpretAndAppendCommand($dt, $bits[0]);
                    $blocks[$blockNum]->appendChild($dt);
                    continue;

                } elseif (empty($blocks)) {
                    ++$blockNum;
                    $blocks[$blockNum] = $dom->createElement('p');
                    continue;
                } elseif ($blocks[$blockNum]->tagName === 'dl' && $blocks[$blockNum]->lastChild->tagName === 'dd') {
                    $blocks[$blockNum]->lastChild->appendChild($dom->createElement('br'));
                } elseif ($blocks[$blockNum]->tagName === 'p') {
                    ++$blockNum;
                    $blocks[$blockNum] = $dom->createElement('blockquote');
                } elseif ($blocks[$blockNum]->tagName === 'blockquote') {
                    // Already in previous .IP,
                    $blocks[$blockNum]->appendChild($dom->createElement('br'));
                    $blocks[$blockNum]->appendChild($dom->createElement('br'));
                } else {
                    throw new Exception($line . ' - unexpected .IP in ' . $blocks[$blockNum]->tagName);
                }
                continue;
            }

            // see cal.1 for maybe an easy start on supporting .RS/.RE
            if (preg_match('~^\.RS ?(.*)$~u', $line)) {
                $rsLevel = 1;
                $rsLines = [];
                for ($i = $i + 1; $i < $numLines; ++$i) {
                    $line = $parentSectionNode->manLines[$i];
                    if (preg_match('~^\.RS~u', $line)) {
                        ++$rsLevel;
                    } elseif (preg_match('~^\.RE~u', $line)) {
                        --$rsLevel;
                        if ($rsLevel === 0) {

                            if (trim(implode('', $rsLines)) === '') {
                                // Skip empty .RS blocks
                                continue 2;
                            }

                            if ($blockNum > 0 && $blocks[$blockNum]->tagName === 'dl') {
                                $rsBlock = $blocks[$blockNum]->lastChild;
                                $rsBlock->appendChild($dom->createElement('br'));
                            } else {
                                ++$blockNum;
                                $blocks[$blockNum] = $dom->createElement('div');
                                $className         = 'indent';
                                if (!empty($matches[1])) {
                                    $className .= '-' . trim($matches[1]);
                                }
                                $blocks[$blockNum]->setAttribute('class', $className);
                                $rsBlock = $blocks[$blockNum];
                            }
                            $rsBlock->manLines = $rsLines;
                            self::handle($rsBlock);
                            continue 2; //End of block
                        }
                    }
                    $rsLines[] = $line;
/*                    if ($rsLevel === 1
                      && mb_strlen($line) > 1 && mb_substr($line, 0, 1) !== '.'
                      && $i < $numLines - 1
                      && mb_strlen($parentSectionNode->manLines[$i + 1]) > 1
                      && mb_substr($parentSectionNode->manLines[$i + 1], 0, 1) !== '.'
                    ) {
                        $rsLines[] = '.br';
                    }
*/
                }
                throw new Exception($line . '.RS without corresponding .RE');
            }

            if (preg_match('~^\.RE~u', $line)) {
                throw new Exception($line . ' - unexpected .RE');
            }

            if (preg_match('~^\.EX~u', $line)) {
                $blocks[++$blockNum] = $dom->createElement('code');
                for ($i = $i + 1; $i < $numLines; ++$i) {
                    $line = $parentSectionNode->manLines[$i];
                    if (preg_match('~^\.EE~u', $line)) {
                        continue 2; // End of example
                    }
                    TextContent::interpretAndAppendCommand($blocks[$blockNum], $line);
                }
                throw new Exception($line . '.EX without corresponding .EE');
            }

            if (preg_match('~^\.nf~u', $line)) {
                $blocks[++$blockNum] = $dom->createElement('pre');
                for ($i = $i + 1; $i < $numLines; ++$i) {
                    $line = $parentSectionNode->manLines[$i];
                    if (preg_match('~^\.fi~u', $line)) {
                        continue 2; // End of no-fill
                    }
                    TextContent::interpretAndAppendCommand($blocks[$blockNum], $line);
//                    if (!preg_match('~^indent~', $parentSectionNode->getAttribute('class'))) {
                        // .RS, aka .indent adds .br...
//                        $blocks[$blockNum]->appendChild($dom->createElement('br'));
                        $blocks[$blockNum]->appendChild(new DOMText("\n"));
//                    }
                }
                throw new Exception($line . '.nf without corresponding .fi');
            }


            if ($blockNum === 0 || in_array($blocks[$blockNum]->tagName, ['div', 'code', 'pre'])) {
                ++$blockNum;
                $blocks[$blockNum] = $dom->createElement('p');
            }

            $parentForLine = null;

            if ($blocks[$blockNum]->tagName === 'dl') {
                if ($blocks[$blockNum]->lastChild->tagName === 'dt') {
                    $dd            = $dom->createElement('dd');
                    $parentForLine = $dd;
                    $blocks[$blockNum]->appendChild($dd);
                } else {
                    $parentForLine = $blocks[$blockNum]->lastChild;
                }
            } else {
                $parentForLine = $blocks[$blockNum];
            }


            if (preg_match('~^\.[RBI][RBI]?$~u', $line)) {
                if ($i === $numLines - 1 || $parentSectionNode->manLines[$i + 1] === '.IP http://www.gnutls.org/manual/') {
                    continue;
                }
                $nextLine = $parentSectionNode->manLines[++$i];
                if (mb_strlen($nextLine) === 0) {
                    continue;
                } else {
                    if ($nextLine[0] === '.') {
                        throw new Exception($nextLine . ' - ' . $line . ' followed by non-text');
                    } else {
                        if ($line === '.B') {
                            $strongNode    = $parentForLine->appendChild($dom->createElement('strong'));
                            $parentForLine = $strongNode;
                            $line          = $nextLine;
                        } elseif ($line === '.I') {
                            $emNode        = $parentForLine->appendChild($dom->createElement('em'));
                            $parentForLine = $emNode;
                            $line          = $nextLine;
                        } else {
                            $line .= ' ' . $nextLine;
                        }
                        $canAppendNextText = false;
                    }
                }
            }

            if (is_null($parentForLine)) {
                throw new Exception($line - ' $parentForLine is null.');
            }

            if ($canAppendNextText && !in_array(mb_substr($line, 0, 1), ['.', ' ']) && !preg_match('~\\\\c$~', $line)) {
                while ($i < $numLines - 1) {
                    $nextLine = $parentSectionNode->manLines[$i + 1];
                    if (mb_strlen($nextLine) === 0 || mb_substr($nextLine, 0, 1) === '.') {
                        break;
                    }
                    $line .= ' ' . $nextLine;
                    ++$i;
                }
            }

            TextContent::interpretAndAppendCommand($parentForLine, $line);

        }

        // Add the blocks
        foreach ($blocks as $block) {
            $parentSectionNode->appendChild($block);
        }

    }

}
