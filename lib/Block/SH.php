<?php


class Block_SH implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        ?array $request = null,
        $needOneLineOnly = false
    ): ?DOMElement {

        array_shift($lines);

        $dom = $parentNode->ownerDocument;

        $headingNode = $dom->createElement('h2');

        if (count($request['arguments']) === 0) {
            if (count($lines) === 0 || Request::getLine($lines)['request'] === 'SH') {
                return null;
            }
            // Text for subheading is on next line.
            $sectionHeading = array_shift($lines);
            if (in_array($sectionHeading, Block_Section::skipSectionNameLines)) {
                return null;
            }
            $sectionHeading = [$sectionHeading];
            Roff::parse($headingNode, $sectionHeading);
        } else {
            $sectionHeading = implode(' ', $request['arguments']);
            TextContent::interpretAndAppendText($headingNode, $sectionHeading);
        }

        // We skip empty .SH macros
        if (trim($headingNode->textContent) === '') {
            return null;
        }

        $headingNode->lastChild->textContent = Util::rtrim($headingNode->lastChild->textContent);

        $section = $dom->createElement('section');
        $section->appendChild($headingNode);

        while ($parentNode->tagName !== 'body') {
            if (!$parentNode->parentNode) {
                throw new Exception('Could not find parent with tag body.');
            }
            $parentNode = $parentNode->parentNode;
        }

        $section = $parentNode->appendChild($section);
//        Roff::parse($section, $lines);

        return $section;

    }

}
