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
        ?array $arguments = null,
        ?string $request = null,
        $needOneLineOnly = false
    ) {

        array_shift($lines);

        $dom = $parentNode->ownerDocument;

        if (!count($lines)) {
            return 0;
        }

        $blockLines = [];
        while (count($lines)) {
            $nextRequest = Request::getLine($lines, 0);
            if ($nextRequest['request'] === 'ti') {
                // Could be a change in indentation, just add a break for now
                array_shift($lines);
                $blockLines[] = '.br';
                continue;
            } elseif (Blocks::lineEndsBlock($lines, 0) || $lines[0] === '') {
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

        return 0;

    }


}
