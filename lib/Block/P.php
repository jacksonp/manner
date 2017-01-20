<?php


class Block_P implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        int $i,
        ?array $arguments = null,
        ?string $request = null,
        $needOneLineOnly = false
    ) {

        $dom = $parentNode->ownerDocument;

        $blockLines = [];
        for (; $i < count($lines) - 1; ++$i) {
            if (Blocks::lineEndsBlock($lines, $i + 1)) {
                break;
            }
            $blockLines[] = $lines[$i + 1];
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

        return $i;

    }

}
