<?php


class Section
{

    static function handle(HybridNode $documentNode, array $lines)
    {

        $dom = $documentNode->ownerDocument;

        $numLines     = count($lines);
        $sectionStart = null;

        for ($i = 0; $i < $numLines; ++$i) {
            $line = $lines[$i];

            if (is_null($sectionStart)) {
                if ($line === '' or preg_match('~^(\\\\?\.$|\.(ad|fi))~u', $line)) {
                    continue;
                }
            }

            if (preg_match('~^\.SH (.*)$~u', $line, $matches)) {

                $sectionHeading = $matches[1];
                $sectionHeading = trim($sectionHeading, '"');

                // We ignore empty .SH macros
                if (!empty($sectionHeading)) {

                    if (is_null($sectionStart)) {
                        Blocks::handle($documentNode, array_slice($lines, 0, $i));
                    } else {
                        Blocks::handle($section, array_slice($lines, $sectionStart, $i - $sectionStart));
                    }

                    $section = $dom->createElement('div');
                    $section->setAttribute('class', 'section');
                    $h = $dom->createElement('h2');
                    TextContent::interpretAndAppendText($h, $sectionHeading);
                    $section->appendChild($h);
                    $documentNode->appendChild($section);
                    $sectionStart = $i + 1;
                }
            }

            if ($i === $numLines - 1 && !is_null($sectionStart)) {
                Blocks::handle($section, array_slice($lines, $sectionStart, $i + 1 - $sectionStart));
            }

        }

        if (is_null($sectionStart)) {
            Blocks::handle($documentNode, $lines);
        }

    }

}
