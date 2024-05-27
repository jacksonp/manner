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
use Exception;
use Manner\Request;
use Manner\TextContent;

/**
 * Make tables out of tab-separated lines
 */
class TabTable implements Template
{

    public const skippableLines = ['.br', ''];

    // \&... see pmlogextract.1
    public const specialAcceptableLines = ['\\&...'];

    private static function isTabTableLine($line): bool
    {
        $line = trim($line);

        return
          mb_strpos($line, "\t") !== false ||
          in_array($line, self::skippableLines) ||
          in_array($line, self::specialAcceptableLines);
    }

    public static function lineContainsTab(string $line): bool
    {
        $line = ltrim($line, '\\&');

        // first char is NOT a tab + non-white-space before tab avoid indented stuff + exclude escaped tabs
        return mb_strpos($line, "\t") > 0 && preg_match('~[^\\\\\s]\t~u', $line);
    }

    public static function isStart(array $lines): bool
    {
        return
          count($lines) > 2 &&
          !is_null($lines[0]) && !is_null($lines[1]) &&
          !in_array(mb_substr($lines[0], 0, 1), ['.', '\'']) &&
          self::lineContainsTab($lines[0]) &&
          (
            self::lineContainsTab($lines[1]) ||
            in_array(trim($lines[1]), self::skippableLines + self::specialAcceptableLines)
          ) &&
          self::lineContainsTab($lines[2]);
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
        $dom = $parentNode->ownerDocument;

        if ($parentNode->tagName === 'p') {
            $parentNode = $parentNode->parentNode;
        }

        $table = $dom->createElement('table');
        $parentNode->appendChild($table);

        while ($nextRequest = Request::getLine($lines)) {
            if (!self::isTabTableLine($nextRequest['raw_line'])) {
                break;
            }

            array_shift($lines);

            if (in_array(trim($nextRequest['raw_line']), self::skippableLines)) {
                continue;
            }

            $tds = preg_split('~\t+~u', $nextRequest['raw_line']);
            $tr  = $table->appendChild($dom->createElement('tr'));
            foreach ($tds as $tdLine) {
                $cell = $dom->createElement('td');
                TextContent::interpretAndAppendText($cell, $tdLine);
                $tr->appendChild($cell);
            }
        }

        return $parentNode;
    }


}
