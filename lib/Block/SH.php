<?php


class Block_SH implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        int $i,
        ?array $arguments = null,
        ?string $request = null,
        $needOneLineOnly = false
    ) {

        $dom = $parentNode->ownerDocument;

        $headingNode = $dom->createElement('h2');

        if (count($arguments) === 0) {
            if ($i === count($lines) - 1 || Request::getClass($lines, $i + 1)['class'] === 'Block_SH') {
                return $i;
            }
            // Text for subheading is on next line.
            $sectionHeading = $lines[++$i];
            if (in_array($sectionHeading, Block_Section::skipSectionNameLines)) {
                return $i;
            }
            $sectionHeading = [$sectionHeading];
            Roff::parse($headingNode, $sectionHeading);
        } else {
            $sectionHeading = implode(' ', $arguments);
            TextContent::interpretAndAppendText($headingNode, $sectionHeading);
        }

        // We skip empty .SH macros
        if (trim($headingNode->textContent) === '') {
            return $i;
        }

        $headingNode->lastChild->textContent = Util::rtrim($headingNode->lastChild->textContent);

        $section = $dom->createElement('section');
        $section->appendChild($headingNode);

        $blockLines = [];
        for ($i = $i + 1; $i < count($lines); ++$i) {
            $request = Request::getClass($lines, $i);
            if ($request['class'] === 'Block_SH') {
                if (
                    (count($request['arguments']) === 1 && $request['arguments'][0] === '\\ ') ||
                    (count($request['arguments']) === 0 &&
                        $i < count($lines) - 1 &&
                        in_array($lines[$i + 1], Block_Section::skipSectionNameLines))
                ) {
                    continue;
                }
                --$i;
                break;
            } else {
                $blockLines[] = $lines[$i];
            }
        }

        Blocks::trim($blockLines);
        Roff::parse($section, $blockLines);
        $parentNode->appendBlockIfHasContent($section);

        return $i;

    }

}
