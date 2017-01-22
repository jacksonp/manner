<?php


class Block_Text implements Block_Template
{

    private static $continuation = false;

    static function addSpace(DOMElement $parentNode, DOMElement $textParent, bool $shouldAppend)
    {
        if (
            $parentNode->tagName !== 'pre' &&
            (!$textParent->lastChild || $textParent->lastChild->nodeType !== XML_ELEMENT_NODE || $textParent->lastChild->tagName !== 'br') &&
            !$shouldAppend &&
            !TextContent::$continuation &&
            $textParent->hasContent()
        ) {
            $textParent->appendChild(new DOMText(' '));
        }
    }

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $arguments = null,
        ?string $request = null,
        $needOneLineOnly = false
    ) {

        $line = self::removeContinuation($lines[0]);

        array_shift($lines);

        // Implicit line break: "A line that begins with a space causes a break and the space is output at the beginning of the next line. Note that this space isn't adjusted, even in fill mode."
        $implicitBreak = mb_substr($line, 0, 1) === ' ';

        if (!$needOneLineOnly) {
            list ($textParent, $shouldAppend, $needOneLineOnly) = Blocks::getTextParent($parentNode);
        }

        // TODO: we accept text lines start with \' - because of bugs in man pages for now, revisit.
        if (mb_strlen($line) < 2 || mb_substr($line, 0, 2) !== '\\.') {
            while (count($lines) && (self::$continuation || !$needOneLineOnly)) {
                $nextLine = $lines[0];
                if (trim($nextLine) === '' ||
                    in_array(mb_substr($nextLine, 0, 1), ['.', ' ']) ||
                    mb_strpos($nextLine, "\t") > 0 || // Could be TabTable
                    (mb_strlen($nextLine) > 1 && mb_substr($nextLine, 0, 2) === '\\.')
                ) {
                    break;
                }

                array_shift($lines);

                if (in_array($nextLine, ['\\&', '\\)'])) {
                    if (self::$continuation) {
                        $line .= ' ';
                    }
                    continue;
                }

                $line .= (self::$continuation ? '' : ' ') . self::removeContinuation($nextLine);
            }
        }

        // Re-add continuation if present to last line for TextContent::interpretAndAppendText:
        if (self::$continuation) {
            self::$continuation = false;
            $line .= '\\c';
        }

        self::addLine($parentNode, $line, $implicitBreak);

        return 0;

    }

    static private function removeContinuation(string $line)
    {
        $line = Replace::preg('~\\\\c\s*$~', '', $line, -1, $replacements);
        self::$continuation = $replacements > 0;

        return $line;
    }

    static function addLine(DOMElement $parentNode, string $line, bool $prefixBR = false)
    {

        list ($textParent, $shouldAppend) = Blocks::getTextParent($parentNode);

        if ($prefixBR) {
            self::addImplicitBreak($textParent);
        }

        self::addSpace($parentNode, $textParent, $shouldAppend);

        TextContent::interpretAndAppendText($textParent, $line);

        if ($shouldAppend) {
            $parentNode->appendBlockIfHasContent($textParent);
        }

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
