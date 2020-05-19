<?php

declare(strict_types=1);

namespace Manner\Block;

use DOMElement;
use Exception;
use Manner\Blocks;
use Manner\Request;
use Manner\Roff;

class ce implements Template
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
        array_shift($lines);
        $parentNode = Blocks::getBlockContainerParent($parentNode);
        $dom        = $parentNode->ownerDocument;
        $block      = $dom->createElement('p');
        $block->setAttribute('class', 'center');
        $parentNode->appendChild($block);

        $numLinesToCenter = count($request['arguments']) === 0 ? 1 : (int)$request['arguments'][0];
        $centerLinesUpTo  = min($numLinesToCenter, count($lines));
        for ($i = 0; $i < $centerLinesUpTo && count($lines); ++$i) {
            if (Request::getLine($lines)['request'] === 'ce') {
                break;
            }
            Roff::parse($block, $lines, true);
            $block->appendChild($dom->createElement('br'));
        }

        return $parentNode;
    }

}
