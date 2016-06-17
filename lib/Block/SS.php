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

        $h3 = $dom->createElement('h3');

        if ($matches[1] === '') {
            // Text for subheading is on next line.
            $subsectionHeading = $lines[++$i];
            TextContent::interpretAndAppendCommand($h3, $subsectionHeading);
        } else {
            $subsectionHeading = trim($matches[1]);
            $subsectionHeading = trim($subsectionHeading, '"');
            TextContent::interpretAndAppendText($h3, $subsectionHeading);
        }

        // We skip empty .SS macros
        if (empty($subsectionHeading)) {
            return $i;
        }

        $subsection = $dom->createElement('div');
        $subsection->setAttribute('class', 'subsection');
        $subsection->appendChild($h3);

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
