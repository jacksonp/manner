<?php
declare(strict_types=1);

class Block_IP implements Block_Template
{

    static function checkAppend(
        DOMElement $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        $man = Man::instance();

        if ($needOneLineOnly && $parentNode->tagName === 'dt') { // See e.g. links2.1
            if (count($request['arguments'])) {
                $lines[0] = $request['arguments'][0];
            } else {
                array_shift($lines);
            }
            $man->resetFonts();
            return null;
        }

        array_shift($lines);

        $dom = $parentNode->ownerDocument;

        $parentNode = Blocks::getBlockContainerParent($parentNode);

        if (count($request['arguments']) > 1) {
            $indentVal        = Roff_Unit::normalize($request['arguments'][1], 'n', 'n');
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

            $man->resetFonts();

            Indentation::set($dd, $indentVal);
            $dd = $dl->appendChild($dd);

            return $dd;
        } else {
            $man->resetFonts();

            /* @var DomElement $div */
            $div = $dom->createElement('div');
            $div->setAttribute('remap', 'IP');
            if (!$indentVal) {
                // Resetting indentation, exit dd
                $parentNode = Blocks::getBlockContainerParent($parentNode, true);
            } else {
                if ($parentNode->tagName !== 'dd' || Indentation::get($parentNode) !== (float)$indentVal) {
                    Indentation::set($div, $indentVal);
                }
            }
            $div = $parentNode->appendChild($div);
            return $div;
        }

    }

}
