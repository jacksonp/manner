<?php
declare(strict_types=1);

class Block_IP implements Block_Template
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
        $man = Man::instance();

        $parentNode = Blocks::getBlockContainerParent($parentNode);

        if (count($request['arguments']) > 1) {
            $indentVal        = Roff_Unit::normalize($request['arguments'][1]);
            $man->indentation = $indentVal;
        } else {
            $indentVal = $man->indentation;
        }

        // 2nd bit: If there's a "designator" - otherwise preg_match hit empty double quotes.
        if (count($request['arguments']) > 0 && trim($request['arguments'][0]) !== '') {

            $dl = Block_DefinitionList::getParentDL($parentNode);

            /* @var DomElement $dd */
            $dt = $dom->createElement('dt');
            $dd = $dom->createElement('dd');

            // TODO: See about adding a check like $dl->lastChild->getAttribute('indent') <= $indentVal
            // And reducing indent if $indentVal is greater
            // And creating new $dl if $indentVal is less

            if (is_null($dl)) {
                $dl = $dom->createElement('dl');
                $dl = $parentNode->appendChild($dl);
            }

            TextContent::interpretAndAppendText($dt, $request['arguments'][0]);
            $dl->appendChild($dt);

            $dd->setAttribute('indent', $indentVal);
            $dd = $dl->appendChild($dd);

            return $dd;
        } else {
            /* @var DomElement $p */
            $p = $dom->createElement('p');
            if ($indentVal === '0') {
                // Resetting indentation, exit dd
                $parentNode = Blocks::getBlockContainerParent($parentNode, true);
            } else {
                if ($parentNode->tagName !== 'dd' || $parentNode->getAttribute('indent') !== $indentVal) {
                    $p->setAttribute('indent', $indentVal);
                }
            }
            $p = $parentNode->appendChild($p);
            return $p;
        }

    }

}
