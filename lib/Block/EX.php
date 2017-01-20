<?php


class Block_EX
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        $blockLines = [];
        while ($i < $numLines - 1) {
            $nextRequest = Request::getLine($lines, $i + 1);
            if (Block_SS::endSubsection($nextRequest['request']) || in_array($nextRequest['request'], ['TS', 'EE'])) {
                break;
            } elseif (in_array($nextRequest['request'], ['nf', 'fi'])) {
                // .EX already marks block as preformatted, just skip
            } else {
                $blockLines[] = $lines[$i + 1];
            }
            ++$i;
        }

        $block = $dom->createElement('pre');
        BlockPreformatted::handle($block, $blockLines);
        $parentNode->appendBlockIfHasContent($block);

        return $i;

    }

}
