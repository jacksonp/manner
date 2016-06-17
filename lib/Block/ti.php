<?php


class Block_ti
{

    static function checkAppend(HybridNode $parentNode, $lines, $i)
    {

        // .ti = temporary indent
        if (!preg_match('~^\.ti ?(.*)$~u', $lines[$i], $matches)) {
            return false;
        }

        $blockEndsRegex = '~^\.[HTLP]?P~u';

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        if ($i === $numLines - 1) {
            return $i;
        }

        $blockLines = [];
        for (; $i < $numLines - 1; ++$i) {
            $line = $lines[$i + 1];
            if (preg_match($blockEndsRegex, $line)) {
                break;
            } elseif (preg_match('~^\.ti~u', $line)) {
                // Could be a change in indentation, just ignore for now
                continue;
            } else {
                $blockLines[] = $line;
            }
        }

        $block = $dom->createElement('blockquote');
        Blocks::handle($block, $blockLines);
        $parentNode->appendBlockIfHasContent($block);

        return $i;

    }


}
