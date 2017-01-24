<?php


class Block_ce implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $request = null,
        $needOneLineOnly = false
    ): ?DOMElement {

        array_shift($lines);

        $dom = $parentNode->ownerDocument;

        $blockLines = [];
        $numLinesToCenter = count($request['arguments']) === 0 ? 1 : (int)$request['arguments'][0];
        $centerLinesUpTo = min($numLinesToCenter, count($lines));
        for ($i = 0; $i < $centerLinesUpTo; ++$i) {
            if (Request::getLine($lines)['request'] === 'ce') {
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

        return null;

    }

}
