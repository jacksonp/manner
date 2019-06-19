<?php
declare(strict_types=1);

/**
 * See https://www.mankier.com/7/groff_man#Macros_to_Describe_Command_Synopses
 */
class Inline_OP implements Block_Template
{

    /**
     * @param DOMElement $parentNode
     * @param array $lines
     * @param array $request
     * @param bool $needOneLineOnly
     * @return DOMElement|null
     * @throws Exception
     */
    static function checkAppend(
      DOMElement $parentNode,
      array &$lines,
      array $request,
      $needOneLineOnly = false
    ): ?DOMElement {

        array_shift($lines);
        $dom        = $parentNode->ownerDocument;
        $parentNode = Blocks::getParentForText($parentNode);

        $parentNode->appendChild(new DOMText('['));
        /* @var DomElement $strong */
        $strong = $parentNode->appendChild($dom->createElement('strong'));
        TextContent::interpretAndAppendText($strong, $request['arguments'][0]);
        if (count($request['arguments']) > 1) {
            $parentNode->appendChild(new DOMText(' '));
            /* @var DomElement $em */
            $em = $parentNode->appendChild($dom->createElement('em'));
            TextContent::interpretAndAppendText($em, $request['arguments'][1]);
        }
        $parentNode->appendChild(new DOMText('] '));

        return $parentNode;
    }

}
