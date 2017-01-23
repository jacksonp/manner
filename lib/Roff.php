<?php


class Roff
{

    static function parse(
        DOMElement $parentNode,
        array &$lines,
        &$callerArguments = null,
        $stopOnContent = false
    ) {

        while ($request = Request::getLine($lines, 0, $callerArguments)) {

            $request['raw_line'] = Roff_Macro::applyReplacements($request['raw_line'], $callerArguments);

            $request = Request::getNextClass($lines);
//            var_dump($request['class']);

            $newI = $request['class']::checkAppend($parentNode, $lines, $request['arguments'], $request['request'],
                $stopOnContent);
            if ($newI === false) {
                throw new Exception('"' . $lines[0] . '" Roff::parse() could not handle it.');
            }

            if ($newI) { // could be 0
                array_splice($lines, 0, $newI);
            }

            if ($stopOnContent && $parentNode->textContent !== '') {
                break;
            }

        }

        return 0;

    }

}
