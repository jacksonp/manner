<?php
declare(strict_types=1);

class Block_Section implements Block_Template
{

    static function checkAppend(
        DOMElement $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        $dom = $parentNode->ownerDocument;

        Man::instance()->resetIndentationToDefault();

        $body = Node::ancestor($parentNode, 'body');
        /* @var DomElement $section */
        $section = $dom->createElement('section');
        /* @var DomElement $headingNode */
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
            $sectionHeading = implode(' ', $request['arguments']);
            TextContent::interpretAndAppendText($headingNode, $sectionHeading);
        }

        if ($headingNode->lastChild) {
            // We don't want empty sections with &nbsp; as heading. See e.g. ntptime.8
            $headingNode->lastChild->textContent = rtrim(
                $headingNode->lastChild->textContent,
                " \t\n\r\0\x0B" . html_entity_decode('&nbsp;')
            );
        }

        // Skip sections with empty headings
        if (trim($headingNode->textContent) === '') {
            $section->parentNode->removeChild($section);
            return null;
        }

        return $section;

    }

}
