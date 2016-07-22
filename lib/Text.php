<?php


class Text
{

    static function stripComments(array $lines): array
    {

        $numLines        = count($lines);
        $linesNoComments = [];
        $linePrefix      = '';

        for ($i = 0; $i < $numLines; ++$i) {

            $line       = $linePrefix . $lines[$i];
            $linePrefix = '';

            // Continuations
            // Can't do these in applyRoffClasses() loop: a continuation could e.g. been in the middle of a conditional
            // picked up Roff_Condition, e.g. man.1
            // Do these before comments (see e.g. ppm.5 where first line is just "\" and next one is a comment.
            while (
              $i < $numLines - 1 and
              mb_substr($line, -1, 1) === '\\' and
              (mb_strlen($line) === 1 or mb_substr($line, -2, 1) !== '\\')) {
                $line = mb_substr($line, 0, -1) . $lines[++$i];
            }

            // Everything up to and including the next newline is ignored. This is interpreted in copy mode.  This is like \" except that the terminating newline is ignored as well.
            if (preg_match('~(^|.*?[^\\\\])\\\\#~u', $line, $matches)) {
                $linePrefix = $matches[1];
                continue;
            }

            // .do: "Interpret .name with compatibility mode disabled."  (e.g. .do if ... )
            $line = Replace::preg('~^\.do ~u', '.', $line);

            // NB: Workaround for lots of broken tcl man pages (section n, Tk_*, Tcl_*, others...):
            $line = Replace::preg('~^\.\s*el\s?\\\\}~u', '.el \\{', $line);

            // TODO: fix this hack, see groff_mom.7
            $line = preg_replace('~^\.FONT ~u', '.', $line);

            $line = Replace::pregCallback('~\\\\\[u([\dA-F]{4})\]~u', function ($matches) {
                return html_entity_decode('&#x' . $matches[1] . ';', ENT_COMPAT, 'UTF-8');
            }, $line);

            $line = Replace::pregCallback('~\\\\\[char(\d+)\]~u', function ($matches) {
                return mb_convert_encoding('&#' . intval($matches[1]) . ';', 'UTF-8', 'HTML-ENTITIES');
            }, $line);

            // Don't worry about changes in point size for now (see rc.1 for digit instead of +- in \s10):
            $line = Replace::preg('~(?<!\\\\)((?:\\\\\\\\)*)\\\\s[-+\d]?\d~u', '$1', $line);

            // Don't worry colour changes:
            $line = Replace::preg('~(?<!\\\\)((?:\\\\\\\\)*)\\\\m(\(..|\[.*?\])~u', '$1', $line);

            $linesNoComments[] = $line;

        }

        return $linesNoComments;

    }

    static function applyRoffClasses(array &$lines, $callerArguments = null): array
    {

        $man = Man::instance();

        $numNoCommentLines = count($lines);
        $linesNoCond       = [];

        for ($i = 0; $i < $numNoCommentLines; ++$i) {

            if (mb_substr($lines[$i], 0, 1) === '.') {
                $arguments = Request::parseArguments($lines[$i]);
                if (count($arguments) > 0) {
                    $macro  = trim(array_shift($arguments));
                    $macros = $man->getMacros();
                    if (isset($macros[$macro])) {
                        $man->setRegister('.$', count($arguments));
                        if (!is_null($callerArguments)) {
                            foreach ($arguments as $k => $v) {
                                $arguments[$k] = Roff_Macro::applyReplacements($arguments[$k], $callerArguments);
                            }
                        }

                        $linesNoCond = array_merge($linesNoCond, Text::applyRoffClasses($macros[$macro], $arguments));

                        continue;
                    }
                }
            }

            $roffClasses = [
              'Comment',
              'Condition',
              'Macro',
              'Register',
              'String',
              'Alias',
              'Translation',
              'Skipped',
            ];

            foreach ($roffClasses as $roffClass) {
                $className = 'Roff_' . $roffClass;
                $result    = $className::checkEvaluate($lines, $i, $callerArguments);
                if ($result !== false) {
                    if (isset($result['lines'])) {
                        foreach ($result['lines'] as $l) {
                            $linesNoCond[] = Roff_Macro::applyReplacements($l, $callerArguments);
                        }
                    }
                    $i = $result['i'];
                    continue 2;
                }
            }

            $lines[$i] = Roff_Macro::applyReplacements($lines[$i], $callerArguments);

            // Do this here, e.g. e.g. a macro may be defined multiple times in a document and we want the current one.
            $linesNoCond[] = $man->applyAllReplacements($lines[$i]);

        }

        return $linesNoCond;

    }

}
