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

namespace Manner;

use Dom\Element;
use Throwable;
use Exception;

class Blocks
{

    public const array BLOCK_ELEMENTS = [
      'p',
      'pre',
      'div',
      'dl',
      'ol',
      'ul',
      'table',
    ];

    public const array INLINE_ELEMENTS = [
      'a',
      'em',
      'strong',
      'small',
      'code',
      'span',
      'sub',
      'sup',
    ];

    /**
     * @throws Throwable
     */
    public static function getParentForText(Element $parentNode): Element
    {
        if (in_array($parentNode->localName, ['body', 'section', 'div', 'dd'])) {
            $p = $parentNode->ownerDocument->createElement('p');
            $p->setAttribute('implicit', '1');
            $parentNode = $parentNode->appendChild($p);
        }

        return $parentNode;
    }

    /**
     * @param Element $parentNode
     * @param bool $superOnly
     * @param bool $ipOK
     * @return Element
     * @throws Exception
     */
    public static function getBlockContainerParent(
      Element $parentNode,
      bool $superOnly = false,
      bool $ipOK = false
    ): Element {
        $blockTags = ['body', 'div', 'section', 'pre'];
        if (!$superOnly) {
            $blockTags[] = 'dt';
            $blockTags[] = 'dd';
            $blockTags[] = 'th';
            $blockTags[] = 'td';
        }

        // We use <div>s to "remap" .IP temporarily so it can contain other <div>s, so treat remaps as non-blocks in
        // some cases.
        while (!in_array($parentNode->localName, $blockTags) || (!$ipOK && $parentNode->hasAttribute('remap'))) {
            $parentNode = $parentNode->parentNode;
            if (!$parentNode) {
                throw new Exception('No more parents.');
            }
        }

        return $parentNode;
    }

}
