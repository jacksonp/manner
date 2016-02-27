<?php


class Section
{


    /**
     * Could be a section, a subsection...
     */
    static function handle(HybridNode $parentSectionNode, int $level)
    {

        if ($level > 6) {
            exit('Passed max heading level: ' . $level);
        }

        $dom = $parentSectionNode->ownerDocument;

        //<editor-fold desc="Remove any subsections from manLines and handle them">
        /** @var HybridNode[] $subsectionNodes */
        $subsectionNodes = [];
        $sectionNum      = 0;

        foreach ($parentSectionNode->manLines as $key => $line) {

            // Start a subsection
            if (preg_match('~^\.SS (.*)$~', $line, $matches)) {
                $subsectionHeading = $matches[1];
                if (empty($subsectionHeading)) {
                    exit($line . ' - empty subsection heading.');
                }

                unset($parentSectionNode->manLines[$key]); // made a subsection out of this!

                ++$sectionNum;
                $subsectionNodes[$sectionNum] = $dom->createElement('div');
                $subsectionNodes[$sectionNum]->setAttribute('class', 'subsection');
                $subsectionNodes[$sectionNum]->appendChild($dom->createElement('h' . $level, $subsectionHeading));
                $subsectionNodes[$sectionNum] = $parentSectionNode->appendChild($subsectionNodes[$sectionNum]);
                continue;
            }

            if (!empty($subsectionNodes)) {
                $subsectionNodes[$sectionNum]->addManLine($line);
                unset($parentSectionNode->manLines[$key]); // moved to subsection!
            }

        }

        foreach ($subsectionNodes as $section) {
            Section::handle($section, $level + 1);
        }
        //</editor-fold>

        // Now we have no more sections in manLines, do definition lists because .TP is a bit special in that we need to keep the 1st line separate for the definition and not merge text as we would otherwise.
        foreach ($parentSectionNode->manLines as $key => $line) {



        }

        // Not in a subsection, handle content:

//            if ($line[0] === '.') {
        // It's a command


        // FAIL on unknown command
//    if (preg_match('~^\.~', $line, $matches)) {
//        exit($line . ' (' . $i . ')' . "\n");
//    }

//            } else {
        // It's some text


//                if (is_null($lastParagraph)) {
//                    $lastParagraph = $dom->createElement('p');
//                    $lastParagraph = $sectionNode->appendChild($lastParagraph);
//                }
//
//                $textNode = $dom->createTextNode(Text::massage($line));
//                $textNode = $lastParagraph->appendChild($textNode);


//            }

    }

}
