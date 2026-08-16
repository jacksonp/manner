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

use Dom\Element;
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
     * @param Element $parentNode
     * @param array $lines
     * @param array $request
     * @param bool $needOneLineOnly
     * @return Element|null
     * @throws Exception
     */
    public static function checkAppend(
      Element $parentNode,
      array &$lines,
      array $request,
      bool $needOneLineOnly = false
    ): ?Element {
        array_shift($lines);

        $indentVal = 0.0;
        if (count($request['arguments'])) {
            $indentVal = Unit::normalize($request['arguments'][0], 'm', 'n');
        }

        if (Indentation::get($parentNode) === (float)$indentVal && $parentNode->lastChild) {
            if (!($parentNode->lastChild instanceof Element) || $parentNode->lastChild->localName !== 'br') {
                VerticalSpace::addBR($parentNode);
            }

            return $parentNode;
        }

        $dt = Node::ancestor($parentNode, 'dt');
        if (is_null($dt)) {
            $parentNode = Blocks::getBlockContainerParent($parentNode);
            $p          = $parentNode->ownerDocument->createElement('p');
            /* @var Element $p */
            $p = $parentNode->appendChild($p);
            Indentation::set($p, $indentVal);

            return $p;
        } else {
            Indentation::set($dt, $indentVal);

            return null;
        }
    }

}
