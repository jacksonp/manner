<?php


class Block_Vb implements Block_Template
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

        $blockLines = [];
        while (count($lines)) {
            $request = Request::getLine($lines, 0);
            if ($request['request'] === 'Ve') {
                array_shift($lines);
                break;
            } else {
                $blockLines[] = array_shift($lines);
            }
        }

        $block = $dom->createElement('pre');
        BlockPreformatted::handle($block, $blockLines);
        $parentNode->appendBlockIfHasContent($block);

        return 0;

    }

}
