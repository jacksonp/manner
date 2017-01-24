<?php


class Block_P implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $request = null,
        $needOneLineOnly = false
    ): ?DOMElement {

        array_shift($lines);

        $dom = $parentNode->ownerDocument;

        $blockLines = [];
        while ($nextRequest = Request::getLine($lines)) {
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
                $parentNode->appendBlockIfHasContent($p);
            }
        }

        return null;

    }

}
