<?php


class Block_ti implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $arguments = null,
        ?string $request = null,
        $needOneLineOnly = false
    ) {

        $dom      = $parentNode->ownerDocument;

        if ($i === count($lines) - 1) {
            return $i;
        }

        $blockLines = [];
        for (; $i < count($lines) - 1; ++$i) {
            $nextRequest = Request::getLine($lines, $i + 1);
            if ($nextRequest['request'] === 'ti') {
                // Could be a change in indentation, just add a break for now
                $blockLines[] = '.br';
                continue;
            } elseif (Blocks::lineEndsBlock($lines, $i + 1) || $lines[$i + 1] === '') {
                // This check has to come after .ti check, as .ti is otherwise a block-ender.
                break;
            } else {
                $blockLines[] = $lines[$i + 1];
            }
        }

        $block = $dom->createElement('blockquote');
        Blocks::trim($blockLines);
        Roff::parse($block, $blockLines);
        $parentNode->appendBlockIfHasContent($block);

        return $i;

    }


}
