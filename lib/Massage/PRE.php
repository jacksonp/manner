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

namespace Manner\Massage;

use DOMElement;
use DOMText;
use Manner\Node;
use Manner\Text;

class PRE
{

    public static function tidy(DOMElement $el): void
    {
        while ($el->lastChild && Node::isTextAndEmpty($el->lastChild)) {
            $el->removeChild($el->lastChild);
        }

        if (!$el->lastChild) {
            $el->parentNode->removeChild($el);

            return;
        }

        if ($el->lastChild->nodeType === XML_TEXT_NODE) {
            $el->replaceChild(new DOMText(mb_rtrim($el->lastChild->textContent)), $el->lastChild);
        }

        if (Text::trimAndRemoveZWSUTF8($el->textContent) === '') {
            $el->parentNode->removeChild($el);
        }
    }

}
