<?php


class Block_SS implements Block_Template
{

    static function endSubsection($requestName)
    {
        return in_array($requestName, ['SS', 'SH']);
    }

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $request = null,
        $needOneLineOnly = false
    ): bool {

        array_shift($lines);

        $dom = $parentNode->ownerDocument;

        $headingNode = $dom->createElement('h3');

        if (count($request['arguments']) === 0) {
            if (count($lines) === 0 || self::endSubsection(Request::getLine($lines, 0)['request'])) {
                return true;
            }
            // Text for subheading is on next line.
            $sectionHeading = array_shift($lines);
            if (in_array($sectionHeading, Block_Section::skipSectionNameLines)) {
                // Skip $line to work around bugs in man pages, e.g. xorrecord.1, bdh.3
                return true;
            }
            $sectionHeading = [$sectionHeading];
            Roff::parse($headingNode, $sectionHeading);
        } else {
            $sectionHeading = ltrim(implode(' ', $request['arguments']));
            TextContent::interpretAndAppendText($headingNode, $sectionHeading);
        }

        // We skip empty .SS macros
        if (trim($headingNode->textContent) === '') {
            return true;
        }

        $headingNode->lastChild->textContent = Util::rtrim($headingNode->lastChild->textContent);

        $subsection = $dom->createElement('section');
        $subsection->appendChild($headingNode);

        $blockLines = [];
        while ($request = Request::getLine($lines)) {
            if (self::endSubsection($request['request'])) {
                break;
            } else {
                $blockLines[] = array_shift($lines);
            }
        }

        Blocks::trim($blockLines);
        Roff::parse($subsection, $blockLines);
        $parentNode->appendBlockIfHasContent($subsection);

        return true;

    }

}
