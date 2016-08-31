<?php


class Roff_Macro
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

        $man = Man::instance();

        if (in_array($newMacro, ['SS', 'FONT', 'URL', 'SY', 'YS', 'SH', 'TP', 'RS', 'RE'])) {
            // Do nothing: don't override these macros.
            // djvm e.g. does something dodgy when overriding .SS, just use normal .SS handling for it.
            // TODO: .FONT see hack in Text::preprocessLines
            // .URL: we can do a better job with the semantic info.
        } else {
            $man->addMacro($newMacro, $macroLines);
        }

        return ['i' => $i];

    }

    static function applyReplacements(string $string, &$arguments): string
    {

        if (is_null($arguments)) {
            return $string;
        }

        if (Request::is($string, 'shift')) {
            array_shift($arguments);

            return '.';
        }

        // \$x - Macro or string argument with one-digit number x in the range 1 to 9.
        for ($n = 1; $n < 10; ++$n) {
            $string = str_replace('\\$' . $n, @$arguments[$n - 1] ?: '', $string);
        }

        // \$* : In a macro or string, the concatenation of all the arguments separated by spaces.
        $string = str_replace('\\$*', implode(' ', $arguments), $string);

        // Other \$ things are also arguments...
        if (mb_strpos($string, '\\$') !== false) {
            throw new Exception($string . ' - can not handle macro that specifies arguments.');
        }

        return $string;
    }

}
