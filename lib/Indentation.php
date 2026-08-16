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
use Exception;

class Indentation
{

    // The default indentation is 7.2n in troff mode and 7n in nroff mode except for grohtml, which ignores indentation.
    // (https://www.mankier.com/7/groff_man#Miscellaneous)
    public const string DEFAULT = '7';

    public static function isSet(Element $p): bool
    {
        return $p->hasAttribute('indent');
    }

    public static function get(Element $el): float
    {
        return (float)$el->getAttribute('indent');
    }

    public static function isSame(Element $elA, Element $elB): bool
    {
        return self::get($elA) === self::get($elB);
    }

    /**
     * @param Element $el
     * @param $indentVal
     * @throws Exception
     */
    public static function set(Element $el, $indentVal): void
    {
        if (!is_numeric($indentVal)) {
            throw new Exception('Non-numeric indent: ' . $indentVal);
        }
        $el->setAttribute('indent', (string)$indentVal);
    }

    public static function remove(Element $el): void
    {
        $el->removeAttribute('indent');
    }

    /**
     * @param Element $el
     * @param $indentVal
     * @throws Exception
     */
    public static function add(Element $el, $indentVal): void
    {
        if (!is_numeric($indentVal)) {
            throw new Exception('Non-numeric indent: ' . $indentVal);
        }
        self::set($el, self::get($el) + $indentVal);
    }

    /**
     * @param Element $el
     * @param $indentVal
     * @throws Exception
     */
    public static function subtract(Element $el, $indentVal): void
    {
        if (!is_numeric($indentVal)) {
            throw new Exception('Non-numeric indent: ' . $indentVal);
        }
        self::set($el, self::get($el) - $indentVal);
    }

    /**
     * @param Element $remainingNode
     * @param Element $leavingNode
     * @throws Exception
     */
    public static function addElIndent(Element $remainingNode, Element $leavingNode): void
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
     * @param Element $el
     * @throws Exception
     */
    public static function popOut(Element $el): void
    {
        $elParent = $el->parentNode;

        // li: see cpupower-monitor.1
        if ($elParent->localName === 'section' || $elParent->localName === 'li') {
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
            /* @var Element $el */
            self::popOut($el);
        }
    }

}
