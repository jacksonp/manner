<?php
declare(strict_types=1);

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
class Block_RE implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        $man             = Man::instance();
        $leftMarginLevel = $man->left_margin_level;

        $backToLevel = $leftMarginLevel - 1; // Back to one before last by default.
        if (count($request['arguments'])) {
            $backToLevel = (int)$request['arguments'][0];
        }

        if ($backToLevel < 2) {
            // .RE 1 is back to base level (used e.g. in lsmcli.1).
            $man->left_margin_level = 1;
            $man->resetIndentationToDefault();
            return $parentNode->ancestor('section');
        }

        $lastDIV = $parentNode;

        while ($leftMarginLevel > $backToLevel) {

            --$leftMarginLevel;
            $lastDIV = $lastDIV->ancestor('div');
            if (is_null($lastDIV)) {
                $man->left_margin_level = 1;
                $man->resetIndentationToDefault();
                return $parentNode->ancestor('section');
            }
        }

        $man->left_margin_level = $leftMarginLevel;

        // Restore prevailing indent (see macro definition above)
        if ($lastDIV->parentNode->hasAttribute('indent')) {
            $man->indent = $lastDIV->parentNode->getAttribute('indent');
        } else {
            $man->resetIndentationToDefault();
        }

        return $lastDIV->parentNode;

    }

}
