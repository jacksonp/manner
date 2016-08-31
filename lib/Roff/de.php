<?php


class Roff_de
{

    static function evaluate(array $request, array &$lines, int $i)
    {

        if (!preg_match('~^([^\s"]+)\s*$~u', $request['arg_string'], $matches)) {
            throw new Exception('Unexpected argument in Roff_Macro: ' . $request['arg_string']);
        }

        $numLines   = count($lines);
        $newMacro   = $matches[1];
        $macroLines = [];
        $foundEnd   = false;

        for ($i = $i + 1; $i < $numLines; ++$i) {
            $macroLine = $lines[$i];
            if (
              $macroLine === '..' or
              ($newMacro === 'P!' and $macroLine === '.') // work around bug in Xm*.3 man pages
            ) {
                $foundEnd = true;
                break;
            }
            $macroLines[] = Request::massageLine($macroLine);
        }

        if (!$foundEnd) {
            throw new Exception($matches[0] . ' - not followed by expected pattern.');
        }

        if (in_array($newMacro, ['SS', 'FONT', 'URL', 'SY', 'YS', 'SH', 'TP', 'RS', 'RE'])) {
            // Do nothing: don't override these macros.
            // djvm e.g. does something dodgy when overriding .SS, just use normal .SS handling for it.
            // TODO: .FONT see hack in Text::preprocessLines
            // .URL: we can do a better job with the semantic info.
        } else {
            Man::instance()->addMacro($newMacro, $macroLines);
        }

        return ['i' => $i];

    }

}
