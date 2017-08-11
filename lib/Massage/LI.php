<?php
declare(strict_types=1);

class Massage_LI
{

    static function tidy(DOMElement $li)
    {

        while ($li->lastChild && (Node::isTextAndEmpty($li->lastChild) || DOM::isTag($li->lastChild, 'br'))) {
            $li->removeChild($li->lastChild);
        }

        if (trim($li->textContent) === '') {
            $li->parentNode->removeChild($li);
        }

    }

}
