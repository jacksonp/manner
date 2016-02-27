<?php


class Section
{


    /**
     * Could be a section, a subsection...
     */
    static function handle(DOMXpath $xpath, HybridNode $parentNode, int $level)
    {

        if ($level > 6) {
            exit('Passed max heading level: ' . $level);
        }

        $dom = $parentNode->ownerDocument;

        /** @var HybridNode $lastSubsectionNode */
        $lastSubsectionNode = null;

        foreach ($parentNode->manLines as $key => $line) {

            // Start a subsection
            if (preg_match('~^\.SS (.*)$~', $line, $matches)) {
                $subsectionHeading = $matches[1];
                if (empty($subsectionHeading)) {
                    exit($line . ' - empty subsection heading.');
                }
                $lastSubsectionNode = $dom->createElement('div');
                $lastSubsectionNode->setAttribute('class', 'subsection');
                $lastSubsectionNode->appendChild($dom->createElement('h' . $level, $subsectionHeading));
                $lastSubsectionNode = $parentNode->appendChild($lastSubsectionNode);
                continue;
            }

            if (!is_null($lastSubsectionNode)) {
                $lastSubsectionNode->addManLine($line);
                unset($parentNode->manLines[$key]); // moved to subsection!
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

        $sections = $xpath->query('//div[@class="subsection"]');
        foreach ($sections as $section) {
            Section::handle($xpath, $section, $level + 1);
        }

    }

}
