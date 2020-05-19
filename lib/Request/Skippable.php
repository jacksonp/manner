<?php

declare(strict_types=1);

namespace Manner\Request;

use DOMElement;
use Manner\Block\Template;

class Skippable implements Template
{

    public static function checkAppend(
      DOMElement $parentNode,
      array &$lines,
      array $request,
      $needOneLineOnly = false
    ): ?DOMElement {
        array_shift($lines);

        return null;
    }

}
