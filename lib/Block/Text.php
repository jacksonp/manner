<?php


class Block_Text implements Block_Template
{

    private static $interruptTextProcessing = false;

    static function addSpace(DOMElement $parentNode)
    {

        if (
            !$parentNode->isOrInTag('pre') &&
            $parentNode->hasContent() &&
            (
                $parentNode->lastChild->nodeType !== XML_ELEMENT_NODE ||
                in_array($parentNode->lastChild->tagName, Blocks::INLINE_ELEMENTS)
            ) &&
            !TextContent::$continuation
        ) {
            $parentNode->appendChild(new DOMText(' '));
        }
    }

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        $line = self::removeTextProcessingInterrupt($request['raw_line']);

        array_shift($lines);

        $parentNode = Blocks::getParentForText($parentNode);

        // Implicit line break: "A line that begins with a space causes a break and the space is output at the beginning of the next line. Note that this space isn't adjusted, even in fill mode."
        $implicitBreak = mb_substr($line, 0, 1) === ' ';

        $man = Man::instance();

        // TODO: we accept text lines start with \' - because of bugs in man pages for now, revisit.
        if (mb_strlen($line) < 2 || mb_substr($line, 0, 2) !== '\\.') {
            while (count($lines) && !self::$interruptTextProcessing && !$needOneLineOnly) {
                Request::getLine($lines); // process line...
                if (!count($lines)) {
                    break;
                }
                $nextLine = $lines[0];
                if (trim($nextLine) === '' ||
                    in_array(mb_substr($nextLine, 0, 1), [$man->control_char, $man->control_char_2, ' ']) ||
                    mb_strpos($nextLine, "\t") > 0 || // Could be TabTable
                    (mb_strlen($nextLine) > 1 && mb_substr($nextLine, 0, 2) === '\\.')
                ) {
                    break;
                }

                array_shift($lines);

                $line .= ' ' . self::removeTextProcessingInterrupt($nextLine);
            }
        }

        // Re-add continuation if present to last line for TextContent::interpretAndAppendText:
        if (self::$interruptTextProcessing) {
            self::$interruptTextProcessing = false;
            $line .= '\\c';
        }

        self::addLine($parentNode, $line, $implicitBreak);

        return $parentNode;

    }

    static private function removeTextProcessingInterrupt(string $line)
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
