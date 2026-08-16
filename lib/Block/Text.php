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

use Dom\Element;
use Throwable;
use Exception;
use Manner\Blocks;
use Manner\Man;
use Manner\Node;
use Manner\Replace;
use Manner\Request;
use Manner\TextContent;

class Text implements Template
{

    public static bool $interruptTextProcessing = false;

    public static function addSpace(Element $parentNode): void
    {
        if (
          !Node::isOrInTag($parentNode, 'pre') && Node::hasContent($parentNode) &&
          (
          !($parentNode->lastChild instanceof Element) ||
            in_array($parentNode->lastChild->localName, Blocks::INLINE_ELEMENTS)
          ) &&
          !TextContent::$interruptTextProcessing
        ) {
            $parentNode->appendChild($parentNode->ownerDocument->createTextNode(' '));
        }
    }

    /**
     * @param Element $parentNode
     * @param array $lines
     * @param array $request
     * @param bool $needOneLineOnly
     * @return Element|null
     * @throws Exception|Throwable
     */
    public static function checkAppend(
      Element $parentNode,
      array &$lines,
      array $request,
      bool $needOneLineOnly = false
    ): ?Element {
        $parentNode = Blocks::getParentForText($parentNode);

        if (Man::instance()->hasPostOutputCallbacks()) {
            $needOneLineOnly = true;
        }

        // Reset
        self::$interruptTextProcessing = false;

        $line = self::removeTextProcessingInterrupt($request['raw_line']);

        array_shift($lines);

        // Implicit line break: "A line that begins with a space causes a break and the space is output at the beginning of the next line. Note that this space isn't adjusted, even in fill mode."
        $implicitBreak = mb_substr($line, 0, 1) === ' ';

        while (count($lines) && !self::$interruptTextProcessing && !$needOneLineOnly) {
            if (!is_null(Request::peepAt($lines[0])['name'])) {
                break;
            }
            $nextRequest = Request::getLine($lines); // process line...
            if (is_null($nextRequest)) {
                break;
            }
            $nextRequestClass = Request::getClass($nextRequest, $lines);
            if ($nextRequestClass !== '\Manner\Block\Text' || mb_substr($lines[0], 0, 1) === ' ') {
                break; // Stop on non-text or implicit line break.
            }
            array_shift($lines);
            $line .= ' ' . self::removeTextProcessingInterrupt($nextRequest['raw_line']);
        }

        // Re-add interrupt if present to last line for TextContent::interpretAndAppendText:
        if (self::$interruptTextProcessing) {
            $line .= '\\c';
        }

        self::addLine($parentNode, $line, $implicitBreak);

        return $parentNode;
    }

    private static function removeTextProcessingInterrupt(string $line): string
    {
        $line                          = Replace::preg('~\\\\c\s*$~', '', $line, -1, $replacements);
        self::$interruptTextProcessing = $replacements > 0;

        return $line;
    }

    /**
     * @throws Throwable
     */
    public static function addLine(Element $parentNode, string $line, bool $prefixBR = false): void
    {
        if ($prefixBR) {
            self::addImplicitBreak($parentNode);
        }

        self::addSpace($parentNode);

        TextContent::interpretAndAppendText($parentNode, $line);
    }

    /**
     * @throws Throwable
     */
    private static function addImplicitBreak(Element $parentNode): void
    {
        if (
          $parentNode->hasChildNodes() &&
          (!($parentNode->lastChild instanceof Element) || $parentNode->lastChild->localName !== 'br')
        ) {
            $parentNode->appendChild($parentNode->ownerDocument->createElement('br'));
        }
    }

}
