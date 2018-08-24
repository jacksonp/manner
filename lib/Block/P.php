<?php
declare(strict_types=1);

class Block_P implements Block_Template
{

    /**
     * @param DOMElement $parentNode
     * @param array $lines
     * @param array $request
     * @param bool $needOneLineOnly
     * @return DOMElement|null
     * @throws Exception
     */
    static function checkAppend(
        DOMElement $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        $man = Man::instance();
        $man->resetIndentationToDefault();
        $man->resetFonts();

        if ($parentNode->tagName === 'p' && !Node::hasContent($parentNode)) {
            return null; // Use existing parent node for content that will follow.
        } else {
            $parentNode = Blocks::getBlockContainerParent($parentNode);
            if ($parentNode->tagName === 'dd') {
                $parentNode = $parentNode->parentNode->parentNode;
            }
            /* @var DomElement $p */
            $p = $parentNode->ownerDocument->createElement('p');
            $p = $parentNode->appendChild($p);
            return $p;
        }

    }

}
