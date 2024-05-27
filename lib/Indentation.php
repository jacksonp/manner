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

use DOMElement;
use Exception;

class Indentation
{

    // The default indentation is 7.2n in troff mode and 7n in nroff mode except for grohtml, which ignores indentation.
    // (https://www.mankier.com/7/groff_man#Miscellaneous)
    public const DEFAULT = '7';

    public static function isSet(DOMElement $p): bool
    {
        return $p->hasAttribute('indent');
    }

    public static function get(DOMElement $el): float
    {
        return (float)$el->getAttribute('indent');
    }

    public static function isSame(DOMElement $elA, DOMElement $elB): bool
    {
        return self::get($elA) === self::get($elB);
    }

    /**
     * @param DOMElement $el
     * @param $indentVal
     * @throws Exception
     */
    public static function set(DOMElement $el, $indentVal): void
    {
        if (!is_numeric($indentVal)) {
            throw new Exception('Non-numeric indent: ' . $indentVal);
        }
        $el->setAttribute('indent', (string)$indentVal);
    }

    public static function remove(DOMElement $el): void
    {
        $el->removeAttribute('indent');
    }

    /**
     * @param DOMElement $el
     * @param $indentVal
     * @throws Exception
     */
    public static function add(DOMElement $el, $indentVal): void
    {
        if (!is_numeric($indentVal)) {
            throw new Exception('Non-numeric indent: ' . $indentVal);
        }
        self::set($el, self::get($el) + $indentVal);
    }

    /**
     * @param DOMElement $el
     * @param $indentVal
     * @throws Exception
     */
    public static function subtract(DOMElement $el, $indentVal): void
    {
        if (!is_numeric($indentVal)) {
            throw new Exception('Non-numeric indent: ' . $indentVal);
        }
        self::set($el, self::get($el) - $indentVal);
    }

    /**
     * @param DOMElement $remainingNode
     * @param DOMElement $leavingNode
     * @throws Exception
     */
    public static function addElIndent(DOMElement $remainingNode, DOMElement $leavingNode): void
    {
        $remainingNodeIndent = self::get($remainingNode);
        $leavingNodeIndent   = self::get($leavingNode);

        if ($leavingNodeIndent) {
            if (!$remainingNodeIndent) {
                self::set($remainingNode, $leavingNodeIndent);
            } else {
                self::set($remainingNode, $remainingNodeIndent + $leavingNodeIndent);
            }
        }
    }

    /**
     * @param DOMElement $el
     * @throws Exception
     */
    public static function popOut(DOMElement $el): void
    {
        $elParent = $el->parentNode;

        // li: see cpupower-monitor.1
        if ($elParent->tagName === 'section' || $elParent->tagName === 'li') {
            return;
        }

        $parentIndent = Indentation::get($elParent);
        $inDD         = DOM::isTag($elParent, 'dd');

        if (
          $el !== $elParent->firstChild &&
          (($inDD && !$elParent->nextSibling) || !$el->nextSibling) &&
          $parentIndent !== .0 &&
          $parentIndent <= -Indentation::get($el)
        ) {
            Indentation::add($el, $parentIndent);

            if ($inDD) {
                $el = $elParent->parentNode->parentNode->insertBefore($el, $elParent->parentNode->nextSibling);
            } else {
                $el = $elParent->parentNode->insertBefore($el, $elParent->nextSibling);
            }
            /* @var DomElement $el */
            self::popOut($el);
        }
    }

}
