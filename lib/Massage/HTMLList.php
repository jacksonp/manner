<?php

declare(strict_types=1);

namespace Manner\Massage;

use DOMElement;
use DOMNode;
use DOMText;
use Manner\DOM;
use Manner\Node;

class HTMLList
{

    public const CHAR_PREFIXES = ['*', 'o', '·', '+', '-', '○'];

    private static function getBulletRegex(): string
    {
        return '~^\s*[' . preg_quote(implode('', HTMLList::CHAR_PREFIXES), '~') . ']\s~u';
    }

    public static function startsWithBullet(string $text): bool
    {
        return (bool)preg_match(self::getBulletRegex(), $text);
    }

    public static function pruneBulletChar(DOMElement $li)
    {
        $firstTextNode = self::getFirstNonEmptyTextNode($li);
        if ($firstTextNode) {
            $firstTextNode->textContent = preg_replace(self::getBulletRegex(), '', $firstTextNode->textContent);
        }
    }

    public static function removeLonePs(DOMElement $list)
    {
        $child = $list->firstChild;
        while ($child) {
            if ($child->childNodes->length === 1 && DOM::isTag($child->firstChild, 'p')) {
                DOM::extractContents($child, $child->firstChild);
                $child->removeChild($child->firstChild);
                Node::addClass($child, 'p');
            }
            $child = $child->nextSibling;
        }
    }

    public static function checkElementForLIs(DOMElement $li): bool
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

                $newLI = $li->ownerDocument->createElement('li');
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
            } elseif (DOM::isTag($child, 'p') && self::startsWithBullet($child->textContent)) {
                $foundInnerLI = true;

                $newLI = $li->ownerDocument->createElement('li');
                while ($li->firstChild) {
                    if ($li->firstChild === $child) {
                        Node::remove($child); // remove the <p>
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
