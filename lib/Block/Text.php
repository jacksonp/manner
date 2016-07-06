<?php


class Block_Text
{

    private static $continuation = false;

    static function getNextInputLine(array $lines, int $i): array
    {

        $blockLines = []; // Could be .B on one line, then some text on next line for example.
        $numLines   = count($lines);
        for (; $i < $numLines; ++$i) {

            if (
              Block_RS::check($lines[$i]) or
              Block_TP::check($lines[$i]) or
              Block_IP::check($lines[$i]) or
              Block_SH::check($lines[$i]) or
              Block_SS::check($lines[$i]) or
              Block_nf::check($lines[$i])
            ) {
                --$i;
                break;
            }

            if (!Block_P::check($lines[$i]) and !Inline_VerticalSpace::check($lines[$i])) {
                $blockLines[] = $lines[$i];
                if (
                  mb_substr($lines[$i], 0, 1) !== '.' or
                  (
                    $matches = Inline_FontOneInputLine::check($lines[$i]) and
                    @$matches[2] != '' // NB: not !==, might not be set
                  ) or
                  (
                    $matches = Inline_AlternatingFont::check($lines[$i]) and
                    @$matches[2] != '' // NB: not !==, might not be set
                  )
                ) {
                    break;
                }

            }

        }
        if ($i < $numLines - 1 and $lines[$i + 1] === '.UE') {
            $blockLines[] = $lines[++$i];
        }

        return ['i' => $i, 'lines' => $blockLines];

    }

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        // TODO: accept \' for now, see e.g. tar.0p .TS with cell '0'
        if (preg_match('~^[\.]~u', $lines[$i])) {
            return false;
        }

        $numLines = count($lines);

        $line = self::removeContinuation($lines[$i]);

        // Implicit line break: "A line that begins with a space causes a break and the space is output at the beginning of the next line. Note that this space isn't adjusted, even in fill mode."
        $implicitBreak = mb_substr($line, 0, 1) === ' ';

        // TODO: we accept text lines start with \' - because of bugs in man pages for now, revisit.
        if (mb_strlen($line) < 2 or mb_substr($line, 0, 2) !== '\\.') {
            for (; $i < $numLines - 1; ++$i) {
                $nextLine = $lines[$i + 1];
                if ($nextLine === '' or
                  in_array(mb_substr($nextLine, 0, 1), ['.', ' ']) or
                  mb_strpos($nextLine, "\t") > 0 or // Could be TabTable
                  (mb_strlen($nextLine) > 1 and mb_substr($nextLine, 0, 2) === '\\.')
                ) {
                    break;
                }


                if ($nextLine === '\\&') {
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

        return $i;

    }

    static private function removeContinuation(string $line)
    {
        $line               = Replace::preg('~\\\\c$~', '', $line, -1, $replacements);
        self::$continuation = $replacements > 0;

        return $line;
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
