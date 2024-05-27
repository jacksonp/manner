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
use Manner\Blocks;
use Manner\Indentation;
use Manner\Man;
use Manner\Request;
use Manner\Roff;
use Manner\Roff\Unit;
use Manner\TextContent;

/**
 * .de1 TP
 * .  sp \\n[PD]u
 * .  if \\n[.$] .nr an-prevailing-indent (n;\\$1)
 * .  it 1 an-trap
 * .  in 0
 * .  if !\\n[an-div?] \{\
 * .    ll -\\n[an-margin]u
 * .    di an-div
 * .  \}
 * .  nr an-div? 1
 * ..
 *
 * .\" Continuation line for .TP header.
 * .de TQ
 * .  br
 * .  ns
 * .  TP \\$1\" no doublequotes around argument!
 * ..
 *
 */
class TP implements Template
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
        if (count($lines) > 1 && $lines[1] === '.nf') {
            // Switch .TP and .nf around, and try again. See e.g. elasticdump.1
            $lines[1] = $lines[0];
            $lines[0] = '.nf';

            return null;
        }

        array_shift($lines);

        if (count($lines) && $lines[0] === '\\&') {
            if (count($request['arguments'])) {
                $lines[0] = '.IP "" ' . $request['arguments'][0];
            } else {
                $lines[0] = '.IP';
            }

            return null;
        }

        $dom = $parentNode->ownerDocument;
        $man = Man::instance();

        $blockContainerParentNode = Blocks::getBlockContainerParent($parentNode);

        if (count($request['arguments'])) {
            $indentVal = Unit::normalize($request['arguments'][0], 'n', 'n');
            if (is_numeric($indentVal)) {
                $man->indentation = $indentVal;
            } else {
                $indentVal = $man->indentation;
            }
        } else {
            $indentVal = $man->indentation;
        }

        $dl = DefinitionList::getParentDL($blockContainerParentNode);

        if (is_null($dl)) {
            $dl = $dom->createElement('dl');
            $dl = $blockContainerParentNode->appendChild($dl);
        }

        $dt = $dom->createElement('dt');
        /* @var DomElement $dt */
        $dt         = $dl->appendChild($dt);
        $gotContent = Roff::parse($dt, $lines, true);
        if (!$gotContent) {
            $dl->removeChild($dt);

            return null;
        }
        while (TextContent::$interruptTextProcessing) {
            Roff::parse($dt, $lines, true);
        }

        while (count($lines)) {
            $request = Request::getLine($lines);
            if (!is_null($request) && $request['request'] === 'TQ') {
                array_shift($lines);
                if (count($request['arguments'])) {
                    $indentVal        = Unit::normalize($request['arguments'][0], 'n', 'n');
                    $man->indentation = $indentVal;
                }
                $dt = $dom->createElement('dt');
                $dl->appendChild($dt);
                $gotContent = Roff::parse($dt, $lines, true);
                if (!$gotContent) {
                    $dl->removeChild($dt);
                }
            } else {
                break;
            }
        }

        $man->resetFonts();

        $dd = $dom->createElement('dd');
        Indentation::set($dd, $indentVal);
        /* @var DomElement $dd */
        $dd = $dl->appendChild($dd);

        return $dd;
    }

}
