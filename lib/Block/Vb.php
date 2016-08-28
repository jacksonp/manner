<?php


class Block_Vb
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {
        if (!Request::is($lines[$i], 'Vb')) {
            return false;
        }

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;


        $blockLines = [];
        for ($i = $i + 1; $i < $numLines; ++$i) {
            $line = $lines[$i];
            if (Request::is($line, 'Ve')) {
                break;
            } else {
                $blockLines[] = $line;
            }
        }

        $block = $dom->createElement('pre');
        BlockPreformatted::handle($block, $blockLines);
        $parentNode->appendBlockIfHasContent($block);

        return $i;

    }

}
