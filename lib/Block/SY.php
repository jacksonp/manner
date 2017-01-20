<?php


class Block_SY
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i, array $arguments)
    {

        // These get swallowed:
        $blockEnds   = ['.YS'];
        $numLines    = count($lines);
        $dom         = $parentNode->ownerDocument;
        $commandName = '';

        if (count($arguments) > 0) {
            $commandName = $arguments[0];
        }

        $pre = $dom->createElement('pre');
        if ($commandName !== '') {
            $commandName = trim(TextContent::interpretString($commandName));
            $pre->setAttribute('class', 'synopsis');
            $pre->appendChild(new DOMText($commandName . ' '));
        }

        $preLines = [];
        for ($i = $i + 1; $i < $numLines; ++$i) {
            $request = Request::getLine($lines, $i);
            if (in_array($lines[$i], $blockEnds)) {
                break;
            } elseif ($request['request'] === 'SY') {
                --$i;
                break;
            }
            $preLines[] = $lines[$i];
        }

        if (count($preLines) === 0) {
            return $i;
        }

        BlockPreformatted::handle($pre, $preLines);
        $parentNode->appendChild($pre);

        return $i;
    }


}
