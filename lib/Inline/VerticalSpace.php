<?php

declare(strict_types=1);

namespace Manner\Inline;

use DOMElement;
use DOMException;
use Manner\Block\Template;
use Manner\Request;

class VerticalSpace implements Template
{

    /**
     * @throws DOMException
     */
    public static function addBR(DOMElement $parentNode): void
    {
        $prevBRs   = 0;
        $nodeCheck = $parentNode->lastChild;
        while ($nodeCheck) {
            if ($nodeCheck instanceof DOMElement && $nodeCheck->tagName === 'br') {
                ++$prevBRs;
            } else {
                break;
            }
            $nodeCheck = $nodeCheck->previousSibling;
        }
        if ($prevBRs < 2) {
            $parentNode->appendChild($parentNode->ownerDocument->createElement('br'));
        }
    }

    public static function check(string $string): bool
    {
        return in_array(Request::peepAt($string)['name'], ['br', 'sp', 'ne']);
    }

    /**
     * @throws DOMException
     */
    public static function checkAppend(
      DOMElement $parentNode,
      array &$lines,
      array $request,
      bool $needOneLineOnly = false
    ): ?DOMElement {
        array_shift($lines);

        /*if (count($request['arguments']) && $request['arguments'][0] === '-1') {
            if ($parentNode->lastChild instanceof DOMElement && $parentNode->lastChild->tagName === 'br') {
                $parentNode->removeChild($parentNode->lastChild);
            }
        } else*/
        if (
          !($parentNode->lastChild instanceof DOMElement) ||
          $parentNode->lastChild->tagName !== 'pre'
        ) {
            self::addBR($parentNode);
            if ($request['request'] !== 'br') {
                self::addBR($parentNode);
            }
        }

        return null;
    }

}
