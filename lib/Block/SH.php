<?php


class Block_SH
{

    static function checkAppend(HybridNode $parentNode, array $lines, int $i, array $arguments)
    {

        $dom      = $parentNode->ownerDocument;
        $numLines = count($lines);

        $headingNode = $dom->createElement('h2');

        if (count($arguments) === 0) {
            if ($i === $numLines - 1 || Request::getClass($lines, $i + 1)['class'] === 'Block_SH') {
                return $i;
            }
            // Text for subheading is on next line.
            $sectionHeading = $lines[++$i];
            if (in_array($sectionHeading, Block_Section::skipSectionNameLines)) {
                return $i;
            }
            Blocks::handle($headingNode, [$sectionHeading]);
        } else {
            $sectionHeading = implode(' ', $arguments);
            TextContent::interpretAndAppendText($headingNode, $sectionHeading);
        }

        // We skip empty .SH macros
        if (trim($headingNode->textContent) === '') {
            return $i;
        }

        $headingNode->lastChild->textContent = Util::rtrim($headingNode->lastChild->textContent);

        $section = $dom->createElement('div');
        $section->setAttribute('class', 'section');
        $section->appendChild($headingNode);

        $blockLines = [];
        for ($i = $i + 1; $i < $numLines; ++$i) {
            $line    = $lines[$i];
            $request = Request::getClass($lines, $i);
            if ($request['class'] === 'Block_SH') {
                if (
                  (count($request['arguments']) === 1 && $request['arguments'][0] === '\\ ') ||
                  (count($request['arguments']) === 0 &&
                    $i < $numLines - 1 &&
                    in_array($lines[$i + 1], Block_Section::skipSectionNameLines))
                ) {
                    continue;
                }
                --$i;
                break;
            } else {
                $blockLines[] = $line;
            }
        }

        Blocks::handle($section, $blockLines);
        $parentNode->appendBlockIfHasContent($section);

        return $i;

    }

}
