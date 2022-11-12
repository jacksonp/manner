<?php
declare(strict_types=1);

namespace Manner;

use DOMElement;
use Exception;

class Roff
{

    /**
     * @param DOMElement $parentNode
     * @param array $lines
     * @param bool $stopOnContent
     * @return bool
     * @throws Exception
     */
    public static function parse(
      DOMElement $parentNode,
      array &$lines,
      bool $stopOnContent = false
    ): bool {
        while ($request = Request::getLine($lines)) {
            if ($stopOnContent) {
                // \c: Interrupt text processing (groff.7)
                if (in_array($request['raw_line'], ['\\c'])) {
                    array_shift($lines);

//                    Man::instance()->runPostOutputCallbacks();
                    return true;
                }

                if (in_array($request['request'], ['SH', 'SS', 'TP', 'br', 'sp', 'ne', 'PP', 'RS', 'RE', 'P', 'LP'])) {
                    return false;
                }

                if ($request['raw_line'] === '') {
                    array_shift($lines);
                    continue;
                }
            }

            $request['class'] = Request::getClass($request, $lines);

            if ($newParent = PreformattedOutput::handle($parentNode, $lines, $request)) {
                // NB: still need $stopOnContent check below (so no continue)
                if ($newParent instanceof DOMElement) {
                    $parentNode = $newParent;
                }
            } else {
                /** @var Block\Template $class */
                $class = $request['class'];
                $newParent = $class::checkAppend($parentNode, $lines, $request, $stopOnContent);
                if (!is_null($newParent)) {
                    $parentNode = $newParent;
                }
            }

            if ($request['class'] === '\Manner\Block\Text' || $parentNode->textContent !== '') {
                if ($stopOnContent) {
                    return true;
                }
                if ($request['class'] !== '\Manner\Inline\FontOneInputLine') { // TODO: hack? fix?
                    $newParent = Man::instance()->runPostOutputCallbacks();
                    if (!is_null($newParent)) {
                        $parentNode = $newParent;
                    }
                }
            }
        }

        return !$stopOnContent;
    }

}
