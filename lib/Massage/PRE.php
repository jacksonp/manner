<?php
declare(strict_types=1);

class Massage_PRE
{

    static function tidy(DOMElement $el)
    {

        while ($el->lastChild && Node::isTextAndEmpty($el->lastChild)) {
            $el->removeChild($el->lastChild);
        }

        if ($el->lastChild->nodeType === XML_TEXT_NODE) {
            $el->replaceChild(new DOMText(rtrim($el->lastChild->textContent)), $el->lastChild);
        }

    }

}