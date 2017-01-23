<?php

/**
 * Class Block_ti
 * .ti ±N: Temporary indent next line (default scaling indicator m).
 */
class Block_ti implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $request = null,
        $needOneLineOnly = false
    ): bool {

        array_shift($lines);

        $dom = $parentNode->ownerDocument;

        if (!count($lines)) {
            return true;
        }

        $blockLines = [];
        while ($nextRequest = Request::getLine($lines)) {
            if ($nextRequest['request'] === 'ti') {
                // Could be a change in indentation, just add a break for now
                array_shift($lines);
                $blockLines[] = '.br';
                continue;
            } elseif (Blocks::lineEndsBlock($nextRequest, $lines) || $lines[0] === '') {
                // This check has to come after .ti check, as .ti is otherwise a block-ender.
                break;
            } else {
                $blockLines[] = array_shift($lines);
            }
        }

        $block = $dom->createElement('blockquote');
        Blocks::trim($blockLines);
        Roff::parse($block, $blockLines);
        $parentNode->appendBlockIfHasContent($block);

        return true;

    }


}
