<?php


class Roff
{

    static function parse(
        DOMElement $parentNode,
        array &$lines,
        &$callerArguments = null,
        $stopOnContentLineAfter = false
    ) {

        if ($stopOnContentLineAfter !== false) {
            $i             = $stopOnContentLineAfter;
            $stopOnContent = true;
        } else {
            $i             = 0;
            $stopOnContent = false;
        }

//        var_dump($lines);

        for (; $i < count($lines) && !($stopOnContent and $parentNode->textContent !== ''); ++$i) {

//            echo $i, "\t", $lines[$i], PHP_EOL;
//            var_dump(array_slice($lines, 0, 5));

            $request = Request::getLine($lines, $i, $callerArguments);

            $result = Roff_Skipped::checkEvaluate($lines, $i);
            if ($result !== false) {
                array_splice($lines, $i, $result['i'] + 1 - $i);
                --$i;
                continue;
            }

            $lines[$i] = Roff_Macro::applyReplacements($lines[$i], $callerArguments);

            $request = Request::getClass($lines, $i);
//            var_dump($request['class']);

            $newI = $request['class']::checkAppend($parentNode, $lines, $i, $request['arguments'], $request['request'],
                $stopOnContent);
            if ($newI === false) {
//            var_dump(array_slice($lines, $i - 5, 10));
//            var_dump($lines);
                throw new Exception('"' . $lines[$i] . '" Roff::parse() could not handle it.');
            }

            $i = $newI;

        }

        return $i;

    }

}
