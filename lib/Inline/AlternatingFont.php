<?php
declare(strict_types=1);

class Inline_AlternatingFont implements Block_Template
{

    /**
     * @param DOMElement $parentNode
     * @param array $lines
     * @param array $request
     * @param bool $needOneLineOnly
     * @return DOMElement|null
     * @throws Exception
     */
    static function checkAppend(
        DOMElement $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);
        $parentNode = Blocks::getParentForText($parentNode);
        $man = Man::instance();
        Block_Text::addSpace($parentNode);

        foreach ($request['arguments'] as $bi => $bit) {
            $requestCharIndex = $bi % 2;
            if (!isset($request['request'][$requestCharIndex])) {
                throw new Exception($lines[0] . ' command ' . $request['request'] . ' has nothing at index ' . $requestCharIndex);
            }
            // Re-massage the line:
            // in a man page the AlternatingFont macro argument would become the macro argument to a .ft call and have
            // double backslashes transformed twice (I think)
            $bit = Request::massageLine($bit);
            $man->pushFont($request['request'][$requestCharIndex]);
            TextContent::interpretAndAppendText($parentNode, $bit);
            $man->resetFonts();
        }

        return $parentNode;

    }

}
