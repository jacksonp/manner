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

use Dom\Element;
use Throwable;
use Dom\XPath;
use Exception;
use Manner\DOM;
use Manner\Indentation;
use Manner\Node;

class DIV
{

    /**
     * @throws Exception
     */
    public static function removeDIVsWithSingleChild(XPath $xpath): void
    {
        $divs = $xpath->query('//h:div');
        foreach ($divs as $div) {
            // TODO add other blocks below? see Dom handling line 106 or so.
            if (DOM::isTag($div->firstChild, ['pre'])) { // 'dl',
                self::removeDIVWithSingleChild($div);
            }
        }
    }

    /**
     * @throws Exception
     */
    public static function removeDIVWithSingleChild(Element $div): void
    {
        if ($div->childNodes->length === 1) {
            Indentation::addElIndent($div->firstChild, $div);
            Node::remove($div);
        }
    }

    public static function getNextNonBRNode(\Dom\Node $element, bool $removeBRs = false): ?\Dom\Node
    {
        $nextSibling = $element->nextSibling;
        while (DOM::isTag($nextSibling, 'br')) {
            $nextSibling = $nextSibling->nextSibling;
            if ($removeBRs) {
                $element->parentNode->removeChild($element->nextSibling);
            }
        }

        return $nextSibling;
    }

    private static function isPotentialLI(?Element $div): bool
    {
        return
          DOM::isTag($div, 'div') &&
          !DOM::isTag($div->firstChild, ['pre', 'ul']) &&
          HTMLList::startsWithBullet($div->textContent);
    }

    /**
     * @throws Throwable
     */
    public static function postProcess(Element $div): ?\Dom\Node
    {
        $doc = $div->ownerDocument;

        /* @var Element $nextNonBR */

        if (self::isPotentialLI($div)) {
            $nextNonBR = self::getNextNonBRNode($div);

            if (
              (is_null($nextNonBR) || !DOM::isTag($nextNonBR, 'div') || !self::isPotentialLI($nextNonBR)) &&
              DOM::isTag($div->firstChild, 'p') &&
              HTMLList::checkElementForLIs($div->firstChild)
            ) {
                $ul = $doc->createElement('ul');
                $ul = $div->parentNode->insertBefore($ul, $div);

                while (DOM::isTag($div->firstChild, 'li')) {
                    $ul->appendChild($div->firstChild);
                }

                /* @var Element $ul */
                /* @var Element $li */
                $li = $ul->appendChild($doc->createElement('li'));

                DOM::extractContents($li, $div);

                $div->parentNode->removeChild($div);

                HTMLList::pruneBulletChar($ul->firstChild);

                return $ul->nextSibling;
            } elseif (is_null($nextNonBR) || (DOM::isTag($nextNonBR, 'div') && self::isPotentialLI($nextNonBR))) {
                $ul = $doc->createElement('ul');
                $ul = $div->parentNode->insertBefore($ul, $div);

                while (self::isPotentialLI($div)) {
                    /* @var Element $li */
                    $li = $ul->appendChild($doc->createElement('li'));

                    if ($div->childNodes->length === 1 && $div->firstChild->localName === 'p') {
                        DOM::extractContents($li, $div->firstChild);
                    } else {
                        DOM::extractContents($li, $div);
                    }

                    HTMLList::pruneBulletChar($li);

                    HTMLList::checkElementForLIs($li);

                    $div->parentNode->removeChild($div);
                    $div = self::getNextNonBRNode($ul, true);
                }

                return $ul->nextSibling;
            }
        }

        return $div->nextSibling;
    }

}
