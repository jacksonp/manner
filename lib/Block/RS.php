<?php
declare(strict_types=1);

/**
 * Class Block_RS
 *
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
class Block_RS implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        $dom = $parentNode->ownerDocument;
        $man = Man::instance();

        if (count($request['arguments']) && $request['arguments'][0] === '0') {
            $parentNode = $parentNode->ancestor('section');
        } else {
            $parentNode = Blocks::getBlockContainerParent($parentNode);
        }

        if (count($request['arguments'])) {
            $leftMargin = Roff_Unit::normalize($request['arguments'][0]);
        } else {
            $leftMargin = $man->indentation;
        }

        $man->left_margin_level = $man->left_margin_level + 1;

        $man->resetIndentationToDefault();

        /* @var DomElement $div */
        $div = $dom->createElement('div');

        if ($leftMargin !== '0') {
            $div->setAttribute('left-margin', $leftMargin);
        }

        $div = $parentNode->appendChild($div);
        return $div;

    }

}
