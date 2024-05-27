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
use Manner\Block\Text;
use Manner\Blocks;
use Manner\Node;
use Manner\Replace;
use Manner\TextContent;

class Link implements Template
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

        $existingAnchor = Node::ancestor($parentNode, 'a');

        $dom = $parentNode->ownerDocument;

        if (is_null($existingAnchor)) {
            $parentNode = Blocks::getParentForText($parentNode);
        } else {
            $parentNode = $existingAnchor->parentNode;
        }

        $anchor = $dom->createElement('a');

        if (count($request['arguments'])) {
            $url  = $request['arguments'][0];
            $href = self::getValidHREF($url);
            if ($href) {
                $anchor->setAttribute('href', $href);
            }
        }

        Text::addSpace($parentNode);
        $parentNode->appendChild($anchor);

        return $anchor;
    }

    public static function getValidHREF(string $url): false|string
    {
        $url  = Replace::preg('~^<(.*)>$~u', '$1', $url);
        $href = TextContent::interpretString($url);
        if (filter_var($href, FILTER_VALIDATE_URL)) {
            return $href;
        } elseif (filter_var($href, FILTER_VALIDATE_EMAIL)) {
            list($user, $server) = explode('@', $href);

            return 'mailto:' . rawurlencode($user) . '@' . rawurlencode($server);
        } else {
            return false;
        }
    }

}
