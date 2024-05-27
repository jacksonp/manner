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
use Exception;
use Manner\Block\Template;
use Manner\Block\Text;
use Manner\Blocks;
use Manner\Man;
use Manner\Request;
use Manner\TextContent;

class AlternatingFont implements Template
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
        $parentNode = Blocks::getParentForText($parentNode);
        $man        = Man::instance();
        Text::addSpace($parentNode);

        foreach ($request['arguments'] as $bi => $bit) {
            $requestCharIndex = $bi % 2;
            if (!isset($request['request'][$requestCharIndex])) {
                throw new Exception(
                  $lines[0] . ' command ' . $request['request'] . ' has nothing at index ' . $requestCharIndex
                );
            }
            // Re-massage the line:
            // in a man page the AlternatingFont macro argument would become the macro argument to a .ft call and have
            // double backslashes transformed twice (I think)
            $bit = Request::massageLine($bit);
            $man->pushFont($request['request'][$requestCharIndex]);
            TextContent::interpretAndAppendText($parentNode, $bit);
            $man->resetFonts();
        }

        return $parentNode;
    }

}
