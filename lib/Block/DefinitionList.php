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

class DefinitionList
{

    public static function getParentDL(DOMElement $parentNode): ?DOMElement
    {
        do {
            $tag = $parentNode->tagName;
            if ($tag === 'dl') {
                return $parentNode;
            }
            if (in_array($tag, ['body']) || ($tag === 'div' && !$parentNode->hasAttribute('remap'))) {
                return null;
            }
        } while ($parentNode = $parentNode->parentNode);

        return null;
    }

}
