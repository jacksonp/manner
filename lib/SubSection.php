<?php


class SubSection
{

    static function handle(HybridNode $parentNode, array $lines)
    {

        $dom = $parentNode->ownerDocument;

        $numLines        = count($lines);
        $subsectionStart = null;

        for ($i = 0; $i < $numLines; ++$i) {
            $line = $lines[$i];

            if (preg_match('~^\.SS (.*)$~u', $line, $matches)) {

                if (is_null($subsectionStart)) {
                    Blocks::handle($parentNode, array_slice($lines, 0, $i));
                } else {
                    Blocks::handle($subsection, array_slice($lines, $subsectionStart, $i - $subsectionStart));
                }

                $subsectionHeading = $matches[1];
                $subsectionHeading = trim($subsectionHeading, '"');
                if (!empty($subsectionHeading)) { // We ignore empty .SS macros
                    $subsection = $dom->createElement('div');
                    $subsection->setAttribute('class', 'subsection');
                    $h = $dom->createElement('h3');
                    TextContent::interpretAndAppendText($h, $subsectionHeading);
                    $subsection->appendChild($h);
                    $parentNode->appendChild($subsection);
                    $subsectionStart = $i + 1;
                }
            }

            if ($i === $numLines - 1 && !is_null($subsectionStart)) {
                Blocks::handle($subsection, array_slice($lines, $subsectionStart, $i + 1 - $subsectionStart));
            }

        }

        if (is_null($subsectionStart)) {
            Blocks::handle($parentNode, $lines);
        }

    }
}
