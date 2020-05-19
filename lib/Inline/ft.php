<?php

declare(strict_types=1);

namespace Manner\Inline;

use DOMElement;
use Manner\Block\Template;
use Manner\Man;

class ft implements Template
{

    public static function checkAppend(
      DOMElement $parentNode,
      array &$lines,
      array $request,
      $needOneLineOnly = false
    ): ?DOMElement {
        array_shift($lines);
        $man = Man::instance();

        // Return to previous font. Same as \f[] or \fP.
        if (count($request['arguments']) === 0 || $request['arguments'][0] === 'P') {
            $man->popFont();
        } else {
            $man->pushFont($request['arguments'][0]);
        }

        return null;
    }

}
