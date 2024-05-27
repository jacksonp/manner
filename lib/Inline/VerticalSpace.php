<?php

/**
 * manner: convert troff man pages to semantic HTML
 * Copyright (C) 2024  Jackson Pauls
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
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
