<?php


class Block_SS
{

    private static function endSubsection($line)
    {
        return preg_match('~^\.\s*S[SH]~u', $line);
    }

    static function checkAppend(HybridNode $parentNode, array $lines, int $i, array $arguments)
    {

        $dom      = $parentNode->ownerDocument;
        $numLines = count($lines);

        $headingNode = $dom->createElement('h3');

        if (count($arguments) === 0) {
            if ($i === $numLines - 1 or self::endSubsection($lines[$i + 1])) {
                return $i;
            }
            // Text for subheading is on next line.
            $sectionHeading = $lines[++$i];
            if (in_array($sectionHeading, Block_Section::skipSectionNameLines)) {
                // Skip $line to work around bugs in man pages, e.g. xorrecord.1, bdh.3
                return $i;
            }
            Blocks::handle($headingNode, [$sectionHeading]);
        } else {
            $sectionHeading = ltrim(implode(' ', $arguments));
            TextContent::interpretAndAppendText($headingNode, $sectionHeading);
        }

        // We skip empty .SS macros
        if (trim($headingNode->textContent) === '') {
            return $i;
        }

        $headingNode->lastChild->textContent = Util::rtrim($headingNode->lastChild->textContent);

        $subsection = $dom->createElement('div');
        $subsection->setAttribute('class', 'subsection');
        $subsection->appendChild($headingNode);

        $blockLines = [];
        for ($i = $i + 1; $i < $numLines; ++$i) {
            $line = $lines[$i];
            if (self::endSubsection($line)) {
                break;
            } else {
                $blockLines[] = $line;
            }
        }

        Blocks::handle($subsection, $blockLines);
        $parentNode->appendBlockIfHasContent($subsection);

        return $i - 1;

    }

}
