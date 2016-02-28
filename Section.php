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
        /** @var HybridNode[] $sectionNodes */
        $sectionNodes = [];
        $sectionNum   = 0;

        foreach ($parentSectionNode->manLines as $key => $line) {

            // Start a subsection
            if (
              ($level === 2 && preg_match('~^\.SH (.*)$~', $line, $matches))
              || ($level > 2 && preg_match('~^\.SS (.*)$~', $line, $matches))
            ) {
                $sectionHeading = $matches[1];
                if (empty($sectionHeading)) {
                    exit($line . ' - empty section heading.');
                }

                unset($parentSectionNode->manLines[$key]); // made a subsection out of this!

                ++$sectionNum;
                $sectionNodes[$sectionNum] = $dom->createElement('div');
                $sectionNodes[$sectionNum]->setAttribute('class', $level === 2 ? 'section' : 'subsection');
                $sectionNodes[$sectionNum]->appendChild($dom->createElement('h' . $level, $sectionHeading));
                continue;
            }

            if ($level === 2 && empty($sectionNodes)) {
                exit($line . ' - not in a section.');
            }

            if (!empty($sectionNodes)) {
                $sectionNodes[$sectionNum]->addManLine($line);
                unset($parentSectionNode->manLines[$key]); // moved to subsection!
            }

        }

        foreach ($sectionNodes as $section) {
            Section::handle($section, $level + 1);
        }
        //</editor-fold>

        BlockContents::handle($parentSectionNode);

        // Sections come after any other content.
        foreach ($sectionNodes as $section) {
            $parentSectionNode->appendChild($section);
        }

        // Not in a subsection, handle content:

//            if ($line[0] === '.') {
        // It's a command


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
