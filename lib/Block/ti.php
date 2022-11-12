<?php

declare(strict_types=1);

namespace Manner\Block;

use DOMElement;
use Exception;
use Manner\Blocks;
use Manner\Indentation;
use Manner\Inline\VerticalSpace;
use Manner\Node;
use Manner\Roff\Unit;

/**
 * .ti ±N: Temporary indent next line (default scaling indicator m).
 */
class ti implements Template
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
      bool $needOneLineOnly = false
    ): ?DOMElement {
        array_shift($lines);

        $indentVal = 0.0;
        if (count($request['arguments'])) {
            $indentVal = Unit::normalize($request['arguments'][0], 'm', 'n');
        }

        if (Indentation::get($parentNode) === (float)$indentVal && $parentNode->lastChild) {
            if ($parentNode->lastChild->nodeType !== XML_ELEMENT_NODE || $parentNode->lastChild->tagName !== 'br') {
                VerticalSpace::addBR($parentNode);
            }

            return $parentNode;
        }

        $dt = Node::ancestor($parentNode, 'dt');
        if (is_null($dt)) {
            $parentNode = Blocks::getBlockContainerParent($parentNode);
            $p          = $parentNode->ownerDocument->createElement('p');
            /* @var DomElement $p */
            $p = $parentNode->appendChild($p);
            Indentation::set($p, $indentVal);

            return $p;
        } else {
            Indentation::set($dt, $indentVal);

            return null;
        }
    }

}
