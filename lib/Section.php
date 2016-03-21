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
              ($level === 2 && preg_match('~^\.S[Hh] (.*)$~u', $line, $matches))
              || ($level > 2 && preg_match('~^\.S[Ss] (.*)$~u', $line, $matches))
            ) {
                $sectionHeading = $matches[1];
                $sectionHeading = trim($sectionHeading, '"');
                if (empty($sectionHeading)) {
                    echo($line . ' - empty section heading.');
                    exit(1);
                }

                unset($parentSectionNode->manLines[$key]); // made a subsection out of this!

                ++$sectionNum;
                $sectionNodes[$sectionNum] = $dom->createElement('div');
                $sectionNodes[$sectionNum]->setAttribute('class', $level === 2 ? 'section' : 'subsection');
                $h = $dom->createElement('h' . $level);
                $h->appendChild(new DOMText($sectionHeading));
                $sectionNodes[$sectionNum]->appendChild($h);
                continue;
            }

            if ($level === 2 && empty($sectionNodes)) {
                echo($line . ' - not in a section.');
                exit(1);
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

        Blocks::handle($parentSectionNode);

        // Sections come after any other content.
        foreach ($sectionNodes as $section) {
            $parentSectionNode->appendChild($section);
        }

    }

}
