<?php


class Block_SH implements Block_Template
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

        $section = $dom->createElement('section');
        $section = $parentNode->ancestor('body')->appendChild($section);
        $headingNode = $dom->createElement('h2');
        $headingNode = $section->appendChild($headingNode);

        if (count($request['arguments']) === 0) {
            $gotContent = Roff::parse($headingNode, $lines, true);
            if (!$gotContent) {
                $section->parentNode->removeChild($section);
                return null;
            }
        } else {
            if ($request['raw_arg_string'] === '\\ ') {
                return null;
            }
            $sectionHeading = implode(' ', $request['arguments']);
            TextContent::interpretAndAppendText($headingNode, $sectionHeading);
        }

        // We skip empty .SH macros
        if (trim($headingNode->textContent) === '') {
            return null;
        }

        $headingNode->lastChild->textContent = Util::rtrim($headingNode->lastChild->textContent);


        return $section;

    }

}
