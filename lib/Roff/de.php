<?php


class Roff_de implements Roff_Template
{

    static function evaluate(array $request, array &$lines, ?array $macroArguments)
    {

        array_shift($lines);

        if (!preg_match('~^([^\s"]+)\s*$~u', $request['arg_string'], $matches)) {
            throw new Exception('Unexpected argument in Roff_Macro: ' . $request['arg_string']);
        }

        $newMacro   = $matches[1];
        $macroLines = [];
        $foundEnd   = false;

        while (count($lines)) {
            $request = Request::getLine($lines, 0);
            if (
                $request['request'] === '.' ||
                ($newMacro === 'P!' && $request['raw_line'] === '.') // work around bug in Xm*.3 man pages
            ) {
                $foundEnd = true;
                array_shift($lines);
                break;
            }
            $macroLines[] = Request::massageLine(array_shift($lines));
        }

        if (!$foundEnd) {
            throw new Exception('Macro definition for "' . $matches[1] . '" does not follow expected pattern.');
        }

        if (in_array($newMacro, ['SS', 'FONT', 'URL', 'SY', 'YS', 'SH', 'TP', 'RS', 'RE'])) {
            // Do nothing: don't override these macros.
            // djvm e.g. does something dodgy when overriding .SS, just use normal .SS handling for it.
            // TODO: .FONT see hack in Text::preprocessLines
            // .URL: we can do a better job with the semantic info.
        } else {
            Man::instance()->addMacro($newMacro, $macroLines);
        }

        return [];

    }

}
