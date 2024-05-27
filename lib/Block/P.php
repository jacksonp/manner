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
        // This could improve slightly the output of readlink.1 but not worth it as doesn't change any other files,
        // and .HP has been deprecated.
//        if (
//          $request['request'] === 'HP'
//          && count($request['arguments']) === 0
//          && count($lines) > 2
//          && $lines[2] === '.TP'
//        ) {
//            $lines[0] = '.TP';
//            array_splice($lines, 2, 0, '[empty]');
//
//            return null;
//        }


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
