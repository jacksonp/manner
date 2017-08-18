<?php
declare(strict_types=1);

class Inline_FontOneInputLine implements Block_Template
{

    static function checkAppend(
        HybridNode $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        if (
            count($request['arguments']) === 0 &&
            count($lines) &&
            (Blocks::lineEndsBlock(Request::getLine($lines), $lines))
        ) {
            return null; // Skip
        }

        if (count($request['arguments']) === 1 && $request['arguments'][0] === '') {
            return null; // bug in man page, see e.g. basic_ldap_auth.8: .B "\"uid\=%s\""
        }

        $parentNode = Blocks::getParentForText($parentNode);
        Block_Text::addSpace($parentNode);
        $dom = $parentNode->ownerDocument;

        $node = $parentNode;

        switch ($request['request']) {
            case 'R':
                break;
            case 'I':
                if (!Node::isOrInTag($parentNode, 'em')) {
                    $node = $parentNode->appendChild($dom->createElement('em'));
                }
                break;
            case 'B':
                if (!Node::isOrInTag($parentNode, 'strong')) {
                    $node = $parentNode->appendChild($dom->createElement('strong'));
                }
                break;
            case 'SB':
                if (!Node::isOrInTag($parentNode, 'strong')) {
                    $node = $parentNode->appendChild($dom->createElement('strong'));
                }
                if (!Node::isOrInTag($parentNode, 'small')) {
                    $node = $parentNode->appendChild($dom->createElement('small'));
                }
                break;
            case 'SM':
                if (!Node::isOrInTag($parentNode, 'small')) {
                    $node = $parentNode->appendChild($dom->createElement('small'));
                }
                break;
            default:
                throw new Exception('switch is exhaustive.');
        }

        if (count($request['arguments']) === 0) {
            $gotContent = Roff::parse($node, $lines, true);
            if (!$gotContent) {
                if ($node->tagName !== $parentNode->tagName) {
                    $parentNode->removeChild($node);
                }
                return null;
            }
        } else {
            TextContent::interpretAndAppendText($node, implode(' ', $request['arguments']));
            if ($pre = Node::ancestor($parentNode, 'pre')) {
                PreformattedOutput::endInputLine($pre);
            }
        }

        return $parentNode;

    }

}
