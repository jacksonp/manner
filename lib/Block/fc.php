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

use DOMDocument;
use DOMElement;
use DOMException;
use Exception;
use Manner\Blocks;
use Manner\Request;
use Manner\TextContent;

class fc implements Template
{

    /**
     * @throws DOMException
     */
    private static function addRow(DOMDocument $dom, DOMElement $table, array $cells): void
    {
        $tr = $dom->createElement('tr');
        foreach ($cells as $contents) {
            $td = $dom->createElement('td');
            TextContent::interpretAndAppendText($td, $contents);
            $tr->appendChild($td);
        }
        $table->appendChild($tr);
    }

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

        $parentNode = Blocks::getBlockContainerParent($parentNode);

        $delim = $request['arguments'][0];
        $pad   = @$request['arguments'][1] ?: ' ';

        $dom = $parentNode->ownerDocument;

        $table = $dom->createElement('table');

        // We don't want to handle the lines at this stage as a fresh call to .fc call a new \Roff\fc, so don't iterate
        // with Request::getLine()
        while (count($lines)) {
            // Don't process next line yet, could be new .fc
            $requestDetails = Request::peepAt($lines[0]);

            if (
              $requestDetails['name'] === 'fi' ||
              ($requestDetails['name'] === 'fc' && $requestDetails['raw_arg_string'] === '')
            ) {
                array_shift($lines);
                break; // Finished
            }

            $nextRequest = Request::getLine($lines);
            array_shift($lines);

            if (in_array($nextRequest['request'], ['ta', 'nf', 'br', 'LP'])) {
                continue; // Ignore
            } elseif (mb_strpos($nextRequest['raw_line'], $delim) === 0) {
                $cells = preg_split('~' . preg_quote($delim, '~') . '~u', $nextRequest['raw_line']);
                array_shift($cells);
                $cells = array_map(
                  function ($contents) use ($pad) {
                      return trim($contents, $pad);
                  },
                  $cells
                );
                self::addRow($dom, $table, $cells);
            } else {
                $cells = preg_split("~\t~u", $nextRequest['raw_line']);
                self::addRow($dom, $table, $cells);
            }
        }

        $parentNode->appendChild($table);

        return $parentNode;
    }

}
