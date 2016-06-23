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
            $subsectionHeading = $lines[++$i];
            TextContent::interpretAndAppendCommand($headingNode, $subsectionHeading);
        } else {
            $subsectionHeading = trim($matches[1]);
            $subsectionHeading = trim($subsectionHeading, '"');
            TextContent::interpretAndAppendText($headingNode, $subsectionHeading);
        }

        // We skip empty .SH macros
        if (empty($subsectionHeading)) {
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
