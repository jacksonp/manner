<?php
declare(strict_types=1);

class Block_EndPreformatted implements Block_Template
{

    static function checkAppend(
        DOMElement $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        if ($pre = Node::ancestor($parentNode, 'pre')) {
            PreformattedOutput::reset();
            return $pre->parentNode;
        } else {
            return null;
        }

    }

}
