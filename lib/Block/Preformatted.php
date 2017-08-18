<?php
declare(strict_types=1);

class Block_Preformatted implements Block_Template
{

    static function checkAppend(
        DOMElement $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        if (Node::isOrInTag($parentNode,'pre') || !count($lines)) {
            return null;
        }

        if (Request::peepAt($lines[0])['name'] === 'PP') {
            array_shift($lines);
            $parentNode = Blocks::getBlockContainerParent($parentNode, true);
        } else {
            $parentNode = Blocks::getBlockContainerParent($parentNode, false, true);
        }

        /* @var DomElement $pre */
        $pre = $parentNode->ownerDocument->createElement('pre');

        $pre = $parentNode->appendChild($pre);

        return $pre;

    }

}
