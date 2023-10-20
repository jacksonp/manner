<?php

declare(strict_types=1);

namespace Manner\Inline;

use DOMElement;
use DOMException;
use DOMText;
use Manner\Block\Template;
use Manner\Block\Text;
use Manner\Blocks;
use Manner\TextContent;

/**
 * https://www.mankier.com/7/groff_man#Description-Hyperlink_macros
 */
class MR implements Template
{

    /**
     * @throws DOMException
     */
    public static function checkAppend(
      DOMElement $parentNode,
      array &$lines,
      array $request,
      bool $needOneLineOnly = false
    ): ?DOMElement {
        array_shift($lines);

        $dom = $parentNode->ownerDocument;

        $parentNode = Blocks::getParentForText($parentNode);

        $anchor = $dom->createElement('a');
        Text::addSpace($parentNode);
        $parentNode->appendChild($anchor);

        $topic         = TextContent::interpretString($request['arguments'][0]);
        $manualSection = TextContent::interpretString($request['arguments'][1]);

        $anchor->appendChild(new DOMText($topic . '(' . $manualSection . ')'));
        $anchor->setAttribute('href', '/' . $manualSection . '/' . $topic);

        // Trailing text (usually punctuation)
        if (count($request['arguments']) > 2) {
            TextContent::interpretAndAppendText($parentNode, $request['arguments'][2]);
        }


        return $parentNode;
    }

}
