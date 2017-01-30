<?php

class Block_SY implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        $commandName = '';

        if (count($request['arguments']) > 0) {
            $commandName = $request['arguments'][0];
        }

        $pre = $parentNode->ownerDocument->createElement('pre');
        if ($commandName !== '') {
            $commandName = trim(TextContent::interpretString($commandName));
            $pre->setAttribute('class', 'synopsis');
            $pre->appendChild(new DOMText($commandName . ' '));
        }

        $pre = $parentNode->appendChild($pre);

        return $pre;

    }

}
