<?php


class Block_Vb
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        $blockLines = [];
        for ($i = $i + 1; $i < $numLines; ++$i) {
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
