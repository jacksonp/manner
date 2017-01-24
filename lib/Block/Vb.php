<?php


class Block_Vb implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement {

        array_shift($lines);

        $dom = $parentNode->ownerDocument;

        $blockLines = [];
        while ($request = Request::getLine($lines)) {
            array_shift($lines);
            if ($request['request'] === 'Ve') {
                break;
            } else {
                $blockLines[] = $request['raw_line'];
            }
        }

        $block = $dom->createElement('pre');
        BlockPreformatted::handle($block, $blockLines);
        $parentNode->appendBlockIfHasContent($block);

        return null;

    }

}
