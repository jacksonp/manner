<?php


class Block_ce
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i, $arguments)
    {

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        $blockLines       = [];
        $numLinesToCenter = count($arguments) === 0 ? 1 : (int)$arguments[0];
        $centerLinesUpTo  = min($i + $numLinesToCenter, $numLines - 1);
        for (; $i < $centerLinesUpTo; ++$i) {
            if (Request::getLine($lines, $i + 1)['request'] === 'ce') {
                break;
            }
            $blockLines[] = $lines[$i + 1];
            $blockLines[] = '.br';
        }
        $block = $dom->createElement('div');
        $block->setAttribute('class', 'center');
        Blocks::trim($blockLines);
        Roff::parse($block, $blockLines);
        $parentNode->appendBlockIfHasContent($block);

        return $i;

    }

}
