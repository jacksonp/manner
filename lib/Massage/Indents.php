<?php
declare(strict_types=1);

class Massage_Indents
{

    /**
     * @param DOMXPath $xpath
     * @throws Exception
     */
    public static function recalc(DOMXPath $xpath)
    {

        $divs = $xpath->query('//div[@left-margin="0"]');
        foreach ($divs as $div) {
            // See tests/warnquota.conf.5
            if (DOM::isTag($div->previousSibling, 'p') && DOM::isTag($div->firstChild, 'p')) {
                $div->previousSibling->appendChild($div->ownerDocument->createElement('br'));
                DOM::extractContents($div->previousSibling, $div->firstChild);
                Node::remove($div->firstChild);
            }
            Node::remove($div);
        }

        $divs = $xpath->query('//div[@left-margin]');
        foreach ($divs as $div) {

            $leftMargin = (int)$div->getAttribute('left-margin');

            $parentNode = $div->parentNode;

            while ($parentNode) {
                if ($parentNode instanceof DOMDocument || $parentNode->tagName === 'div') {
                    break;
                }
                if (Indentation::isSet($parentNode)) {
                    $leftMargin -= Indentation::get($parentNode);
                }
                $parentNode = $parentNode->parentNode;
            }
            Indentation::set($div, $leftMargin);
            $div->removeAttribute('left-margin');

        }
    }
}
