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
use Manner\Man;
use Manner\Node;
use Manner\PreformattedOutput;
use Manner\TextContent;

class FontOneInputLine implements Template
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

        $man = Man::instance();

        $man->pushFont($request['request']);

        if (count($request['arguments']) === 0) {
            $man->addPostOutputCallback(
              function () use ($parentNode) {
                  Man::instance()->resetFonts();

                  return null;
              }
            );

            return null;
        } else {
            $parentNode = Blocks::getParentForText($parentNode);
            Text::addSpace($parentNode);
            TextContent::interpretAndAppendText($parentNode, implode(' ', $request['arguments']));
            if ($pre = Node::ancestor($parentNode, 'pre')) {
                PreformattedOutput::endInputLine($pre);
            }
            $man->resetFonts();

            return $parentNode;
        }
    }

}
