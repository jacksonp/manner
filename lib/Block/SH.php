<?php


class Block_SH implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $arguments = null,
        ?string $request = null,
        $needOneLineOnly = false
    ) {

        array_shift($lines);

        $dom = $parentNode->ownerDocument;

        $headingNode = $dom->createElement('h2');

        if (count($arguments) === 0) {
            if (count($lines) === 0 || Request::getNextClass($lines)['class'] === 'Block_SH') {
                return 0;
            }
            // Text for subheading is on next line.
            $sectionHeading = array_shift($lines);
            if (in_array($sectionHeading, Block_Section::skipSectionNameLines)) {
                return 0;
            }
            $sectionHeading = [$sectionHeading];
            Roff::parse($headingNode, $sectionHeading);
        } else {
            $sectionHeading = implode(' ', $arguments);
            TextContent::interpretAndAppendText($headingNode, $sectionHeading);
        }

        // We skip empty .SH macros
        if (trim($headingNode->textContent) === '') {
            return 0;
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

        return 0;

    }

}
