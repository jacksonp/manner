<?php


class SubSection
{

    static function handle(HybridNode $parentNode, array $lines)
    {

        $dom = $parentNode->ownerDocument;

        // Right trim $lines
        for ($i = count($lines) - 1; $i >= 0; --$i) {
            if (in_array($lines[$i], ['', '.br', '.SS'])) {
                unset($lines[$i]);
            } else {
                break;
            }
        }

        $numLines        = count($lines);
        $subsectionStart = null;
        $subsection      = null;

        for ($i = 0; $i < $numLines; ++$i) {
            $line = $lines[$i];

            if (preg_match('~^\.SS(.*)$~u', $line, $matches)) {

                $h3             = $dom->createElement('h3');
                $prevSectionEnd = $i;

                if ($matches[1] === '') {
                    // Text for subheading is on next line.
                    $subsectionHeading = $lines[++$i];
                    TextContent::interpretAndAppendCommand($h3, $subsectionHeading);
                } else {
                    $subsectionHeading = trim($matches[1]);
                    $subsectionHeading = trim($subsectionHeading, '"');
                    TextContent::interpretAndAppendText($h3, $subsectionHeading);
                }

                if (is_null($subsection)) {
                    if ($i > 0) {
                        Blocks::handle($parentNode, array_slice($lines, 0, $prevSectionEnd));
                    }
                } else {
                    Blocks::handle($subsection,
                      array_slice($lines, $subsectionStart, $prevSectionEnd - $subsectionStart));
                }

                if (!empty($subsectionHeading)) { // We ignore empty .SS macros
                    $subsection = $dom->createElement('div');
                    $subsection->setAttribute('class', 'subsection');
                    $subsection->appendChild($h3);
                    $parentNode->appendChild($subsection);
                    $subsectionStart = $i + 1;
                }

                continue;

            }

            if ($i === $numLines - 1 && !is_null($subsection)) {
                Blocks::handle($subsection, array_slice($lines, $subsectionStart, $i + 1 - $subsectionStart));
            }

        }

        if (is_null($subsection)) {
            Blocks::handle($parentNode, $lines);
        }

    }
}
