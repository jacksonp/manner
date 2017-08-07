<?php
declare(strict_types=1);

class Indentation
{

    // The default indentation is 7.2n in troff mode and 7n in nroff mode except for grohtml, which ignores indentation.
    // (https://www.mankier.com/7/groff_man#Miscellaneous)
    const DEFAULT = '7';

    static function get(DOMElement $el): int
    {
        return (int)$el->getAttribute('indent');
    }

    static function set(DOMElement $el, int $indentVal): void
    {
        $el->setAttribute('indent', (string)$indentVal);
    }

    static function add(DOMElement $el, int $indentVal)
    {
        self::set($el, self::get($el) + $indentVal);
    }

    static function subtract(DOMElement $el, int $indentVal)
    {
        self::set($el, self::get($el) + $indentVal);
    }

    public static function addElIndent(DOMElement $remainingNode, DOMElement $leavingNode): void
    {
        $remainingNodeIndent = self::get($remainingNode);
        $leavingNodeIndent   = self::get($leavingNode);

        // TODO: this could be simplified
        if (!$leavingNodeIndent) {
            // Do nothing
        } elseif (!$remainingNodeIndent) {
            self::set($remainingNode, $leavingNodeIndent);
        } else {
            self::set($remainingNode, $remainingNodeIndent + $leavingNodeIndent);
        }
    }

}
