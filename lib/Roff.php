<?php


class Roff
{

    static function parse(
        DOMElement $parentNode,
        array &$lines,
        &$callerArguments = null,
        $stopOnContent = false
    ): void {

        while ($request = Request::getLine($lines, 0, $callerArguments)) {

            $request['raw_line'] = Roff_Macro::applyReplacements($request['raw_line'], $callerArguments);

            $request = Request::getNextClass($lines);
//            var_dump($request['class']);

            $used = $request['class']::checkAppend($parentNode, $lines, $request, $stopOnContent);
            if (!$used) {
                throw new Exception('"' . $request['raw_line'] . '" Roff::parse() could not handle it.');
            }

            if ($stopOnContent && $parentNode->textContent !== '') {
                break;
            }

        }

    }

}
