<?php

declare(strict_types=1);

namespace Manner\Block;

use DOMElement;
use Manner\Node;
use Manner\PreformattedOutput;

class EndPreformatted implements Template
{

    public static function checkAppend(
      DOMElement $parentNode,
      array &$lines,
      array $request,
      $needOneLineOnly = false
    ): ?DOMElement {
        array_shift($lines);

        if ($pre = Node::ancestor($parentNode, 'pre')) {
            PreformattedOutput::reset();

            return $pre->parentNode;
        } else {
            return null;
        }
    }

}
