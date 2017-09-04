<?php
declare(strict_types=1);

class Roff_de implements Roff_Template
{

    static function evaluate(array $request, array &$lines, ?array $macroArguments)
    {

        // shift .de
        array_shift($lines);

        if (!preg_match('~^([^\s"]+)\s*$~u', $request['arg_string'], $matches)) {
            throw new Exception('Unexpected argument in Roff_Macro: ' . $request['arg_string']);
        }

        $newMacro   = $matches[1];
        $macroLines = [];
        $foundEnd   = false;

        // We don't want to handle the lines at this stage (e.g. a conditional in the macro), so don't iterate with
        // Request::getLine()
        while (count($lines)) {
            $line    = array_shift($lines);
            $request = Request::peepAt($line);
            if (
                $request['name'] === '.' ||
                ($newMacro === 'P!' && $line === '.') // work around bug in Xm*.3 man pages
            ) {
                $foundEnd = true;
                break;
            }
            $macroLines[] = Request::massageLine($line);
        }

        if (!$foundEnd) {
            throw new Exception('Macro definition for "' . $matches[1] . '" does not follow expected pattern.');
        }

        if (in_array($newMacro, ['SS', 'URL', 'SY', 'YS', 'SH', 'TP', 'RS', 'RE'])) {
            // Do nothing: don't override these macros.
            // djvm e.g. does something dodgy when overriding .SS, just use normal .SS handling for it.
            // .URL: we can do a better job with the semantic info.
        } else {
            Man::instance()->addMacro($newMacro, $macroLines);
        }

        return [];

    }

}
