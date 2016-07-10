<?php


class Roff_Condition
{

    // Tcl_RegisterObjType.3 condition: ""with whitespace"
    const CONDITION_REGEX = '([cdmrFS]\s?[^\s]+|!?"[^"]*"[^"]*"|!?\'[^\']*\'[^\']*\'|[^"][^\s]*)';

    static function checkEvaluate(array &$lines, int $i)
    {

        if (preg_match(
          '~^\.if\s+' . self::CONDITION_REGEX . ' \.if\s+' . self::CONDITION_REGEX . ' \\\\{\s*(.*)$~u',
          $lines[$i], $matches)
        ) {
            $cond1 = Text::translateCharacters($matches[1]);
            $cond2 = Text::translateCharacters($matches[2]);

            return self::ifBlock($lines, $i, $matches[3], self::test($cond1) and self::test($cond2));
        }

        if (preg_match('~^\.\s*if\s+' . self::CONDITION_REGEX . ' \\\\{\s*(.*)$~u', $lines[$i], $matches)) {
            return self::ifBlock($lines, $i, $matches[2], self::test($matches[1]));
        }

        if (
        preg_match('~^\.\s*if\s+' . self::CONDITION_REGEX . ' \.if\s+' . self::CONDITION_REGEX . ' (.*)$~u',
          $lines[$i], $matches)
        ) {
            $cond1 = Text::translateCharacters($matches[1]);
            $cond2 = Text::translateCharacters($matches[2]);
            if (self::test($cond1) and self::test($cond2)) {
                return ['lines' => Text::applyRoffClasses([Macro::massageLine($matches[3])]), 'i' => $i];
            } else {
                return ['lines' => [], 'i' => $i];
            }
        }

        if (preg_match('~^\.\s*if\s+' . self::CONDITION_REGEX . '\s?(.*?)$~u', $lines[$i], $matches)) {
            if (self::test(Text::translateCharacters($matches[1]))) {
                $lines[$i] = Macro::massageLine($matches[2]); // i.e. just remove .if <condition> prefix and go again.
                return ['i' => $i - 1];
            } else {
                return ['lines' => [], 'i' => $i];
            }
        }

        if (preg_match('~^\.\s*ie ' . self::CONDITION_REGEX . '\s?\\\\{\s*(.*)$~u', $lines[$i], $matches)) {
            $useIf = self::test(Text::translateCharacters($matches[1]));
            $if    = self::ifBlock($lines, $i, $matches[2], $useIf);
            $i     = $if['i'];

            $result = Roff_Comment::checkEvaluate($lines, $i);
            if ($result !== false) {
                $i = $result['i'];
            }

            return self::handleElse($lines, $i + 1, $useIf, $if['lines']);

        }

        if (preg_match('~^\.\s*ie ' . self::CONDITION_REGEX . '\s?(.*)$~u', $lines[$i], $ifMatches)) {
            $useIf  = self::test(Text::translateCharacters($ifMatches[1]));
            $result = Roff_Comment::checkEvaluate($lines, $i + 1);
            if ($result !== false) {
                $i = $result['i'];
            }

            return self::handleElse($lines, $i + 1, $useIf,
              Text::applyRoffClasses([Macro::massageLine($ifMatches[2])]));

        }

        return false;

    }

    private static function handleElse(array $lines, int $i, bool $useIf, array $ifLines): array
    {

        $line = $lines[$i];

        if (preg_match('~^\.\s*el\s?\\\\{(.*)$~u', $line, $matches)) {
            $else     = self::ifBlock($lines, $i, $matches[1], !$useIf);
            $newLines = $useIf ? $ifLines : $else['lines'];

            return ['lines' => $newLines, 'i' => $else['i']];
        }

        if (!preg_match('~^\.\s*el (.*)$~u', $line, $elseMatches)) {
            //throw new Exception('.ie condition - not followed by expected pattern on line ' . $i . ' (got "' . $lines[$i] . '").');
            // Just skip the ie and el lines:
            return ['lines' => [], 'i' => $i];
        }

        if ($useIf) {
            return ['lines' => $ifLines, 'i' => $i];
        } else {
            return ['lines' => Text::applyRoffClasses([Macro::massageLine($elseMatches[1])]), 'i' => $i];
        }


    }

