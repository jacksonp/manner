<?php
declare(strict_types=1);

class Massage_UL
{

    const CHAR_PREFIXES = ['*', 'o', '·', '+', '-'];

    private static function getBulletRegex(): string
    {
        return '~^\s*[' . preg_quote(implode('', Massage_UL::CHAR_PREFIXES), '~') . '](\s|$)~u';
    }

    static function startsWithBullet(string $text)
    {
        return preg_match(self::getBulletRegex(), $text);
    }

    static function pruneBulletChar(DOMElement $li)
    {
        $firstTextNode = self::getFirstNonEmptyTextNode($li);
        if ($firstTextNode) {
            $firstTextNode->textContent = preg_replace(self::getBulletRegex(), '', $firstTextNode->textContent);
        }
    }

    static function checkElementForLIs(DOMElement $li): bool
    {

        $foundInnerLI = false;

        $child = $li->firstChild;

        do {

            if (
                DOM::isTag($child, 'br') &&
                $child->nextSibling &&
                ($child->nextSibling instanceof DOMText || DOM::isInlineElement($child->nextSibling)) &&
                self::startsWithBullet($child->nextSibling->textContent)
            ) {

                $foundInnerLI = true;
                $newLI        = $li->ownerDocument->createElement('li');
                while ($li->firstChild) {
                    if ($li->firstChild === $child) {
                        $li->removeChild($child); // remove the <br>
                        break;
                    }
                    $newLI->appendChild($li->firstChild);
                }
                $li->parentNode->insertBefore($newLI, $li);
                self::pruneBulletChar($li);
                $child = $li->firstChild;
            } else {
                $child = $child->nextSibling;
            }
        } while ($child);

        return $foundInnerLI;

    }

    private static function getFirstNonEmptyTextNode(?DOMNode $domNode): ?DOMText
    {

        if ($domNode instanceof DOMText) {
            if (trim($domNode->textContent) === '') {
                $domNode->parentNode->removeChild($domNode);
                return null;
            }
            return $domNode;
        }

        foreach ($domNode->childNodes as $node) {
            if ($el = self::getFirstNonEmptyTextNode($node)) {
                return $el;
            }
        }

        return null;

    }

}
