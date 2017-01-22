<?php


class Block_P implements Block_Template
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

        $blockLines = [];
        while (count($lines)) {
            $nextRequest = Request::getLine($lines, 0);
            if (!count($lines) || Blocks::lineEndsBlock($nextRequest, $lines)) {
                break;
            }
            $blockLines[] = array_shift($lines);
        }

        Blocks::trim($blockLines);

        if (count($blockLines) > 0) {
            if ($parentNode->tagName === 'p' && !$parentNode->hasContent()) {
                Roff::parse($parentNode, $blockLines);
            } else {
                $p = $dom->createElement('p');
                Roff::parse($p, $blockLines);
                $p->trimTrailingBrs();
                $parentNode->appendBlockIfHasContent($p);
            }
        }

        return 0;

    }

}
