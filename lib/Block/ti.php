<?php


class Block_ti
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        if ($i === $numLines - 1) {
            return $i;
        }

        $blockLines = [];
        for (; $i < $numLines - 1; ++$i) {
            $line = $lines[$i + 1];
            if (Request::is($line, 'ti')) {
                // Could be a change in indentation, just add a break for now
                $blockLines[] = '.br';
                continue;
            } elseif (Blocks::lineEndsBlock($lines, $i + 1) || $lines[$i + 1] === '') {
                // This check has to come after .ti check, as .ti is otherwise a block-ender.
                break;
            } else {
                $blockLines[] = $line;
            }
        }

        $block = $dom->createElement('blockquote');
        Blocks::trim($blockLines);
        Blocks::handle($block, $blockLines);
        $parentNode->appendBlockIfHasContent($block);

        return $i;

    }


}
