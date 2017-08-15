<?php
declare(strict_types=1);

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
            PreformattedOutput::reset();
            return $pre->parentNode;
        } else {
            return null;
        }

    }

}
