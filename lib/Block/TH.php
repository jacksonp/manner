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

namespace Manner\Block;

use DOMElement;
use DOMText;
use Exception;
use Manner\Man;
use Manner\Node;
use Manner\Replace;
use Manner\TextContent;

class TH implements Template
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

        $man = Man::instance();

        $body = Node::ancestor($parentNode, 'body');

        if (empty($man->title)) {
            if (count($request['arguments']) < 1) {
                throw new Exception($request['raw_line'] . ' - missing title info');
            }

            foreach ($request['arguments'] as $k => $v) {
                // See amor.6 for \FB \FR nonsense.
                $value = Replace::preg('~\\\\F[BR]~', '', $v);
                $value = TextContent::interpretString($value);
                // Fix vnu's "Saw U+0000 in stream" e.g. in lvmsadc.8:
                $value                    = mb_trim($value);
                $request['arguments'][$k] = $value;
            }

            $man->title = $request['arguments'][0];
            if (count($request['arguments']) > 1) {
                $man->section = $request['arguments'][1];
                $man->extra1  = @$request['arguments'][2] ?: '';
                $man->extra2  = @$request['arguments'][3] ?: '';
                $man->extra3  = @$request['arguments'][4] ?: '';
            }

            $h1 = $body->ownerDocument->createElement('h1');
            $h1->appendChild(new DOMText($man->title));
            $body->appendChild($h1);
        } elseif (count($request['arguments'])) {
            // Some pages  have multiple .THs for different commands in one page, just had a horizontal line when we hit
            // .THs with content after the first
            $hr = $body->ownerDocument->createElement('hr');
            $body->appendChild($hr);
        }

        return $body;
    }

}
