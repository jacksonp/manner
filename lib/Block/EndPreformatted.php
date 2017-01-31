<?php

class Block_EndPreformatted implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        if ($parentNode->isOrInTag('pre')) {
            Block_Preformatted::reset();
            return $parentNode->ancestor('pre')->parentNode;
        } else {
            return null;
        }

    }

}
