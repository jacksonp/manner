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
use DOMText;
use Exception;
use Manner\Block\Template;
use Manner\Block\Text;
use Manner\Blocks;
use Manner\Roff;
use Manner\TextContent;

class URL implements Template
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
        $dom        = $parentNode->ownerDocument;
        $parentNode = Blocks::getParentForText($parentNode);

        Text::addSpace($parentNode);
        if (count($request['arguments']) === 0) {
            throw new Exception('Not enough arguments to .URL: ' . $request['raw_line']);
        }

        $url  = TextContent::interpretString($request['arguments'][0]);
        $href = Link::getValidHREF($url);
        if ($href) {
            $anchor = $dom->createElement('a');
            $anchor->setAttribute('href', $href);
            $parentNode->appendChild($anchor);
        } else {
            $anchor = $dom->createElement('span');
            $parentNode->appendChild($anchor);
        }

        $parentNode->appendChild($anchor);

        if (count($request['arguments']) > 1) {
            TextContent::interpretAndAppendText($anchor, $request['arguments'][1]);
        } elseif (count($lines)) {
            Roff::parse($anchor, $lines, true);
        }

        if ($anchor->textContent === '') {
            $anchor->appendChild(new DOMText($url));
        }

        if (count($request['arguments']) === 3) {
            TextContent::interpretAndAppendText($parentNode, $request['arguments'][2]);
        }

        return $parentNode;
    }

}
