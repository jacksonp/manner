<?php


class Block_ce
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        if (!preg_match('~^\.\s*ce ?(\d*)$~u', $lines[$i], $matches)) {
            return false;
        }

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        $blockLines       = [];
        $numLinesToCenter = empty($matches[1]) ? 1 : (int)$matches[1];
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
