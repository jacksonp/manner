<?php
declare(strict_types=1);

class Inline_FontOneInputLine implements Block_Template
{

    static function checkAppend(
        DOMElement $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        $man = Man::instance();

        $man->pushFont($request['request']);

        if (count($request['arguments']) === 0) {
            $man->addPostOutputCallback(function () use ($parentNode) {
                Man::instance()->resetFonts();
                return null;
            });
            return null;
        } else {
            $parentNode = Blocks::getParentForText($parentNode);
            Block_Text::addSpace($parentNode);
            TextContent::interpretAndAppendText($parentNode, implode(' ', $request['arguments']));
            if ($pre = Node::ancestor($parentNode, 'pre')) {
                PreformattedOutput::endInputLine($pre);
            }
            $man->resetFonts();
            return $parentNode;
        }

    }

}
