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
use Manner\Block\Template;
use Manner\Node;

class LinkEnd implements Template
{

    public static function checkAppend(
      DOMElement $parentNode,
      array &$lines,
      array $request,
      bool $needOneLineOnly = false
    ): ?DOMElement {
        array_shift($lines);
        $anchorNode = Node::ancestor($parentNode, 'a');
        if (is_null($anchorNode)) {
            return null;
        }
        $parentNode  = $anchorNode->parentNode;
        $punctuation = trim($request['arg_string']);

        $removed = false;

        if ($anchorNode->getAttribute('href') === '') {
            $href = Link::getValidHREF($anchorNode->textContent);
            if ($href) {
                $anchorNode->setAttribute('href', $href);
            } else {
                Node::remove($anchorNode);
                $removed = true;
            }
        }

        if (!$removed) {
            if ($anchorNode->textContent === '') {
                $urlAsText = $anchorNode->getAttribute('href');
                $urlAsText = preg_replace('~^mailto:~', '', $urlAsText);
                $anchorNode->appendChild(new DOMText($urlAsText));
            }
        }

        if ($punctuation !== '') {
            $parentNode->appendChild(new DOMText($punctuation));
        }

        return $parentNode;
    }

}
