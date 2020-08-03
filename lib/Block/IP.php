<?php

declare(strict_types=1);

namespace Manner\Block;

use DOMElement;
use Exception;
use Manner\Blocks;
use Manner\Indentation;
use Manner\Man;
use Manner\Roff;
use Manner\TextContent;

class IP implements Template
{

    /**
     * @param DOMElement $parentNode
     * @param array $lines
     * @param array $request
     * @param bool $needOneLineOnly
     * @return DOMElement|null
     * @throws Exception
     */
    public static function checkAppend(
      DOMElement $parentNode,
      array &$lines,
      array $request,
      $needOneLineOnly = false
    ): ?DOMElement {
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
            $indentVal = Roff\Unit::normalize($request['arguments'][1], 'n', 'n');
            if (is_numeric($indentVal)) {
                $man->indentation = $indentVal;
            } else {
                $indentVal = $man->indentation;
            }
        } else {
            $indentVal = $man->indentation;
        }

        // Could have hit double quotes, null, \& (zero-width space)
        $rawDesignator = array_shift($request['arguments']);
        $designator = TextContent::interpretString($rawDesignator);
        $designator = \Manner\Text::trimAndRemoveZWSUTF8($designator);

        if ($designator !== '') {
            $dl = DefinitionList::getParentDL($parentNode);

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

            TextContent::interpretAndAppendText($dt, $rawDesignator);
            $dl->appendChild($dt);

            $man->resetFonts();

            Indentation::set($dd, $indentVal);
            $dd = $dl->appendChild($dd);

            return $dd;
        } else {
            $man->resetFonts();


            $div = $dom->createElement('div');
            $div->setAttribute('remap', 'IP');
            if (!$indentVal) {
                // Resetting indentation, exit dd
                $parentNode = Blocks::getBlockContainerParent($parentNode, true);
            } elseif ($parentNode->tagName !== 'dd' || Indentation::get($parentNode) !== (float)$indentVal) {
                Indentation::set($div, $indentVal);
            }
            /* @var DomElement $div */
            $div = $parentNode->appendChild($div);

            return $div;
        }
    }

}
