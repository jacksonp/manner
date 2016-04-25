<?php


class Section
{

    /**
     * Could be a section, a subsection...
     * @param HybridNode $parentSectionNode
     * @param int $level
     * @throws Exception
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

        $numLines = count($parentSectionNode->manLines);

        for ($i = 0; $i < $numLines; ++$i) {
            $line = $parentSectionNode->manLines[$i];

            if (mb_strlen($line) === 0 && $i === $numLines - 1) {
                unset($parentSectionNode->manLines[$i]); // trim trailing empty lines.
                continue;
            }

            // Start a subsection
            if (
              ($level === 2 and preg_match('~^\.SH (.*)$~u', $line, $matches))
              or ($level > 2 and preg_match('~^\.SS (.*)$~u', $line, $matches))
            ) {
                $sectionHeading = $matches[1];
                $sectionHeading = trim($sectionHeading, '"');
                if (empty($sectionHeading)) {
                    throw new Exception($line . ' - empty section heading.');
                }

                unset($parentSectionNode->manLines[$i]); // made a subsection out of this!

                $sectionNodes[++$sectionNum] = $dom->createElement('div');
                $sectionNodes[$sectionNum]->setAttribute('class', $level === 2 ? 'section' : 'subsection');
                $h = $dom->createElement('h' . $level);
                TextContent::interpretAndAppendText($h, $sectionHeading);
                $sectionNodes[$sectionNum]->appendChild($h);
                continue;
            }

            if ($level === 2 && empty($sectionNodes)) {
                if (mb_strlen($line) === 0
                  || preg_match('~^\.ad~u', $line)
                  || preg_match('~^\\\\?\.$~u', $line) // Empty request
                ) {
                    continue;
                } else {
                    throw new Exception($line . ' - not in a section.');
                }
            }

            if (!empty($sectionNodes)) {
                $sectionNodes[$sectionNum]->addManLine($line);
                unset($parentSectionNode->manLines[$i]); // moved to subsection!
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

        if ($level === 2 && count($sectionNodes) === 0) {
            throw new Exception('No sections.');
        }

    }

}
