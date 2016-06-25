<?php


class Block_SH
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        if (!preg_match('~^\.SH(.*)$~u', $lines[$i], $matches)) {
            return false;
        }

        $dom      = $parentNode->ownerDocument;
        $numLines = count($lines);

        $headingNode = $dom->createElement('h2');

        if ($matches[1] === '') {
            if ($i === $numLines - 1) {
                return $i;
            }
            // Text for subheading is on next line.
            $sectionHeading = $lines[++$i];
            Blocks::handle($headingNode, [$sectionHeading]);
        } else {
            $sectionHeading = trim($matches[1]);
            $sectionHeading = trim($sectionHeading, '"');
            TextContent::interpretAndAppendText($headingNode, $sectionHeading);
        }

        // We skip empty .SH macros
        if (empty($sectionHeading)) {
            return $i;
        }

        $section = $dom->createElement('div');
        $section->setAttribute('class', 'section');
        $section->appendChild($headingNode);

        $blockLines = [];
        for ($i = $i + 1; $i < $numLines; ++$i) {
            $line = $lines[$i];
            if (preg_match('~^\.SH~u', $line)) {
                --$i;
                break;
            } else {
                $blockLines[] = $line;
            }
        }

        Blocks::handle($section, $blockLines);
        $parentNode->appendBlockIfHasContent($section);

        return $i;

    }

}
