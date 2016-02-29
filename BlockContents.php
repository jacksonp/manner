<?php


class BlockContents
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

            if (preg_match('~^\.B (.*)$~', $line, $matches)) {
                if (empty($blocks)) {
                    ++$blockNum;
                    $blocks[$blockNum] = $dom->createElement('p');
                    $parentSectionNode->appendChild($blocks[$blockNum]);
                }
                $textToBold = trim($matches[1], '"');
                $b          = $dom->createElement('strong', $textToBold);
                $blocks[$blockNum]->appendChild($b);
                unset($parentSectionNode->manLines[$i]);
                continue;
            }

            // TODO: change the following to switch on e.g. BR[$bi % 2] ?

            if (preg_match('~^\.RB (.*)$~', $line, $matches)) {
                if (empty($blocks)) {
                    ++$blockNum;
                    $blocks[$blockNum] = $dom->createElement('p');
                    $parentSectionNode->appendChild($blocks[$blockNum]);
                }
                $bits = str_getcsv($matches[1], ' ');
                foreach ($bits as $bi => $bit) {
                    if ($bi % 2 === 0) {
                        $blocks[$blockNum]->appendChild(new DOMText($bit));
                    } else {
                        $blocks[$blockNum]->appendChild($dom->createElement('strong', $bit));
                    }
                }
                unset($parentSectionNode->manLines[$i]);
                continue;
            }

            if (preg_match('~^\.BR (.*)$~', $line, $matches)) {
                if (empty($blocks)) {
                    ++$blockNum;
                    $blocks[$blockNum] = $dom->createElement('p');
                    $parentSectionNode->appendChild($blocks[$blockNum]);
                }
                $bits = str_getcsv($matches[1], ' ');
                foreach ($bits as $bi => $bit) {
                    if ($bi % 2 === 0) {
                        $blocks[$blockNum]->appendChild($dom->createElement('strong', $bit));
                    } else {
                        $blocks[$blockNum]->appendChild(new DOMText($bit));
                    }
                }
                unset($parentSectionNode->manLines[$i]);
                continue;
            }

            if (preg_match('~^\.RI (.*)$~', $line, $matches)) {
                if (empty($blocks)) {
                    ++$blockNum;
                    $blocks[$blockNum] = $dom->createElement('p');
                    $parentSectionNode->appendChild($blocks[$blockNum]);
                }
                $bits = str_getcsv($matches[1], ' ');
                foreach ($bits as $bi => $bit) {
                    if ($bi % 2 === 0) {
                        $blocks[$blockNum]->appendChild(new DOMText($bit));
                    } else {
                        $blocks[$blockNum]->appendChild($dom->createElement('em', $bit));
                    }
                }
                unset($parentSectionNode->manLines[$i]);
                continue;
            }

            if (
              preg_match('~^\.[LP]?P$~', $line, $matches)
              || preg_match('~^\.sp$~', $line, $matches)
            ) {
                ++$blockNum;
                $blocks[$blockNum] = $dom->createElement('p');
                unset($parentSectionNode->manLines[$i]);
                continue;
            }

            // TODO $matches[1] will contain the indentation level, try to use this to handle nested dls?
            if (preg_match('~^\.TP ?(.*)$~', $line)) {
                if (empty($blocks) || $blocks[$blockNum]->tagName !== 'dl') {
                    ++$blockNum;
                    $blocks[$blockNum] = $dom->createElement('dl');
                    $parentSectionNode->appendChild($blocks[$blockNum]);
                }

                unset($parentSectionNode->manLines[$i]);

                $dtLine = $parentSectionNode->manLines[++$i];
                unset($parentSectionNode->manLines[$i]);
                $ddLine = $parentSectionNode->manLines[++$i];
                unset($parentSectionNode->manLines[$i]);

                $dt = $dom->createElement('dt');
                TextContent::interpretAndAppend($dt, $dtLine);
                $blocks[$blockNum]->appendChild($dt);

                $dd = $dom->createElement('dd');
                TextContent::interpretAndAppend($dd, $ddLine);
                $blocks[$blockNum]->appendChild($dd);

                continue;
            }


            // TODO:  --group-directories-first in ls.1 - separate para rather than br?
            if (preg_match('~^\.IP$~', $line)) {
                if (empty($blocks) || $blocks[$blockNum]->lastChild->tagName !== 'dd') {
                    throw new Exception($line . ' - unexpected .IP');
                }
                $blocks[$blockNum]->lastChild->appendChild($dom->createElement('br', $line));
                continue;
            }

            if (preg_match('~^\.br~', $line)) {
                $blocks[$blockNum]->appendChild($dom->createElement('br', $line));
                continue;
            }

            // FAIL on unknown command
            if (preg_match('~^\.~', $line, $matches)) {
                echo 'BlockContents status:', PHP_EOL;
                Debug::echoTidy($dom->saveHTML($parentSectionNode));
                echo PHP_EOL, PHP_EOL;
                var_dump($parentSectionNode->manLines);
                echo PHP_EOL, PHP_EOL;
                echo $line, ' - unknown command.', PHP_EOL;
                exit;
            }

            if ($blockNum === 0) {
                ++$blockNum;
                $blocks[$blockNum] = $dom->createElement('p');
            }

            if ($blocks[$blockNum]->tagName === 'dl') {
                TextContent::interpretAndAppend($blocks[$blockNum]->lastChild, $line);
            } else {
                TextContent::interpretAndAppend($blocks[$blockNum], $line);
            }


        }

        // Add the blocks
        foreach ($blocks as $block) {
            $parentSectionNode->appendChild($block);
        }

    }

}
