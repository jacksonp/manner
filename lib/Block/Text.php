<?php


class Block_Text
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        if (preg_match('~^\.~u', $lines[$i])) {
            return false;
        }

        $numLines = count($lines);

        $line = $lines[$i];

        // Implicit line break: "A line that begins with a space causes a break and the space is output at the beginning of the next line. Note that this space isn't adjusted, even in fill mode."
        $implicitBreak = mb_substr($line, 0, 1) === ' ';

        if (
          (mb_strlen($line) < 2 or mb_substr($line, 0, 2) !== '\\.') and
          !preg_match('~\\\\c$~', $line)
        ) {
            for (; $i < $numLines - 1; ++$i) {
                $nextLine = $lines[$i + 1];
                if ($nextLine === '' or
                  in_array(mb_substr($nextLine, 0, 1), ['\'', '.', ' ']) or
                  mb_strpos($nextLine, "\t") > 0 or // Could be TabTable
                  (mb_strlen($nextLine) > 1 and mb_substr($nextLine, 0, 2) === '\\.')
                ) {
                    break;
                }
                if ($nextLine === '\\&') {
                    // Skip this, not otherwise they can mess up continuations
                    continue;
                }


                $line .= ' ' . $nextLine;
            }
        }

        self::addLine($parentNode, $line, $implicitBreak);

        return $i;

    }

    static function addLine(DOMElement $parentNode, string $line, bool $prefixBR = false)
    {

        list ($textParent, $shouldAppend) = Blocks::getTextParent($parentNode);

        if ($prefixBR) {
            self::addImplicitBreak($textParent);
        }

        TextContent::interpretAndAppendText(
          $textParent,
          $line,
          $textParent->hasContent(),
          !in_array($textParent->tagName, ['h2', 'h3'])
        );

        if ($shouldAppend) {
            $parentNode->appendBlockIfHasContent($textParent);
        }

    }

    private static function addImplicitBreak(DOMElement $parentNode)
    {
        if (
          $parentNode->hasChildNodes() and
          (
            $parentNode->lastChild->nodeType !== XML_ELEMENT_NODE or
            $parentNode->lastChild->tagName !== 'br'
          )
        ) {
            $parentNode->appendChild($parentNode->ownerDocument->createElement('br'));
        }
    }

}
