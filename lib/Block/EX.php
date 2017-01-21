<?php


class Block_EX implements Block_Template
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
            if (Block_SS::endSubsection($nextRequest['request']) || in_array($nextRequest['request'], ['TS', 'EE'])) {
                array_shift($lines);
                break;
            } elseif (in_array($nextRequest['request'], ['nf', 'fi'])) {
                // .EX already marks block as preformatted, just skip
                array_shift($lines);
            } else {
                $blockLines[] = array_shift($lines);
            }
        }

        $block = $dom->createElement('pre');
        BlockPreformatted::handle($block, $blockLines);
        $parentNode->appendBlockIfHasContent($block);

        return 0;

    }

}
