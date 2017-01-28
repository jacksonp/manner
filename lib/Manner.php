<?php


class Manner
{

    static function removeNode(DOMNode $from, $preserveChildren = true)
    {
        if ($preserveChildren) {
            $sibling = $from->firstChild;
            if ($sibling) { // ->firstChild is null is there isn't one
                do {
                    $next = $sibling->nextSibling;
                    $from->parentNode->insertBefore($sibling, $from);
                } while ($sibling = $next);
            }
        }
        $from->parentNode->removeChild($from);
    }

    private static function trimBrs(DOMElement $element): void
    {

        while (
            $firstChild = $element->firstChild and
            (
                ($firstChild->nodeType === XML_TEXT_NODE && trim($firstChild->textContent) === '') ||
                ($firstChild->nodeType === XML_ELEMENT_NODE && $firstChild->tagName === 'br')
            )
        ) {
            $element->removeChild($firstChild);
        }

        while (
            $nextSibling = $element->nextSibling and
            (
                ($nextSibling->nodeType === XML_TEXT_NODE && trim($nextSibling->textContent) === '') ||
                ($nextSibling->nodeType === XML_ELEMENT_NODE && $nextSibling->tagName === 'br')
            )
        ) {
            $element->parentNode->removeChild($nextSibling);
        }

        while (
            $lastChild = $element->lastChild and
            (
                ($lastChild->nodeType === XML_TEXT_NODE && trim($lastChild->textContent) === '') ||
                ($lastChild->nodeType === XML_ELEMENT_NODE && $lastChild->tagName === 'br')
            )
        ) {
            $element->removeChild($lastChild);
        }

        if ($element->tagName === 'div' && $element->childNodes->length === 1 && $element->firstChild->tagName === 'dl') {
            self::removeNode($element);
            return;
        }

        if (!$element->hasChildNodes()) {
            $element->parentNode->removeChild($element);
        }

    }

    // if we include <pre> below, we won't want to trim leading empty text elements above...
    private static function trimBrsRecursive(DOMElement $element)
    {
        $child = $element->firstChild;
        while ($child) {
            $nextChild = $child->nextSibling;
            if (
                $child->nodeType === XML_ELEMENT_NODE &&
                in_array($child->tagName, ['section', 'p', 'dd', 'dt', 'div', 'blockquote', 'dl'])
            ) {
                self::trimBrsRecursive($child);
            }
            $child = $nextChild;
        }
        self::trimBrs($element);
    }

    static function roffToDOM(array $fileLines, string $filePath): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->registerNodeClass('DOMElement', 'HybridNode');

        /** @var HybridNode $manPageContainer */
        $manPageContainer = $dom->createElement('body');
        $manPageContainer = $dom->appendChild($manPageContainer);

        $man = Man::instance();
        $man->reset();

        $strippedLines = Preprocessor::strip($fileLines);
        Roff::parse($manPageContainer, $strippedLines);
        self::trimBrsRecursive($manPageContainer);

        return $dom;
    }


    static function roffToHTML(array $fileLines, string $filePath, string $outputFile = null)
    {

        $dom  = self::roffToDOM($fileLines, $filePath);
        $html = $dom->saveHTML();

        $man = Man::instance();

        // Remove \& chars aka zero width space.
        $html   = str_replace(Char::ZERO_WIDTH_SPACE_HTML, '', $html);
        $title  = str_replace(Char::ZERO_WIDTH_SPACE_UTF8, '', $man->title);
        $extra1 = str_replace(Char::ZERO_WIDTH_SPACE_UTF8, '', $man->extra1);
        $extra2 = str_replace(Char::ZERO_WIDTH_SPACE_UTF8, '', $man->extra2);
        $extra3 = str_replace(Char::ZERO_WIDTH_SPACE_UTF8, '', $man->extra3);

//        echo $html; return;

        $manPageInfo = '<meta name="man-page-info" data-extra1="' . htmlspecialchars($extra1) . '" data-extra2="' . htmlspecialchars($extra2) . '" data-extra3="' . htmlspecialchars($extra3) . '">';

        if (is_null($outputFile)) {
            echo '<!DOCTYPE html>',
            '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">',
            $manPageInfo,
            '<title>', htmlspecialchars($title), '</title>',
            $html;
        } else {
            $fp = fopen($outputFile, 'w');
            fwrite($fp, '<!DOCTYPE html>');
            fwrite($fp, '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">');
            fwrite($fp, $manPageInfo);
            fwrite($fp, '<title>' . htmlspecialchars($title) . '</title>');
            fwrite($fp, $html);
            fclose($fp);
        }
    }

}
