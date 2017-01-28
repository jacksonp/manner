<?php


class Block_EX implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement {

        array_shift($lines);

        $dom = $parentNode->ownerDocument;

        $blockLines = [];
        while ($nextRequest = Request::getLine($lines)) {
            array_shift($lines);
            if (Block_SS::endSubsection($nextRequest['request']) || in_array($nextRequest['request'], ['TS', 'EE'])) {
                break;
            } elseif (in_array($nextRequest['request'], ['nf', 'fi'])) {
                // .EX already marks block as preformatted, just skip
            } else {
                $blockLines[] = $nextRequest['raw_line'];
            }
        }

        if ($parentNode->tagName === 'p') {
            $parentNode = $parentNode->parentNode;
        }

        $block = $dom->createElement('pre');
        BlockPreformatted::handle($block, $blockLines);
        $parentNode->appendBlockIfHasContent($block);

        return $parentNode;

    }

}
