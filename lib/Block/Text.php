<?php
declare(strict_types=1);

class Block_Text implements Block_Template
{

    public static $interruptTextProcessing = false;

    static function addSpace(DOMElement $parentNode)
    {

        if (
            !Node::isOrInTag($parentNode,'pre') && Node::hasContent($parentNode) &&
            (
                $parentNode->lastChild->nodeType !== XML_ELEMENT_NODE ||
                in_array($parentNode->lastChild->tagName, Blocks::INLINE_ELEMENTS)
            ) &&
            !TextContent::$interruptTextProcessing
        ) {
            $parentNode->appendChild(new DOMText(' '));
        }
    }

    static function checkAppend(
        DOMElement $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        $parentNode = Blocks::getParentForText($parentNode);

        if (Man::instance()->hasPostOutputCallbacks()) {
            $needOneLineOnly = true;
        }

        // Reset
        self::$interruptTextProcessing = false;

        $line = self::removeTextProcessingInterrupt($request['raw_line']);

        array_shift($lines);

        // Implicit line break: "A line that begins with a space causes a break and the space is output at the beginning of the next line. Note that this space isn't adjusted, even in fill mode."
        $implicitBreak = mb_substr($line, 0, 1) === ' ';

        while (count($lines) && !self::$interruptTextProcessing && !$needOneLineOnly) {
            $nextRequest = Request::getLine($lines); // process line...
            if (is_null($nextRequest)) {
                break;
            }
            $nextRequest = Request::setClass($nextRequest, $lines);
            if (
                $nextRequest['class'] !== 'Block_Text' ||
                mb_substr($nextRequest['raw_line'], 0, 1) === ' ' // Stop on implicit line break.
            ) {
                break;
            }
            array_shift($lines);
            $line .= ' ' . self::removeTextProcessingInterrupt($nextRequest['raw_line']);
        }

        // Re-add interrupt if present to last line for TextContent::interpretAndAppendText:
        if (self::$interruptTextProcessing) {
            $line .= '\\c';
        }

        self::addLine($parentNode, $line, $implicitBreak);

        return $parentNode;

    }

    static private function removeTextProcessingInterrupt(string $line): string
    {
        $line                          = Replace::preg('~\\\\c\s*$~', '', $line, -1, $replacements);
        self::$interruptTextProcessing = $replacements > 0;
        return $line;
    }

    static function addLine(DOMElement $parentNode, string $line, bool $prefixBR = false)
    {

        if ($prefixBR) {
            self::addImplicitBreak($parentNode);
        }

        self::addSpace($parentNode);

        TextContent::interpretAndAppendText($parentNode, $line);

    }

    private static function addImplicitBreak(DOMElement $parentNode)
    {
        if (
            $parentNode->hasChildNodes() &&
            ($parentNode->lastChild->nodeType !== XML_ELEMENT_NODE || $parentNode->lastChild->tagName !== 'br')
        ) {
            $parentNode->appendChild($parentNode->ownerDocument->createElement('br'));
        }
    }

}
