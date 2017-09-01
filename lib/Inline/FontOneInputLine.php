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

        if (
            count($request['arguments']) === 0 &&
            count($lines) &&
            Blocks::lineEndsBlock(Request::getLine($lines), $lines)
        ) {
            return null; // Skip
        }

        $parentNode = Blocks::getParentForText($parentNode);

        $man->pushFont($request['request']);

        if (count($request['arguments']) === 0) {
            Roff::parse($parentNode, $lines, true);
        } else {
            Block_Text::addSpace($parentNode);
            TextContent::interpretAndAppendText($parentNode, implode(' ', $request['arguments']));
            if ($pre = Node::ancestor($parentNode, 'pre')) {
                PreformattedOutput::endInputLine($pre);
            }
        }

        $man->resetFonts();

        return $parentNode;

    }

}
