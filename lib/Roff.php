<?php


class Roff
{

    static function parse(
        DOMElement $parentNode,
        array &$lines,
        $stopOnContent = false
    ): bool {

        while ($request = Request::getLine($lines)) {

            // \c: Interrupt text processing (groff.7)
            // \fB\fP see KRATool.1
            if ($stopOnContent && in_array($request['raw_line'], ['\\c', '\\fB\\fP'])) {
                array_shift($lines);
                return true;
            }

            $request = Request::getNextClass($lines);
//            var_dump($request['class']);

            if ($stopOnContent && in_array($request['request'], ['SH', 'SS', 'TP', 'br', 'sp', 'ne', 'PP', 'RS', 'P', 'LP'])) {
                return false;
            }

            if (Block_Preformatted::handle($parentNode, $lines, $request)) {
                continue;
            }

            $newParent = $request['class']::checkAppend($parentNode, $lines, $request, $stopOnContent);
            if (!is_null($newParent)) {
                $parentNode = $newParent;
            }

            if ($stopOnContent && ($request['class'] === 'Block_Text' || $parentNode->textContent !== '')) {
                return true;
            }

        }

        return !$stopOnContent;

    }

}
