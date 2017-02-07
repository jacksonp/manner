<?php
declare(strict_types = 1);

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

        $parentNode = Blocks::getBlockContainerParent($parentNode);

        // TODO $arguments will contain the indentation level, try to use this to handle nested dls?

        // 2nd bit: If there's a "designator" - otherwise preg_match hit empty double quotes.
        if (count($request['arguments']) > 0 && trim($request['arguments'][0]) !== '') {

            $indentVal = null;
            if (
                count($request['arguments']) > 1 &&
                $normalizedVal = Roff_Unit::normalize($request['arguments'][1]) // note this filters out 0s
            ) {
                $indentVal = $normalizedVal;
            }

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
                if ($parentNode->tagName !== 'dd') {
                    $p->setAttribute('class', 'indent');
                }
            }
            $p = $parentNode->appendChild($p);
            return $p;
        }

    }

}
