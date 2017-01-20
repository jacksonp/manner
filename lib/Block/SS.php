<?php


class Block_SS
{

    static function endSubsection($line)
    {
        return Request::is($line, ['SS', 'SH']);
    }

    static function checkAppend(HybridNode $parentNode, array $lines, int $i, array $arguments)
    {

        $dom      = $parentNode->ownerDocument;
        $numLines = count($lines);

        $headingNode = $dom->createElement('h3');

        if (count($arguments) === 0) {
            if ($i === $numLines - 1 || self::endSubsection($lines[$i + 1])) {
                return $i;
            }
            // Text for subheading is on next line.
            $sectionHeading = $lines[++$i];
            if (in_array($sectionHeading, Block_Section::skipSectionNameLines)) {
                // Skip $line to work around bugs in man pages, e.g. xorrecord.1, bdh.3
                return $i;
            }
            $sectionHeading = [$sectionHeading];
            Roff::parse($headingNode, $sectionHeading);
        } else {
            $sectionHeading = ltrim(implode(' ', $arguments));
            TextContent::interpretAndAppendText($headingNode, $sectionHeading);
        }

        // We skip empty .SS macros
        if (trim($headingNode->textContent) === '') {
            return $i;
        }

        $headingNode->lastChild->textContent = Util::rtrim($headingNode->lastChild->textContent);

        $subsection = $dom->createElement('section');
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

        Blocks::trim($blockLines);
        Roff::parse($subsection, $blockLines);
        $parentNode->appendBlockIfHasContent($subsection);

        return $i - 1;

    }

}
