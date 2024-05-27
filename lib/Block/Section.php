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
use Manner\Man;
use Manner\Node;
use Manner\Roff;
use Manner\TextContent;

class Section implements Template
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

        $dom = $parentNode->ownerDocument;

        $man = Man::instance();
        $man->resetIndentationToDefault();
        $man->resetFonts();

        $body    = Node::ancestor($parentNode, 'body');
        $section = $dom->createElement('section');
        /* @var DomElement $headingNode */
        if ($request['request'] === 'SH') {
            $section     = $body->appendChild($section);
            $headingNode = $dom->createElement('h2');
        } else {
            if ($body->lastChild && $body->lastChild->tagName === 'section') {
                $superSection = $body->lastChild;
            } else {
                // Make a new h2 level container section:
                $superSection = $body->appendChild($dom->createElement('section'));
            }
            $section     = $superSection->appendChild($section);
            $headingNode = $dom->createElement('h3');
        }

        /** @var DOMElement $headingNode */
        $headingNode = $section->appendChild($headingNode);

        if (count($request['arguments']) === 0) {
            $gotContent = Roff::parse($headingNode, $lines, true);
            if (!$gotContent) {
                $section->parentNode->removeChild($section);

                return null;
            }
        } else {
            $sectionHeading = implode(' ', $request['arguments']); // .SH "A B" sames as .SH A B
            TextContent::interpretAndAppendText($headingNode, $sectionHeading);
            if ($headingNode->lastChild) {
                // We don't want empty sections with &nbsp; as heading. See e.g. ntptime.8
                $headingNode->lastChild->textContent = rtrim(
                  $headingNode->lastChild->textContent,
                  " \t\n\r\0\x0B" . html_entity_decode('&nbsp;')
                );
            }
            // Skip sections with empty headings
            if (trim($headingNode->textContent) === '') {
                $section->parentNode->removeChild($section);

                return null;
            }
        }

        return $section;
    }

}
