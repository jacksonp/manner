<?php

declare(strict_types=1);

namespace Manner\Massage;

use DOMElement;
use DOMXPath;
use Exception;
use Manner\Blocks;
use Manner\DOM;
use Manner\Indentation;

class Remap
{

    /**
     * @param DOMXPath $xpath
     * @throws Exception
     */
    public static function doAll(DOMXPath $xpath)
    {
        $divs = $xpath->query('//div[@remap]');
        /** @var DOMElement $div */
        /** @var DOMElement $p */
        foreach ($divs as $div) {
            if ($div->getAttribute('remap') === 'IP') {
                $indentVal = Indentation::get($div);

                $remapChild = $div->firstChild;
                if ($remapChild) {
                    $next = false;
                    do {
                        if (DOM::isTag($remapChild, BLOCKS::BLOCK_ELEMENTS)) {
                            $next = $remapChild->nextSibling;
                            $remapChild->removeAttribute('implicit');
                            $indentVal && Indentation::add($remapChild, $indentVal);
                            $div->parentNode->insertBefore($remapChild, $div);
                        } else {
                            $p = $div->ownerDocument->createElement('p');
                            $p = $div->parentNode->insertBefore($p, $div);
                            $indentVal && Indentation::add($p, $indentVal);
                            while ($remapChild && !DOM::isTag($remapChild, BLOCKS::BLOCK_ELEMENTS)) {
                                $next = $remapChild->nextSibling;
                                $p->appendChild($remapChild);
                                $remapChild = $next;
                            }
                        }
                    } while ($remapChild = $next);
                }

                $div->parentNode->removeChild($div);
            } else {
                throw new Exception('Unexpected value for remap.');
            }
        }
    }
}
