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

        $dom = $parentNode->ownerDocument;

        $blockLines = [];
        for ($i = $i + 1; $i < count($lines); ++$i) {
            $request = Request::getLine($lines, $i);
            if ($request['request'] === 'Ve') {
                break;
            } else {
                $blockLines[] = $lines[$i];
            }
        }

        $block = $dom->createElement('pre');
        BlockPreformatted::handle($block, $blockLines);
        $parentNode->appendBlockIfHasContent($block);

        return $i;

    }

}
