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

        if ($pre = $parentNode->ancestor('pre')) {
            Block_Preformatted::reset();
            return $pre->parentNode;
        } else {
            return null;
        }

    }

}
