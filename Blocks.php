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

            if (preg_match('~^\.[LP]?P$~u', $line) || preg_match('~^\.sp$~u', $line)) {
                ++$blockNum;
                $blocks[$blockNum] = $dom->createElement('p');
                continue;
            }

            // TODO $matches[1] will contain the indentation level, try to use this to handle nested dls?
            if (preg_match('~^\.TP ?(.*)$~u', $line)) {
                if (empty($blocks) || $blocks[$blockNum]->tagName !== 'dl') {
                    ++$blockNum;
                    $blocks[$blockNum] = $dom->createElement('dl');
                }

                $dtLine = $parentSectionNode->manLines[++$i];
                $ddLine = $parentSectionNode->manLines[++$i];

                $dt = $dom->createElement('dt');
                TextContent::interpretAndAppendCommand($dt, $dtLine);
                $blocks[$blockNum]->appendChild($dt);

                $dd = $dom->createElement('dd');
                TextContent::interpretAndAppendCommand($dd, $ddLine);
                $blocks[$blockNum]->appendChild($dd);

                continue;
            }

            // TODO:  --group-directories-first in ls.1 - separate para rather than br?
            if (preg_match('~^\.IP$~u', $line)) {
                if (empty($blocks) || $blocks[$blockNum]->lastChild->tagName !== 'dd') {
                    throw new Exception($line . ' - unexpected .IP');
                }
                $blocks[$blockNum]->lastChild->appendChild($dom->createElement('br', $line));
                continue;
            }

            if ($blockNum === 0) {
                ++$blockNum;
                $blocks[$blockNum] = $dom->createElement('p');
            }

            if ($blocks[$blockNum]->tagName === 'dl') {
                TextContent::interpretAndAppendCommand($blocks[$blockNum]->lastChild, $line);
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
