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

            // empty lines cause a new para also, see sar.1
            if (preg_match('~^\.[HLP]?P$~u', $line) || preg_match('~^\.sp$~u', $line)) {
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
                    if (count($bits) > 1) {
                        throw new Exception($line . ' - cannot handle .IP with indentation');
                    }
                    // Copied from .TP:
                    if (empty($blocks) || $blocks[$blockNum]->tagName !== 'dl') {
                        ++$blockNum;
                        $blocks[$blockNum] = $dom->createElement('dl');
                    }
                    $dt     = $dom->createElement('dt');
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

            if (preg_match('~^\.RS~u', $line)) {
                // see cal.1 for maybe an easy start on supporting .RS/.RE
                throw new Exception($line . ' - no support for .RS yet');
            }

            if (preg_match('~^\.RE~u', $line)) {
                throw new Exception($line . ' - no support for .RE yet');
            }

            if (preg_match('~^\.EX~u', $line)) {
                throw new Exception($line . ' - no support for .EX yet');
            }

            if (preg_match('~^\.EE~u', $line)) {
                throw new Exception($line . ' - no support for .EE yet');
            }

            if (preg_match('~^\.IX~u', $line)) {
                throw new Exception($line . ' - no support for .IX yet');
            }

            if (preg_match('~^\.[BI]$~u', $line)) {
                $line .= ' ' . $parentSectionNode->manLines[++$i];
            }

            if ($blockNum === 0) {
                ++$blockNum;
                $blocks[$blockNum] = $dom->createElement('p');
            }

            if ($blocks[$blockNum]->tagName === 'dl') {
                if ($blocks[$blockNum]->lastChild->tagName === 'dt') {
                    $dd = $dom->createElement('dd');
                    TextContent::interpretAndAppendCommand($dd, $line);
                    $blocks[$blockNum]->appendChild($dd);
                } else {
                    TextContent::interpretAndAppendCommand($blocks[$blockNum]->lastChild, $line);
                }
            } else {
                TextContent::interpretAndAppendCommand($blocks[$blockNum], $line);
            }

        }

        // Add the blocks
        foreach ($blocks as $block) {
            $parentSectionNode->appendChild($block);
        }

    }

}
