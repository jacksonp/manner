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
use DOMException;
use Manner\DOM;
use Manner\Node;

class DT
{

    /**
     * @throws DOMException
     */
    public static function postProcess(DOMElement $dt): void
    {
        $child = $dt->lastChild;
        while ($child) {
            if (DOM::isTag($child, 'br')) {
                $newDT = $dt->ownerDocument->createElement('dt');
                $newDT = $dt->parentNode->insertBefore($newDT, $dt->nextSibling);

                $nextChild = $child->nextSibling;
                $dt->removeChild($child);
                while ($nextChild) {
                    $sib = $nextChild->nextSibling;
                    $newDT->appendChild($nextChild);
                    $nextChild = $sib;
                }
                self::postProcess($dt);
            }
            $child = $child->previousSibling;
        }
    }

    public static function tidy(DOMElement $dt): void
    {
        while ($dt->lastChild && (Node::isTextAndEmpty($dt->lastChild) || DOM::isTag($dt->lastChild, 'br'))) {
            $dt->removeChild($dt->lastChild);
        }

        if (trim($dt->textContent) === '') {
            $dt->parentNode->removeChild($dt);
        }

        if (Dom::isTag($dt->firstChild, 'pre')) {
            // <pre>s can't go inside <dt>s (tho we put them there for convenience now).
            // TODO: remove this once we handle .nf and .EX by setting flag rather than creating <pre> element.
            Node::remove($dt->firstChild);
            self::tidy($dt);
        }
    }

}
