<?php
declare(strict_types=1);

class Inline_LinkEnd implements Block_Template
{

    static function checkAppend(
        DOMElement $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);
        $anchorNode = Node::ancestor($parentNode, 'a');
        if (is_null($anchorNode)) {
            return null;
        }
        $parentNode  = $anchorNode->parentNode;
        $punctuation = trim($request['arg_string']);

        $removed = false;

        if ($anchorNode->getAttribute('href') === '') {
            $href = Inline_Link::getValidHREF($anchorNode->textContent);
            if ($href) {
                $anchorNode->setAttribute('href', $href);
            } else {
                Node::remove($anchorNode);
                $removed = true;
            }
        }

        if (!$removed) {
            if ($anchorNode->textContent === '') {
                $urlAsText = $anchorNode->getAttribute('href');
                $urlAsText = preg_replace('~^mailto:~', '', $urlAsText);
                $anchorNode->appendChild(new DOMText($urlAsText));
            }
        }

        if ($punctuation !== '') {
            $parentNode->appendChild(new DOMText($punctuation));
        }

        return $parentNode;

    }

}
