<?php


class Block_EX
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {
        if (!preg_match('~^\.EX~u', $lines[$i])) {
            return false;
        }

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;


        $blockLines = [];
        for ($i = $i + 1; $i < $numLines; ++$i) {
            $line = $lines[$i];
            if (preg_match('~^\.EE~u', $line)) {
                break;
            } elseif (preg_match('~^\.(nf|fi)~u', $line)) {
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
