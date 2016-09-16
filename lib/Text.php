<?php


class Text
{

    static function applyRoffClasses(DOMElement $parentNode, array &$lines, &$callerArguments = null): array
    {

        $man = Man::instance();

        for ($i = 0; $i < count($lines); ++$i) {

//            echo $i, "\t", $lines[$i], PHP_EOL;
//            var_dump(array_slice($lines, 0, 5));

            // Do comments first
            $result = Roff_Comment::checkEvaluate($lines, $i);
            if ($result !== false) {
                if ($result['i'] < $i) { // We want another look at a modified $lines[$i];
                    --$i;
                } else {
                    array_splice($lines, $i, $result['i'] + 1 - $i);
                    --$i;// = $result['i'] - 1;
                }
                continue;
            }

            $request = Request::get($lines[$i]);

            if (!is_null($request['request'])) {

                $macros = $man->getMacros();
                if (isset($macros[$request['request']])) {
                    $man->setRegister('.$', count($request['arguments']));
                    if (!is_null($callerArguments)) {
                        foreach ($request['arguments'] as $k => $v) {
                            $request['arguments'][$k] = Roff_Macro::applyReplacements($request['arguments'][$k],
                              $callerArguments);
                        }
                    }

                    // Make copies of arrays:
                    $macroLines           = $macros[$request['request']];
                    $macroCallerArguments = $request['arguments'];
                    $newLines             = Text::applyRoffClasses($parentNode, $macroLines, $macroCallerArguments);
                    array_splice($lines, $i, 1, $newLines);
                    --$i;

                    continue;
                }

                $className = $man->getRoffRequestClass($request['request']);
                if ($className) {
                    $result = $className::evaluate($parentNode, $request, $lines, $i, $callerArguments);
                    if ($result !== false) {
                        if (isset($result['lines'])) {
                            foreach ($result['lines'] as $k => $l) {
                                $result['lines'][$k] = Roff_Macro::applyReplacements($l, $callerArguments);
                            }
                            array_splice($lines, $i, $result['i'] + 1 - $i, $result['lines']);
                        } else {
                            array_splice($lines, $i, $result['i'] + 1 - $i);
                        }
                        --$i;
//                        $i = $result['i'] - 1;
                        continue;
                    }
                }

            }

            $result = Roff_Skipped::checkEvaluate($lines, $i);
            if ($result !== false) {
                array_splice($lines, $i, $result['i'] + 1 - $i);
                --$i;
//                $i = $result['i'] - 1;
                continue;
            }

            $lines[$i] = Roff_Macro::applyReplacements($lines[$i], $callerArguments);

            // Do this here, e.g. e.g. a macro may be defined multiple times in a document and we want the current one.
            $lines[$i] = $man->applyAllReplacements($lines[$i]);

        }

        return $lines;

    }

}
