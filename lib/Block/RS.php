<?php
declare(strict_types = 1);

class Block_RS implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        $dom = $parentNode->ownerDocument;

        $className = 'indent';
        if (count($request['arguments']) > 0) {
            $thisIndent = Roff_Unit::normalize($request['arguments'][0]);
            if ($thisIndent) { // note this filters out 0s
                $className .= '-' . $thisIndent;
            }
        }

        $parentNode = Blocks::getBlockContainerParent($parentNode);

        if ($className === 'indent' && $parentNode->tagName === 'div' && $parentNode->getAttribute('class') === 'indent') {
            array_unshift($lines, '.br');
            return null;
        }

        /* @var DomElement $div */
        $div = $dom->createElement('div');
        $div->setAttribute('class', $className);

        $div = $parentNode->appendChild($div);
        return $div;

    }

}
