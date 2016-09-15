<?php


class Text
{

    static function applyRoffClasses(DOMElement $parentNode, array &$lines, &$callerArguments = null): array
    {

        $man = Man::instance();

        $numLines = count($lines);
        $outLines = [];

        for ($i = 0; $i < $numLines; ++$i) {

            // Do comments first
            $result = Roff_Comment::checkEvaluate($lines, $i);
            if ($result !== false) {
                $i = $result['i'];
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

                    $outLines = array_merge($outLines,
                      Text::applyRoffClasses($parentNode, $macros[$request['request']], $request['arguments']));

                    continue;
                }

                $className = $man->getRoffRequestClass($request['request']);
                if ($className) {
                    $result = $className::evaluate($parentNode, $request, $lines, $i, $callerArguments);
                    if ($result !== false) {
                        if (isset($result['lines'])) {
                            foreach ($result['lines'] as $l) {
                                $outLines[] = Roff_Macro::applyReplacements($l, $callerArguments);
                            }
                        }
                        $i = $result['i'];
                        continue;
                    }
                }

            }

            $result = Roff_Skipped::checkEvaluate($lines, $i);
            if ($result !== false) {
                $i = $result['i'];
                continue;
            }

            $lines[$i] = Roff_Macro::applyReplacements($lines[$i], $callerArguments);

            // Do this here, e.g. e.g. a macro may be defined multiple times in a document and we want the current one.
            $outLines[] = $man->applyAllReplacements($lines[$i]);

        }

        return $outLines;

    }

}
