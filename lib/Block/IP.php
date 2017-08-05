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

            // TODO: only keep this one if indentation matches?
            $dl = Block_DefinitionList::getParentDL($parentNode);

            if (is_null($dl)) {
                $dl = $dom->createElement('dl');
                $dl = $parentNode->appendChild($dl);
                if ($indentVal) {
                    $dl->setAttribute('class', 'indent-' . $indentVal);
                }
            }

            $dt = $dom->createElement('dt');
            TextContent::interpretAndAppendText($dt, $request['arguments'][0]);
            $dl->appendChild($dt);

            /* @var DomElement $dd */
            $dd = $dom->createElement('dd');
            $dd = $dl->appendChild($dd);

            return $dd;
        } else {
            /* @var DomElement $p */
            $p = $dom->createElement('p');
            if (count($request['arguments']) > 1 && $request['arguments'][1] === '0') {
                // Resetting indentation, exit dd
                $parentNode = Blocks::getBlockContainerParent($parentNode, true);
            } else {
                if ($indentVal) {
                    $dl = Block_DefinitionList::getParentDL($parentNode);
                    if ($dl && $dl->getAttribute('class') === 'indent-' . $indentVal) {
                        $parentNode = $dl->lastChild;
                    } else {
                        $p->setAttribute('class', 'indent-' . $indentVal);
                    }
                }
            }
            $p = $parentNode->appendChild($p);
            return $p;
        }

    }

}
