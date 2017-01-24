<?php


class Block_SY implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $request = null,
        $needOneLineOnly = false
    ): ?DOMElement {

        array_shift($lines);

        // These get swallowed:
        $dom         = $parentNode->ownerDocument;
        $commandName = '';

        if (count($request['arguments']) > 0) {
            $commandName = $request['arguments'][0];
        }

        $pre = $dom->createElement('pre');
        if ($commandName !== '') {
            $commandName = trim(TextContent::interpretString($commandName));
            $pre->setAttribute('class', 'synopsis');
            $pre->appendChild(new DOMText($commandName . ' '));
        }

        $preLines = [];
        while ($request = Request::getLine($lines)) {
            if ($request['request'] === 'YS') {
                array_shift($lines);
                break;
            } elseif ($request['request'] === 'SY' || Blocks::lineEndsBlock($request, $lines)) {
                break;
            }
            $preLines[] = array_shift($lines);
        }

        if (count($preLines) === 0) {
            return null;
        }

        BlockPreformatted::handle($pre, $preLines);
        $parentNode->appendChild($pre);

        return null;
    }


}
