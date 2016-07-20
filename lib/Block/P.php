<?php


class Block_P
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i, $arguments)
    {

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        $blockLines = [];
        for (; $i < $numLines - 1; ++$i) {
            if (Blocks::lineEndsBlock($lines, $i + 1)) {
                break;
            }
            $blockLines[] = $lines[$i + 1];
        }

        if (count($blockLines) > 0) {
            if ($parentNode->tagName === 'p' and !$parentNode->hasContent()) {
                Blocks::handle($parentNode, $blockLines);
            } else {
                $p = $dom->createElement('p');
                Blocks::handle($p, $blockLines);
                $parentNode->appendBlockIfHasContent($p);
            }
        }

        return $i;

    }

}
