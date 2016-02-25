<?php


class Section
{


    /**
     * Could be a section, a subsection...
     * @param DOMNode $parentNode
     * @param int $level
     * @param string $heading
     * @param array $sectionLines
     */
    static function handle(DOMNode $parentNode, int $level, string $heading, array $sectionLines)
    {

        if ($level > 6) {
            exit('Passed max heading level: ' . $level);
        }

        $dom = $parentNode->ownerDocument;

        $sectionNode = $dom->createElement('div');
        $headingNode = $dom->createElement('h' . $level, $heading);
        $headingNode = $sectionNode->appendChild($headingNode);

        $sectionNode = $parentNode->appendChild($sectionNode);

        $subsections       = [];
        $subsectionHeading = null;

        $lastParagraph = null;

        foreach ($sectionLines as $line) {

            // Start a subsection
            if (preg_match('~^\.SS (.*)$~', $line, $matches)) {
                $subsectionHeading = $matches[1];
                if (empty($subsectionHeading)) {
                    exit($line . ' - empty subsection heading.');
                }
                $subsections[$subsectionHeading] = [];
                continue;
            }

            if (!is_null($subsectionHeading)) {
                $subsections[$subsectionHeading][] = $line;
            }

            // Not in a subsection, handle content:

            if ($line[0] === '.') {
                // It's a command


                // FAIL on unknown command
//    if (preg_match('~^\.~', $line, $matches)) {
//        exit($line . ' (' . $i . ')' . "\n");
//    }

            } else {
                // It's some text


//                if (is_null($lastParagraph)) {
//                    $lastParagraph = $dom->createElement('p');
//                    $lastParagraph = $sectionNode->appendChild($lastParagraph);
//                }
//
//                $textNode = $dom->createTextNode(Text::massage($line));
//                $textNode = $lastParagraph->appendChild($textNode);


            }


        }

        foreach ($subsections as $heading => $subsectionLines) {
            self::handle($sectionNode, $level + 1, $heading, $subsectionLines);
        }


    }

}
