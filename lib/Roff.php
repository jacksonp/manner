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

class Roff
{

    /**
     * @param DOMElement $parentNode
     * @param array $lines
     * @param bool $stopOnContent
     * @return bool
     * @throws Exception
     */
    public static function parse(
      DOMElement $parentNode,
      array &$lines,
      bool $stopOnContent = false
    ): bool {
        while ($request = Request::getLine($lines)) {
            if ($stopOnContent) {
                // \c: Interrupt text processing (groff.7)
                if (in_array($request['raw_line'], ['\\c'])) {
                    array_shift($lines);

//                    Man::instance()->runPostOutputCallbacks();
                    return true;
                }

                if (in_array($request['request'], ['SH', 'SS', 'TP', 'br', 'sp', 'ne', 'PP', 'RS', 'RE', 'P', 'LP'])) {
                    return false;
                }

                if ($request['raw_line'] === '') {
                    array_shift($lines);
                    continue;
                }
            }

            $request['class'] = Request::getClass($request, $lines);

            if ($newParent = PreformattedOutput::handle($parentNode, $lines, $request)) {
                // NB: still need $stopOnContent check below (so no continue)
                if ($newParent instanceof DOMElement) {
                    $parentNode = $newParent;
                }
            } else {
                /** @var Block\Template $class */
                $class     = $request['class'];
                $newParent = $class::checkAppend($parentNode, $lines, $request, $stopOnContent);
                if (!is_null($newParent)) {
                    $parentNode = $newParent;
                }
            }

            if ($request['class'] === '\Manner\Block\Text' || $parentNode->textContent !== '') {
                if ($stopOnContent) {
                    return true;
                }
                if ($request['class'] !== '\Manner\Inline\FontOneInputLine') { // TODO: hack? fix?
                    $newParent = Man::instance()->runPostOutputCallbacks();
                    if (!is_null($newParent)) {
                        $parentNode = $newParent;
                    }
                }
            }
        }

        return !$stopOnContent;
    }

}
