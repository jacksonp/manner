<?php


class Block_ce
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i, $arguments)
    {

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        $blockLines       = [];
        $numLinesToCenter = empty($arguments[0]) ? 1 : (int)$arguments[0];
        $centerLinesUpTo  = min($i + $numLinesToCenter, $numLines - 1);
        for (; $i < $centerLinesUpTo; ++$i) {
            $nextLine = $lines[$i + 1];
            if (Request::is($nextLine, 'ce')) {
                break;
            }
            $blockLines[] = $nextLine;
            $blockLines[] = '.br';
        }
        $block = $dom->createElement('div');
        $block->setAttribute('class', 'center');
        Blocks::handle($block, $blockLines);
        $parentNode->appendBlockIfHasContent($block);

        return $i;

    }

}
