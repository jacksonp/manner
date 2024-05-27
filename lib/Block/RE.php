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
use Manner\Indentation;
use Manner\Man;
use Manner\Node;

/**
 * .RE [nnn]
 * This macro moves the left margin back to level nnn, restoring the previous left margin. If no argument is given, it
 * moves one level back. The first level (i.e., no call to .RS yet) has number 1, and each call to .RS increases the
 * level by 1.
 *
 * .de1 RE
 * .  ie \\n[.$] .nr an-level ((;\\$1) <? \\n[an-level])
 * .  el         .nr an-level -1
 * .  nr an-level (1 >? \\n[an-level])
 * .  nr an-margin \\n[an-saved-margin\\n[an-level]]
 * .  nr an-prevailing-indent \\n[an-saved-prevailing-indent\\n[an-level]]
 * .  in \\n[an-margin]u
 * ..
 *
 */
class RE implements Template
{

    /**
     * @throws Exception
     */
    public static function checkAppend(
      DOMElement $parentNode,
      array &$lines,
      array $request,
      bool $needOneLineOnly = false
    ): ?DOMElement {
        array_shift($lines);

        $man     = Man::instance();
        $anLevel = (int)$man->getRegister('an-level');

        $backToLevel = $anLevel - 1; // Back to one before last by default.
        if (count($request['arguments'])) {
            $backToLevel = (int)$request['arguments'][0];
            if ($backToLevel < 2) {
                // .RE 1 is back to base level (used e.g. in lsmcli.1).
                $man->setRegister('an-level', '1');
                $man->resetIndentationToDefault();

                return Node::ancestor($parentNode, 'section');
            }
        }

        $lastDIV = $parentNode;

        while ($anLevel > $backToLevel) {
            --$anLevel;
            while ($lastDIV = Node::ancestor($lastDIV, 'div')) {
                if ($lastDIV->hasAttribute('remap')) {
                    $lastDIV = $lastDIV->parentNode;
                } else {
                    break;
                }
            }
            if (is_null($lastDIV)) {
                $man->setRegister('an-level', '1');
                $man->resetIndentationToDefault();

                return Node::ancestor($parentNode, 'section');
            }
        }

        $man->setRegister('an-level', (string)$anLevel);

        // Restore prevailing indent (see macro definition above)
        if (Indentation::isSet($lastDIV->parentNode)) {
            $man->indentation = (string)Indentation::get($lastDIV->parentNode);
        } else {
            $man->resetIndentationToDefault();
        }

        return $lastDIV->parentNode;
    }

}
