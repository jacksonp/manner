<?php
declare(strict_types=1);

/**
 * Class Block_ti
 * .ti ±N: Temporary indent next line (default scaling indicator m).
 */
class Block_ti implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        $indentVal = '0';
        if (count($request['arguments'])) {
            $indentVal = Roff_Unit::normalize($request['arguments'][0]);
        }

        if ($parentNode->getAttribute('indent') === $indentVal) {
            if ($parentNode->lastChild->nodeType !== XML_ELEMENT_NODE || $parentNode->lastChild->tagName !== 'br') {
                Inline_VerticalSpace::addBR($parentNode);
            }
            return $parentNode;
        }

        $parentNode = Blocks::getBlockContainerParent($parentNode);
        /* @var DomElement $p */
        $p = $parentNode->ownerDocument->createElement('p');
        $p = $parentNode->appendChild($p);
        $p->setAttribute('indent', $indentVal);
        return $p;

    }

}
