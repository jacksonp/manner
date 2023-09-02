<?php

declare(strict_types=1);

namespace Manner\Massage;

use DOMElement;
use DOMText;
use Manner\Node;
use Manner\Text;

class PRE
{

    public static function tidy(DOMElement $el): void
    {
        while ($el->lastChild && Node::isTextAndEmpty($el->lastChild)) {
            $el->removeChild($el->lastChild);
        }

        if (!$el->lastChild) {
            $el->parentNode->removeChild($el);
            return;
        }

        if ($el->lastChild->nodeType === XML_TEXT_NODE) {
            $el->replaceChild(new DOMText(rtrim($el->lastChild->textContent)), $el->lastChild);
        }

        if (Text::trimAndRemoveZWSUTF8($el->textContent) === '') {
            $el->parentNode->removeChild($el);
        }
    }

}
