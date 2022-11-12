<?php

declare(strict_types=1);

namespace Manner\Block;

use DOMElement;
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
        }

        if ($backToLevel < 2) {
            // .RE 1 is back to base level (used e.g. in lsmcli.1).
            $man->setRegister('an-level', '1');
            $man->resetIndentationToDefault();

            return Node::ancestor($parentNode, 'section');
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
