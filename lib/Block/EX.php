<?php


class Block_EX
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        $blockLines = [];
        while ($i < $numLines - 1) {
            $line    = $lines[$i + 1];
            $request = Request::get($line);
            if (Block_SS::endSubsection($line) or in_array($request['request'], ['TS', 'EE'])) {
                break;
            } elseif (Request::is($line, ['nf', 'fi'])) {
                // .EX already marks block as preformatted, just skip
            } else {
                $blockLines[] = $line;
            }
            ++$i;
        }

        $block = $dom->createElement('pre');
        BlockPreformatted::handle($block, $blockLines);
        $parentNode->appendBlockIfHasContent($block);

        return $i;

    }

}