    private static function test(string $condition)
    {

        if (mb_strpos($condition, '!') === 0) {
            return !self::test(mb_substr($condition, 1));
        }

        if (
          preg_match('~^\'([^\']*)\'([^\']*)\'$~u', $condition, $matches) or
          preg_match('~^"([^"]*)"([^"]*)"$~u', $condition, $matches)
        ) {
            return $matches[1] === $matches[2];
        }

        if (preg_match('~^m\s*\w+$~u', $condition)) {
            // mname: True if there is a color called name.
            return false; // No colours for now.
        }

        if (preg_match('~^c\s*\w+$~u', $condition)) {
            // cch: True if there is a glyph ch available.
            return true; // Assume we have all the glyphs
        }

        if (preg_match('~^d\s*(\w+)$~u', $condition, $matches)) {
            // dname: True if there is a string, macro, diversion, or request called name.
            // Hack (all other checks are against "d pdfmarks", hopefully that's should be false.
            return $matches[1] === 'TE';
        }

        $condition = Replace::pregCallback('~(\d+(?:\.\d+)?)([uicpPszfmnvM])~u', function ($matches) {
            $unitMultipliers = [
                // device dependent measurement, quite small, ranging from 1/75th to 1/72000th of an inch
              'u' => 1,
                // inch
              'i' => 75,
                // One inch is equal to 2.54cm.
              'c' => 75 * 2.54,
                // Points. This is a typesetter’s measurement used for measure type size. It is 72 points to an inch.
              'p' => 75 / 72,
                // Pica. Another typesetting measurement. 6 Picas to an inch (and 12 points to a pica).
              'P' => 75 / 6,
                // https://www.gnu.org/software/groff/manual/html_node/Fractional-Type-Sizes.html#Fractional-Type-Sizes
              's' => 1,
                // https://www.gnu.org/software/groff/manual/html_node/Fractional-Type-Sizes.html#Fractional-Type-Sizes
              'z' => 1,
                // https://www.gnu.org/software/groff/manual/html_node/Colors.html#Colors
              'f' => 65536,
                // em = 11/64 inch
              'm' => 75 * 11 / 64,
                // en = 1/2 em:
              'n' => (75 * 11 / 64) / 2,
                // By default, gtroff uses 10 point type on 12 point spacing. https://www.gnu.org/software/groff/manual/html_node/Sizes.html#Sizes
              'v' => 75 * 10 / 72,
                // 100ths of an em.
              'M' => (75 * 11 / 64) / 100,
            ];

            return $unitMultipliers[$matches[2]] * $matches[1];
        }, $condition);

        $condition = Replace::preg('~:~u', ' or ', $condition);
        $condition = Replace::preg('~&~u', ' and ', $condition);

        if (preg_match('~^([-\+\*/\d\(\)><=\.]| or | and )+$~u', $condition)) {
            $condition = Replace::preg('~(?<=\d)=(?=\d)~', '==', $condition);

            return eval('return ' . $condition . ';');
        }

        $alwaysTrue = [
          'n',       // "Formatter is nroff." ("for TTY output" - try changing to 't' sometime?)
          'dURL',
          'dTS',
        ];

        if (in_array($condition, $alwaysTrue, true)) {
            return true;
        }

        $alwaysFalse = [
          '\n()P',
          't', // "Formatter is troff."
          'v', // vroff
          'rF',
          'require_index',
          'c \\[shc]', // see man.1
          '\'po4a.hide\'',
        ];

        if (in_array($condition, $alwaysFalse, true)) {
            return false;
        }

        throw new Exception('Unhandled condition: "' . $condition . '".');

    }

    private static function ifBlock(array $lines, int $i, string $firstLine, bool $processContents = true)
    {

        $numLines         = count($lines);
        $foundEnd         = false;
        $replacementLines = [];
        $man              = Man::instance();

        $line = $firstLine;

        $openBraces = 1;
        $recurse    = false;

        for ($ifIndex = $i; $ifIndex < $numLines;) {
            $line = $man->applyAllReplacements($line);

            $openBraces += substr_count($line, '\\{');
            if ($openBraces > 1 or
              ($i !== $ifIndex and preg_match('~^\.\s*i[fe] ~u', $line))
            ) {
                $recurse = true;
            }
            $openBraces -= substr_count($line, '\\}');
            if (preg_match('~^(.*)\\\\}$~u', $line, $matches) and $openBraces === 0) {
                $foundEnd = true;
                if (!empty($matches[1]) and $matches[1] !== '\'br') {
                    $replacementLines[] = Macro::massageLine($matches[1]);
                }
                break;
            } elseif ($line !== '') {
                $replacementLines[] = Macro::massageLine($line);
            }
            ++$ifIndex;
            if ($ifIndex < $numLines) {
                $line = $lines[$ifIndex];
            }
        }

        if (!$foundEnd) {
            throw new Exception('.if condition \\{ - not followed by expected pattern on line ' . $ifIndex . '.');
        }

        if (!$processContents) {
            return ['lines' => [], 'i' => $ifIndex];
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
