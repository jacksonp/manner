<?php
declare(strict_types = 1);

class Block_ad implements Block_Template
{

    static function checkAppend(
        DOMElement $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        if (in_array($request['arg_string'], ['', 'n', 'b']) && $parentNode->tagName === 'pre') {
            PreformattedOutput::reset();
            return $parentNode->parentNode;
        } else {
            return null;
        }

    }

}
