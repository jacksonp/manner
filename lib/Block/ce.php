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
      bool $needOneLineOnly = false
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
