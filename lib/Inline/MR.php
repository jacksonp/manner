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
use DOMText;
use Manner\Block\Template;
use Manner\Block\Text;
use Manner\Blocks;
use Manner\TextContent;

/**
 * https://www.mankier.com/7/groff_man#Description-Hyperlink_macros
 */
class MR implements Template
{

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

        $dom = $parentNode->ownerDocument;

        $parentNode = Blocks::getParentForText($parentNode);

        $anchor = $dom->createElement('a');
        Text::addSpace($parentNode);
        $parentNode->appendChild($anchor);

        $topic         = TextContent::interpretString($request['arguments'][0]);
        $manualSection = TextContent::interpretString($request['arguments'][1]);

        $anchor->appendChild(new DOMText($topic . '(' . $manualSection . ')'));
        $anchor->setAttribute('href', '/' . $manualSection . '/' . $topic);

        // Trailing text (usually punctuation)
        if (count($request['arguments']) > 2) {
            TextContent::interpretAndAppendText($parentNode, $request['arguments'][2]);
        }


        return $parentNode;
    }

}
