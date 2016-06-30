<?php


class Block_Text
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        if (preg_match('~^\.~u', $lines[$i])) {
            return false;
        }

        $numLines = count($lines);
        $dom      = $parentNode->ownerDocument;

        $line = $lines[$i];

        // Implicit line break: "A line that begins with a space causes a break and the space is output at the beginning of the next line. Note that this space isn't adjusted, even in fill mode."
        $implicitBreak = mb_substr($line, 0, 1) === ' ';

        if (
          !$implicitBreak and
          (mb_strlen($line) < 2 or mb_substr($line, 0, 2) !== '\\.') and
          !preg_match('~\\\\c$~', $line)
        ) {
            for (; $i < $numLines - 1; ++$i) {
                $nextLine = $lines[$i + 1];
                if ($nextLine === '' or
                  in_array(mb_substr($nextLine, 0, 1), ['.', ' ']) or
                  mb_strpos($nextLine, "\t") > 0 or // Could be TabTable
                  (mb_strlen($nextLine) > 1 and mb_substr($nextLine, 0, 2) === '\\.')
                ) {
                    break;
                }
                $line .= ' ' . $nextLine;
            }
        }

        if (in_array($parentNode->tagName, Blocks::TEXT_CONTAINERS)) {

            if ($implicitBreak) {
                self::addImplicitBreak($parentNode);
            }

            TextContent::interpretAndAppendText($parentNode, $line, $parentNode->hasContent(),
              !in_array($parentNode->tagName, ['h2', 'h3']));

        } else {

            $parentNodeLastBlock = $parentNode->getLastBlock();

            if (is_null($parentNodeLastBlock) or
              in_array($parentNodeLastBlock->tagName, ['div', 'pre', 'code', 'table', 'h2', 'h3', 'dl'])
            ) {

                $p = $parentNode->appendChild($dom->createElement('p'));
                TextContent::interpretAndAppendText($p, $line);

            } else {

                if ($implicitBreak) {
                    self::addImplicitBreak($parentNodeLastBlock);
                }

                TextContent::interpretAndAppendText($parentNodeLastBlock, $line, $parentNode->hasContent());

            }

        }

        return $i;

    }

    private static function addImplicitBreak($parentNode)
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
