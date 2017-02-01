<?php


class Block_Section implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        $dom = $parentNode->ownerDocument;

        $body    = $parentNode->ancestor('body');
        $section = $dom->createElement('section');

        if ($request['request'] === 'SH') {
            $section     = $body->appendChild($section);
            $headingNode = $dom->createElement('h2');
        } else {
            if ($body->lastChild && $body->lastChild->tagName === 'section') {
                $superSection = $body->lastChild;
            } else {
                // Make a new h2 level container section:
                $superSection = $body->appendChild($dom->createElement('section'));
            }
            $section     = $superSection->appendChild($section);
            $headingNode = $dom->createElement('h3');
        }

        $headingNode = $section->appendChild($headingNode);

        if (count($request['arguments']) === 0) {
            $gotContent = Roff::parse($headingNode, $lines, true);
            if (!$gotContent) {
                $section->parentNode->removeChild($section);
                return null;
            }
        } else {
//            if ($request['raw_arg_string'] === '\\ ') {
//                $section->parentNode->removeChild($section);
//                return null;
//            }
            $sectionHeading = implode(' ', $request['arguments']);
            TextContent::interpretAndAppendText($headingNode, $sectionHeading);
        }

        $headingNode->lastChild->textContent = Util::rtrim($headingNode->lastChild->textContent);

        // Skip sections with empty headings
        if (trim($headingNode->textContent) === '') {
            $section->parentNode->removeChild($section);
            return null;
        }

        return $section;

    }

}
