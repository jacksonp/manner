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

        if (count($request['arguments']) && $request['arguments'][0] === '0') {
            $parentNode = $parentNode->ancestor('section');
        } else {
            $parentNode = Blocks::getBlockContainerParent($parentNode);
        }


        $className = 'indent';
        if (count($request['arguments']) > 0) {
            $thisIndent = Roff_Unit::normalize($request['arguments'][0]);
            if ($thisIndent) { // note this filters out 0s
                $className .= '-' . $thisIndent;
            }
        }

        /* @var DomElement $div */
        $div = $dom->createElement('div');
        $div->setAttribute('class', $className);

        $div = $parentNode->appendChild($div);
        return $div;

    }

}
