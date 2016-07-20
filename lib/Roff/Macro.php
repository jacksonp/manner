<?php


class Roff_Macro
{

    static function checkEvaluate(array $lines, int $i)
    {

        if (!preg_match('~^\.\s*de1? ([^\s"]+)\s*$~u', $lines[$i], $matches)) {
            return false;
        }

        $numLines   = count($lines);
        $newMacro   = '.' . $matches[1];
        $macroLines = [];
        $foundEnd   = false;

        for ($i = $i + 1; $i < $numLines; ++$i) {
            $macroLine = $lines[$i];
            if (
              $macroLine === '..' or
              ($newMacro === '.P!' and $macroLine === '.') // work around bug in Xm*.3 man pages
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

        if (in_array($newMacro, ['.SS', '.FONT', '.URL', '.SY', '.YS'])) {
            // Do nothing: don't override these macros.
            // djvm e.g. does something dodgy when overriding .SS, just use normal .SS handling for it.
            // TODO: .FONT see hack in Text::preprocessLines
            // .URL: we can do a better job with the semantic info.
        } elseif ($newMacro === '.INDENT') {
            $man->addAlias('INDENT', 'RS');
        } elseif ($newMacro === '.UNINDENT') {
            $man->addAlias('UNINDENT', 'RE');
        } else {
            $man->addMacro($newMacro, $macroLines);
        }

        return ['i' => $i];

    }

}
