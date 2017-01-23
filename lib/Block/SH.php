<?php


class Block_SH implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $request = null,
        $needOneLineOnly = false
    ): bool {

        array_shift($lines);

        $dom = $parentNode->ownerDocument;

        $headingNode = $dom->createElement('h2');

        if (count($request['arguments']) === 0) {
            if (count($lines) === 0 || Request::getLine($lines)['request'] === 'SH') {
                return true;
            }
            // Text for subheading is on next line.
            $sectionHeading = array_shift($lines);
            if (in_array($sectionHeading, Block_Section::skipSectionNameLines)) {
                return true;
            }
            $sectionHeading = [$sectionHeading];
            Roff::parse($headingNode, $sectionHeading);
        } else {
            $sectionHeading = implode(' ', $request['arguments']);
            TextContent::interpretAndAppendText($headingNode, $sectionHeading);
        }

        // We skip empty .SH macros
        if (trim($headingNode->textContent) === '') {
            return true;
        }

        $headingNode->lastChild->textContent = Util::rtrim($headingNode->lastChild->textContent);

        $section = $dom->createElement('section');
        $section->appendChild($headingNode);

        $blockLines = [];
        while ($request = Request::getLine($lines)) {
            if ($request['request'] === 'SH') {
                if (
                    (count($request['arguments']) === 1 && $request['arguments'][0] === '\\ ') ||
                    (count($request['arguments']) === 0 &&
                        count($lines) > 1 &&
                        in_array($lines[1], Block_Section::skipSectionNameLines))
                ) {
                    continue;
                }
                break;
            } else {
                $blockLines[] = array_shift($lines);
            }
        }

        Blocks::trim($blockLines);
        Roff::parse($section, $blockLines);
        $parentNode->appendBlockIfHasContent($section);

        return true;

    }

}
