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
use Manner\Man;
use Manner\Roff\Unit;

/**
 * This macro moves the left margin to the right by the value nnn if specified (default unit is ‘n’); otherwise it is
 * set to the previous indentation value specified with .TP, .IP, or .HP (or to the default value if none of them have
 * been used yet). The indentation value is then set to the default.
 *
 * Calls to the RS macro can be nested.
 *
 * .de1 RS
 * .  nr an-saved-margin\\n[an-level] \\n[an-margin]
 * .  nr an-saved-prevailing-indent\\n[an-level] \\n[an-prevailing-indent]
 * .  ie \\n[.$] .nr an-margin +(n;\\$1)
 * .  el         .nr an-margin +\\n[an-prevailing-indent]
 * .  in \\n[an-margin]u
 * .  nr an-prevailing-indent \\n[IN]
 * .  nr an-level +1
 * ..
 *
 */
class RS implements Template
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

        // See: https://www.mankier.com/1/zip
        // $parentNode = Blocks::getBlockContainerParent($parentNode, true);
        $parentNode = Blocks::getBlockContainerParent($parentNode);

        if (count($request['arguments'])) {
            $leftMargin = Unit::normalize($request['arguments'][0], 'n', 'n');
        } else {
            $leftMargin = $man->indentation;
        }

        $newAnLevel = (int)$man->getRegister('an-level') + 1;
        $man->setRegister('an-level', (string)$newAnLevel);

        $man->resetIndentationToDefault();

        $div = $dom->createElement('div');
        $div->setAttribute('left-margin', (string)$leftMargin);
        /* @var DomElement $div */
        $div = $parentNode->appendChild($div);

        return $div;
    }

}
