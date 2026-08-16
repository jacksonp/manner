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

use Dom\Element;
use Exception;
use Manner\Block\Template;
use Manner\Blocks;
use Manner\TextContent;

/**
 * See https://www.mankier.com/7/groff_man#Macros_to_Describe_Command_Synopses
 */
class OP implements Template
{

    /**
     * @param Element $parentNode
     * @param array $lines
     * @param array $request
     * @param bool $needOneLineOnly
     * @return Element|null
     * @throws Exception
     */
    public static function checkAppend(
      Element $parentNode,
      array &$lines,
      array $request,
      bool $needOneLineOnly = false
    ): ?Element {
        array_shift($lines);
        $dom        = $parentNode->ownerDocument;
        $parentNode = Blocks::getParentForText($parentNode);

        // Used to prevent line-breaks inside options:
        $optSpan = $parentNode->appendChild($dom->createElement('span'));
        $optSpan->setAttribute('class', 'opt');

        $optSpan->appendChild($parentNode->ownerDocument->createTextNode('['));
        /* @var Element $strong */
        $strong = $optSpan->appendChild($dom->createElement('strong'));
        TextContent::interpretAndAppendText($strong, $request['arguments'][0]);
        if (count($request['arguments']) > 1) {
            $optSpan->appendChild($parentNode->ownerDocument->createTextNode(' '));
            /* @var Element $em */
            $em = $optSpan->appendChild($dom->createElement('em'));
            TextContent::interpretAndAppendText($em, $request['arguments'][1]);
        }
        $optSpan->appendChild($parentNode->ownerDocument->createTextNode('] '));

        return $parentNode;
    }

}
