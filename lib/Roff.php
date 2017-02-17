<?php
declare(strict_types = 1);

class Roff
{

    static function parse(
        DOMElement $parentNode,
        array &$lines,
        $stopOnContent = false
    ): bool {

        while ($request = Request::getLine($lines)) {

            if ($stopOnContent) {

                // \c: Interrupt text processing (groff.7)
                // \fB\fP see KRATool.1
                if (in_array($request['raw_line'], ['\\c', '\\fB\\fP'])) {
                    array_shift($lines);
                    return true;
                }

                if (in_array($request['request'], ['SH', 'SS', 'TP', 'br', 'sp', 'ne', 'PP', 'RS', 'P', 'LP'])) {
                    return false;
                }

                if ($request['raw_line'] === '') {
                    array_shift($lines);
                    continue;
                }

            }

            $request = Request::getNextClass($lines);

            if (PreformattedOutput::handle($parentNode, $lines, $request)) {
                // Do nothing, but don't continue; as need $stopOnContent check below.
            } else {
                $newParent = $request['class']::checkAppend($parentNode, $lines, $request, $stopOnContent);
                if (!is_null($newParent)) {
                    $parentNode = $newParent;
                }
            }

            if ($stopOnContent && ($request['class'] === 'Block_Text' || $parentNode->textContent !== '')) {
                return true;
            }

        }

        return !$stopOnContent;

    }

}
