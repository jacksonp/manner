<?php

declare(strict_types=1);

namespace Manner\Block;

use DOMElement;
use Manner;
use Manner\PreformattedOutput;

class ad implements Template
{

    public static function checkAppend(
      DOMElement $parentNode,
      array &$lines,
      array $request,
      bool $needOneLineOnly = false
    ): ?DOMElement {
        array_shift($lines);

        if (in_array($request['arg_string'], ['', 'n', 'b']) && $parentNode->tagName === 'pre') {
            PreformattedOutput::reset();

            return $parentNode->parentNode;
        } else {
            return null;
        }
    }

}
