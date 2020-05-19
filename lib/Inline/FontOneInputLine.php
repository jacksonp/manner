<?php

declare(strict_types=1);

namespace Manner\Inline;

use DOMElement;
use Manner\Block\Template;
use Manner\Block\Text;
use Manner\Blocks;
use Manner\Man;
use Manner\Node;
use Manner\PreformattedOutput;
use Manner\TextContent;

class FontOneInputLine implements Template
{

    public static function checkAppend(
      DOMElement $parentNode,
      array &$lines,
      array $request,
      $needOneLineOnly = false
    ): ?DOMElement {
        array_shift($lines);

        $man = Man::instance();

        $man->pushFont($request['request']);

        if (count($request['arguments']) === 0) {
            $man->addPostOutputCallback(
              function () use ($parentNode) {
                  Man::instance()->resetFonts();

                  return null;
              }
            );

            return null;
        } else {
            $parentNode = Blocks::getParentForText($parentNode);
            Text::addSpace($parentNode);
            TextContent::interpretAndAppendText($parentNode, implode(' ', $request['arguments']));
            if ($pre = Node::ancestor($parentNode, 'pre')) {
                PreformattedOutput::endInputLine($pre);
            }
            $man->resetFonts();

            return $parentNode;
        }
    }

}
