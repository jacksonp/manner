<?php


class Block_EX
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;


        $blockLines = [];
        for ($i = $i + 1; $i < $numLines; ++$i) {
            $line = $lines[$i];
            if (Request::is($line, 'EE')) {
                break;
            } elseif (Request::is($line, ['nf', 'fi'])) {
                // .EX already marks block as preformatted, just ignore
                continue;
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
