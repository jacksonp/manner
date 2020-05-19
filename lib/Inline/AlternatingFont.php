<?php

declare(strict_types=1);

namespace Manner\Inline;

use DOMElement;
use Exception;
use Manner\Block\Template;
use Manner\Block\Text;
use Manner\Blocks;
use Manner\Man;
use Manner\Request;
use Manner\TextContent;

class AlternatingFont implements Template
{

    /**
     * @param DOMElement $parentNode
     * @param array $lines
     * @param array $request
     * @param bool $needOneLineOnly
     * @return DOMElement|null
     * @throws Exception
     */
    public static function checkAppend(
      DOMElement $parentNode,
      array &$lines,
      array $request,
      $needOneLineOnly = false
    ): ?DOMElement {
        array_shift($lines);
        $parentNode = Blocks::getParentForText($parentNode);
        $man        = Man::instance();
        Text::addSpace($parentNode);

        foreach ($request['arguments'] as $bi => $bit) {
            $requestCharIndex = $bi % 2;
            if (!isset($request['request'][$requestCharIndex])) {
                throw new Exception(
                  $lines[0] . ' command ' . $request['request'] . ' has nothing at index ' . $requestCharIndex
                );
            }
            // Re-massage the line:
            // in a man page the AlternatingFont macro argument would become the macro argument to a .ft call and have
            // double backslashes transformed twice (I think)
            $bit = Request::massageLine($bit);
            $man->pushFont($request['request'][$requestCharIndex]);
            TextContent::interpretAndAppendText($parentNode, $bit);
            $man->resetFonts();
        }

        return $parentNode;
    }

}
