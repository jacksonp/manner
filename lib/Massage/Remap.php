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
use DOMXPath;
use Exception;
use Manner\Blocks;
use Manner\DOM;
use Manner\Indentation;

class Remap
{

    /**
     * @param DOMXPath $xpath
     * @throws Exception
     */
    public static function doAll(DOMXPath $xpath): void
    {
        $divs = $xpath->query('//div[@remap]');
        /** @var DOMElement $div */
        /** @var DOMElement $p */
        foreach ($divs as $div) {
            if ($div->getAttribute('remap') === 'IP') {
                $indentVal = Indentation::get($div);

                $remapChild = $div->firstChild;
                if ($remapChild) {
                    $next = false;
                    do {
                        if (DOM::isTag($remapChild, BLOCKS::BLOCK_ELEMENTS)) {
                            $next = $remapChild->nextSibling;
                            $remapChild->removeAttribute('implicit');
                            $indentVal && Indentation::add($remapChild, $indentVal);
                            $div->parentNode->insertBefore($remapChild, $div);
                        } else {
                            $p = $div->ownerDocument->createElement('p');
                            $p = $div->parentNode->insertBefore($p, $div);
                            $indentVal && Indentation::add($p, $indentVal);
                            while ($remapChild && !DOM::isTag($remapChild, BLOCKS::BLOCK_ELEMENTS)) {
                                $next = $remapChild->nextSibling;
                                $p->appendChild($remapChild);
                                $remapChild = $next;
                            }
                        }
                    } while ($remapChild = $next);
                }

                $div->parentNode->removeChild($div);
            } else {
                throw new Exception('Unexpected value for remap.');
            }
        }
    }
}
