<?php


class Roff
{

    static function parse(
        DOMElement $parentNode,
        array &$lines,
        &$callerArguments = null,
        $stopOnContent = false
    ) {

        while (count($lines) && (!$stopOnContent || $parentNode->textContent === '')) {

            $request = Request::getLine($lines, 0, $callerArguments);
            if (!count($lines)) {
                // e.g. if last request was a comment: stop getClass below causing an error.
                // Bit of a hack, see instead about bringing not doing both getLine and getClass...
                break;
            }

            if (Roff_Skipped::skip($request)) {
                array_shift($lines);
                continue;
            }

            $request['raw_line'] = Roff_Macro::applyReplacements($request['raw_line'], $callerArguments);

            $request = Request::getClass($lines, 0);
//            var_dump($request['class']);

            $newI = $request['class']::checkAppend($parentNode, $lines, $request['arguments'], $request['request'],
                $stopOnContent);
            if ($newI === false) {
                throw new Exception('"' . $lines[0] . '" Roff::parse() could not handle it.');
            }

            if ($newI) { // could be 0
                array_splice($lines, 0, $newI);
            }

        }

        return 0;

    }

}
