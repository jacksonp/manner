<?php


class Block_SS
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        if (!preg_match('~^\.SS(.*)$~u', $lines[$i], $matches)) {
            return false;
        }

        $dom      = $parentNode->ownerDocument;
        $numLines = count($lines);

        $headingNode = $dom->createElement('h3');

        if ($matches[1] === '') {
            if ($i === $numLines - 1) {
                return $i;
            }
            // Text for subheading is on next line.
            $sectionHeading = $lines[++$i];
            if ($sectionHeading === '.br') {
                // Skip this to work around bugs in man pages, e.g. xorrecord.1
                return $i;
            }
            Blocks::handle($headingNode, [$sectionHeading]);
        } else {
            $sectionHeading = trim($matches[1]);
            $sectionHeading = trim($sectionHeading, '"');
            TextContent::interpretAndAppendText($headingNode, $sectionHeading);
        }

        // We skip empty .SS macros
        if (empty($sectionHeading)) {
            return $i;
        }

        $subsection = $dom->createElement('div');
        $subsection->setAttribute('class', 'subsection');
        $subsection->appendChild($headingNode);

        $blockLines = [];
        for ($i = $i + 1; $i < $numLines; ++$i) {
            $line = $lines[$i];
            if (preg_match('~^\.S[SH]~u', $line)) {
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
