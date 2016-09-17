<?php


class Roff
{

    static function parse(
      DOMElement $parentNode,
      array &$lines,
      &$callerArguments = null,
      $stopOnContentLineAfter = false
    ) {

        $man = Man::instance();

        if ($stopOnContentLineAfter !== false) {
            $i             = $stopOnContentLineAfter;
            $stopOnContent = true;
        } else {
            $i             = 0;
            $stopOnContent = false;
        }

        for (; $i < count($lines) && !($stopOnContent and $parentNode->textContent !== ''); ++$i) {

//            echo $i, "\t", $lines[$i], PHP_EOL;
//            var_dump(array_slice($lines, 0, 5));

            // Do comments first
            $result = Roff_Comment::checkEvaluate($lines, $i);
            if ($result !== false) {
                if ($result['i'] < $i) { // We want another look at a modified $lines[$i];
                    --$i;
                } else {
                    array_splice($lines, $i, $result['i'] + 1 - $i);
                    --$i;
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
                    Roff::parse($parentNode, $macroLines, $macroCallerArguments);

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
                        continue;
                    }
                }

            }

            $result = Roff_Skipped::checkEvaluate($lines, $i);
            if ($result !== false) {
                array_splice($lines, $i, $result['i'] + 1 - $i);
                --$i;
                continue;
            }

            $lines[$i] = Roff_Macro::applyReplacements($lines[$i], $callerArguments);

            $request = Request::getClass($lines, $i);

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
