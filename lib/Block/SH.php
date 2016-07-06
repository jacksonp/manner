<?php


class Block_SH
{

    static function check($line)
    {
        if (preg_match('~^\.SH(.*)$~u', $line, $matches)) {
            return $matches;
        }

        return false;
    }

    static function checkAppend(HybridNode $parentNode, array $lines, int $i)
    {

        $matches = self::check($lines[$i]);
        if ($matches === false) {
            return false;
        }

        $dom      = $parentNode->ownerDocument;
        $numLines = count($lines);

        $headingNode = $dom->createElement('h2');

        if ($matches[1] === '') {
            if ($i === $numLines - 1 or self::check($lines[$i + 1])) {
                return $i;
            }
            // Text for subheading is on next line.
            $sectionHeading = $lines[++$i];
            if (in_array($sectionHeading, Block_Section::skipSectionNameLines)) {
                return $i;
            }
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
            $line    = $lines[$i];
            $matches = self::check($lines[$i]);
            if ($matches !== false) {
                if (
                  $matches[1] === '' and
                  $i < $numLines - 1 and
                  in_array($lines[$i + 1], Block_Section::skipSectionNameLines)
                ) {
                    continue;
                }
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
