<?php
declare(strict_types = 1);

class Block_TP implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        if (count($lines) > 1 && $lines[1] === '.nf') {
            // Switch .TP and .nf around, and try again. See e.g. elasticdump.1
            $lines[1] = $lines[0];
            $lines[0] = '.nf';
            return null;
        }

        array_shift($lines);

        $dom = $parentNode->ownerDocument;

        $blockContainerParentNode = Blocks::getBlockContainerParent($parentNode);

        $indentVal = null;
        if (
            count($request['arguments']) &&
            $normalizedVal = Roff_Unit::normalize($request['arguments'][0]) // note this filters out 0s
        ) {
            $indentVal = $normalizedVal;
        }

        $dl = Block_DefinitionList::getParentDL($blockContainerParentNode);

        if (is_null($dl)) {
            $dl = $dom->createElement('dl');
            $dl = $blockContainerParentNode->appendChild($dl);
            if ($indentVal) {
                $dl->setAttribute('class', 'indent-' . $indentVal);
            }
        }

        $dt         = $dom->createElement('dt');
        $dt         = $dl->appendChild($dt);
        $gotContent = Roff::parse($dt, $lines, true);
        if (!$gotContent) {
            $dl->removeChild($dt);
            return null;
        }

        while (count($lines)) {
            $request = Request::getLine($lines);
            if ($request['request'] === 'TQ') {
                array_shift($lines);
                $dt = $dom->createElement('dt');
                $dl->appendChild($dt);
                $gotContent = Roff::parse($dt, $lines, true);
                if (!$gotContent) {
                    $dl->removeChild($dt);
                }
            } else {
                break;
            }
        }

        $dd = $dom->createElement('dd');
        $dd = $dl->appendChild($dd);

        return $dd;

    }

}
