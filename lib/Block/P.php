<?php

declare(strict_types=1);

namespace Manner\Block;

use DOMElement;
use Exception;
use Manner\Blocks;
use Manner\Man;
use Manner\Node;

class P implements Template
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

            $p = $parentNode->ownerDocument->createElement('p');
            /* @var DomElement $p */
            $p = $parentNode->appendChild($p);

            return $p;
        }
    }

}
