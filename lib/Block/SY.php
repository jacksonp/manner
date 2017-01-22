<?php


class Block_SY implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $arguments = null,
        ?string $request = null,
        $needOneLineOnly = false
    ) {

        array_shift($lines);

        // These get swallowed:
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
        while (count($lines)) {
            $request = Request::getLine($lines, 0);
            if ($request['request'] === 'YS') {
                array_shift($lines);
                break;
            } elseif ($request['request'] === 'SY') {
                break;
            }
            $preLines[] = array_shift($lines);
        }

        if (count($preLines) === 0) {
            return 0;
        }

        BlockPreformatted::handle($pre, $preLines);
        $parentNode->appendChild($pre);

        return 0;
    }


}
