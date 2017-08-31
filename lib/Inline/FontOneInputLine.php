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
            (Blocks::lineEndsBlock(Request::getLine($lines), $lines))
        ) {
            return null; // Skip
        }

        if (count($request['arguments']) === 1 && $request['arguments'][0] === '') {
            return null; // bug in man page, see e.g. basic_ldap_auth.8: .B "\"uid\=%s\""
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
