<?php

declare(strict_types=1);

namespace Manner\Massage;

use DOMElement;
use Manner\DOM;
use Manner\Node;

class LI
{

    public static function tidy(DOMElement $li): void
    {
        while ($li->lastChild && (Node::isTextAndEmpty($li->lastChild) || DOM::isTag($li->lastChild, 'br'))) {
            $li->removeChild($li->lastChild);
        }

        if (trim($li->textContent) === '') {
            $li->parentNode->removeChild($li);
        }
    }

}
