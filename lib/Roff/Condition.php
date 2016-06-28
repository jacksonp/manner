<?php


class Roff_Condition
{

    const CONDITION_REGEX = '(c\s?[^\s]+|[^\s]+)';

    static function checkEvaluate(array $lines, int $i)
    {

        if (preg_match(
          '~^\.if ' . self::CONDITION_REGEX . ' \.if ' . self::CONDITION_REGEX . ' \\\\{(.*)$~u',
          $lines[$i], $matches)
        ) {

            $if = self::ifBlock($lines, $i, $matches[3]);
            if (self::test($matches[1]) and self::test($matches[2])) {
                return $if;
            } else {
                return ['lines' => [], 'i' => $if['i']];
            }
        }

        if (preg_match('~^\.if ' . self::CONDITION_REGEX . ' \\\\{(.*)$~u', $lines[$i], $matches)) {
            $if = self::ifBlock($lines, $i, $matches[2]);
            if (self::test($matches[1])) {
                return $if;
            } else {
                return ['lines' => [], 'i' => $if['i']];
            }
        }

        if (
        preg_match('~^\.if ' . self::CONDITION_REGEX . ' \.if ' . self::CONDITION_REGEX . ' (.*)$~u',
          $lines[$i], $matches)
        ) {
            if (self::test($matches[1]) and self::test($matches[2])) {
                return ['lines' => Text::applyRoffClasses([Macro::massageLine($matches[3])]), 'i' => $i];
            } else {
                return ['lines' => [], 'i' => $i];
            }
        }

        if (preg_match('~^\.if ' . self::CONDITION_REGEX . ' (.*)$~u', $lines[$i], $matches)) {
            if (self::test($matches[1])) {
                return ['lines' => Text::applyRoffClasses([Macro::massageLine($matches[2])]), 'i' => $i];
            } else {
                return ['lines' => [], 'i' => $i];
            }
        }

        if (preg_match('~^\.ie ' . self::CONDITION_REGEX . ' \\\\{(.*)$~u', $lines[$i], $matches)) {
            $condition = $matches[1];
            $if        = self::ifBlock($lines, $i, $matches[2]);
            $i         = $if['i'] + 1;

            $line = $lines[$i];

            if (!preg_match('~^\.el\s?\\\\{(.*)$~', $line, $matches)) {
                throw new Exception('.ie - not followed by expected .el on line: "' . $line . '".');
            }

            $else     = self::ifBlock($lines, $i, $matches[1]);
            $newLines = self::test($condition) ? $if['lines'] : $else['lines'];

            return ['lines' => $newLines, 'i' => $else['i']];

        }

        if (preg_match('~^\.ie ' . self::CONDITION_REGEX . ' (.*)$~u', $lines[$i], $ifMatches)) {
            ++$i;
            if (!preg_match('~^\.el (.*)$~', $lines[$i], $elseMatches)) {
                //throw new Exception('.ie condition - not followed by expected pattern on line ' . $i . ' (got "' . $lines[$i] . '").');
                // Just skip the ie and el lines:
                return ['lines' => [], 'i' => $i];
            }

            if (self::test($ifMatches[1])) {
                return ['lines' => Text::applyRoffClasses([Macro::massageLine($ifMatches[2])]), 'i' => $i];
            } else {
                return ['lines' => Text::applyRoffClasses([Macro::massageLine($elseMatches[1])]), 'i' => $i];
            }

        }

        return false;

    }

    private static function test(string $condition)
    {

        if (mb_strpos($condition, '!') === 0) {
            return !self::test(mb_substr($condition, 1));
        }

        if (preg_match('~^\'([\w\.]*)\'([\w\.]*)\'$~u', $condition, $matches)) {
            return $matches[1] === $matches[2];
        }

        $alwaysTrue = [
          'n',       // "Formatter is nroff." ("for TTY output" - try changing to 't' sometime?)
          '\\n[.g]', // Always 1 in GNU troff.  Macros should use it to test if running under groff.
          '\\n(.g',  // as above
          'dURL',
          'dTS',
        ];

        if (in_array($condition, $alwaysTrue)) {
            return true;
        }

        $alwaysFalse = [
          '0',
          't', // "Formatter is troff."
          'v', // vroff
          'rF',
          '\\nF>0',
          '\\nF',
          '\\nF==2', // F register != 0 used to signal we should generate index entries. See e.g. frogatto.6
          '(\\n(rF:(\\n(.g==0))',
          '\\n(.H>23', // part of a check for low resolution devices, e.g. frogatto.6
          '(\\n(.H=4u)&(1m=24u)', // ? e.g. frogatto.6
          '(\\n(.H=4u)&(1m=20u)', // ? e.g. frogatto.6
          'require_index',
          '\\\\n(.$>=3', // revisit, see gnugo.6
          '\\\\n(.$=0:((0\\\\$1)*2u>(\\\\n(.lu-\\\\n(.iu))', // revisit, see urls_txt.5
          '\'\\*[.T]\'ascii\'',
          '\'\\*[.T]\'ascii8\'',
          '\'\\*[.T]\'latin1\'',
          '\'\\*[.T]\'nippon\'',
          '\'\\*[.T]\'utf8\'',
          '\'\\*[.T]\'cp1047\'',
          '\'\\*[pts-dev]\'tty\'',
          'c \\[shc]', // see man.1
          '\'po4a.hide\'',
          '\\n(.$>=3', // hack for isag.1
        ];

        if (in_array($condition, $alwaysFalse)) {
            return false;
        }

        throw new Exception('Unhandled condition: "' . $condition . '".');

    }

    private static function ifBlock(array $lines, int $i, string $firstLine)
    {

        $numLines         = count($lines);
        $foundEnd         = false;
        $replacementLines = [];

        $line = $firstLine;

        $openBraces = 1;
        $recurse    = false;

        for ($ifIndex = $i; $ifIndex < $numLines;) {
            $openBraces += substr_count($line, '\\{');
            if ($openBraces > 1 or
              ($i !== $ifIndex and preg_match('~^\.\s*i[fe] ~u', $line))
            ) {
                $recurse = true;
            }
            $openBraces -= substr_count($line, '\\}');
            if (preg_match('~^(.*)\\\\}$~u', $line, $matches) and $openBraces === 0) {
                $foundEnd = true;
                if (!empty($matches[1]) && $matches[1] !== '\'br') {
                    $replacementLines[] = Macro::massageLine($matches[1]);
                }
                break;
            } elseif ($line !== '') {
                $replacementLines[] = Macro::massageLine($line);
            }
            $line = $lines[++$ifIndex];
        }

        if (!$foundEnd) {
            throw new Exception('.if condition \\{ - not followed by expected pattern on line ' . $ifIndex . '.');
        }

        if ($recurse) {
            $recurseLines = [];
            for ($j = 0; $j < count($replacementLines); ++$j) {
                $result = self::checkEvaluate($replacementLines, $j);
                if ($result !== false) {
                    $recurseLines = array_merge($recurseLines, $result['lines']);
                    $j            = $result['i'];
                } else {
                    $recurseLines[] = $replacementLines[$j];
                }
            }
            $replacementLines = $recurseLines;
        }

        return ['lines' => Text::applyRoffClasses($replacementLines), 'i' => $ifIndex];

    }

}
