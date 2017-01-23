<?php


class Block_ce implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $arguments = null,
        ?string $request = null,
        $needOneLineOnly = false
    ) {

        array_shift($lines);

        $dom = $parentNode->ownerDocument;

        $blockLines       = [];
        $numLinesToCenter = count($arguments) === 0 ? 1 : (int)$arguments[0];
        $centerLinesUpTo  = min($numLinesToCenter, count($lines));
        for ($i = 0; $i < $centerLinesUpTo; ++$i) {
            if (Request::getLine($lines, 0)['request'] === 'ce') {
                break;
            }
            $blockLines[] = array_shift($lines);
            $blockLines[] = '.br';
        }
        $block = $dom->createElement('div');
        $block->setAttribute('class', 'center');
        Blocks::trim($blockLines);
        Roff::parse($block, $blockLines);
        $parentNode->appendBlockIfHasContent($block);

        return 0;

    }

}
