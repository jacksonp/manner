<?php


class Section
{


    /**
     * Could be a section, a subsection...
     */
    static function handle(HybridNode $parentNode, int $level)
    {

        if ($level > 6) {
            exit('Passed max heading level: ' . $level);
        }

        $dom = $parentNode->ownerDocument;

        /** @var HybridNode[] $sectionNodes */
        $sectionNodes     = [];
        $sectionNum       = 0;

        foreach ($parentNode->manLines as $key => $line) {

            // Start a subsection
            if (preg_match('~^\.SS (.*)$~', $line, $matches)) {
                $subsectionHeading = $matches[1];
                if (empty($subsectionHeading)) {
                    exit($line . ' - empty subsection heading.');
                }

                unset($parentNode->manLines[$key]); // made a subsection out of this!

                ++$sectionNum;
                $sectionNodes[$sectionNum] = $dom->createElement('div');
                $sectionNodes[$sectionNum]->setAttribute('class', 'subsection');
                $sectionNodes[$sectionNum]->appendChild($dom->createElement('h' . $level, $subsectionHeading));
                $sectionNodes[$sectionNum] = $parentNode->appendChild($sectionNodes[$sectionNum]);
                continue;
            }

            if (!empty($sectionNodes)) {
                $sectionNodes[$sectionNum]->addManLine($line);
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

        foreach ($sectionNodes as $section) {
            Section::handle($section, $level + 1);
        }

//        $sections = $xpath->query('//div[@class="subsection"]');
//        foreach ($sections as $section) {
//            Section::handle($xpath, $section, $level + 1);
//        }

    }

}
