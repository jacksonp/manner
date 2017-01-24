<?php


class Roff
{

    static function parse(
        DOMElement $parentNode,
        array &$lines,
        &$callerArguments = null,
        $stopOnContent = false
    ): void {

        while ($request = Request::getLine($lines, $callerArguments)) {

            // \c: Interrupt text processing (groff.7)
            // \fB\fP see KRATool.1
            if ($stopOnContent && in_array($request['raw_line'], ['\\c', '\\fB\\fP'])) {
                array_shift($lines);
                break;
            }

            $request['raw_line'] = Roff_Macro::applyReplacements($request['raw_line'], $callerArguments);

            $request = Request::getNextClass($lines);
//            var_dump($request['class']);

            if ($stopOnContent && in_array($request['request'], ['SH', 'SS'])) {
                break;
            }

            $newParent = $request['class']::checkAppend($parentNode, $lines, $request, $stopOnContent);
            if (!is_null($newParent)) {
                $parentNode = $newParent;
            }

            if ($stopOnContent && ($request['class'] === 'Block_Text' || $parentNode->textContent !== '')) {
                break;
            }

        }

    }

}
