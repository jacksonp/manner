<?php


class Section
{

    /**
     * Could be a section, a subsection...
     */
    static function handle(HybridNode $parentSectionNode, int $level)
    {

        if ($level > 6) {
            throw new Exception('Passed max heading level: ' . $level);
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
                    throw new Exception($line . ' - empty section heading.');
                }

                unset($parentSectionNode->manLines[$key]); // made a subsection out of this!

                ++$sectionNum;
                $sectionNodes[$sectionNum] = $dom->createElement('div');
                $sectionNodes[$sectionNum]->setAttribute('class', $level === 2 ? 'section' : 'subsection');
                $h = $dom->createElement('h' . $level);
                TextContent::interpretAndAppendText($h, $sectionHeading);
                $sectionNodes[$sectionNum]->appendChild($h);
                continue;
            }

            if ($level === 2 && empty($sectionNodes)) {
                if (mb_strlen($line) === 0) {
                    continue;
                } else {
                    throw new Exception($line . ' - not in a section.');
                }
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
