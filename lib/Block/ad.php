<?php

class Block_ad implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        if (in_array($request['arg_string'], ['', 'n', 'b']) && $parentNode->tagName === 'pre') {
            Block_Preformatted::reset();
            return $parentNode->parentNode;
        } else {
            return null;
        }

    }

}
