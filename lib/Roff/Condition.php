<?php


class Roff_Condition
{

    const CONDITION_REGEX = '([cdmrFS]\s?[^\s]+|[^\s]+)';

    static function checkEvaluate(array $lines, int $i)
    {
//        var_dump($lines[$i]);

        if (preg_match(
          '~^\.if ' . self::CONDITION_REGEX . ' \.if ' . self::CONDITION_REGEX . ' \\\\{(.*)$~u',
          $lines[$i], $matches)
        ) {
            return self::ifBlock($lines, $i, $matches[3], self::test($matches[1]) and self::test($matches[2]));
        }

        if (preg_match('~^\.if ' . self::CONDITION_REGEX . ' \\\\{(.*)$~u', $lines[$i], $matches)) {
            return self::ifBlock($lines, $i, $matches[2], self::test($matches[1]));
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
            $useIf = self::test($matches[1]);
            $if    = self::ifBlock($lines, $i, $matches[2], $useIf);
            $i     = $if['i'] + 1;

            $line = $lines[$i];

            if (!preg_match('~^\.el\s?\\\\{(.*)$~', $line, $matches)) {
                throw new Exception('.ie - not followed by expected .el on line: "' . $line . '".');
            }

            $else     = self::ifBlock($lines, $i, $matches[1], !$useIf);
            $newLines = $useIf ? $if['lines'] : $else['lines'];

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

        if (
          preg_match('~^\'([^\']*)\'([^\']*)\'$~u', $condition, $matches) or
          preg_match('~^"([^"]*)"([^"]*)"$~u', $condition, $matches)
        ) {
            return $matches[1] === $matches[2];
        }

        if (preg_match('~^m\s*\w+$~u', $condition)) {
            return false; // No colours for now.
        }

        if (preg_match('~^[-\+\*/\d\(\)><=]+$~u', $condition)) {
            $condition = Replace::preg('~(?<=\d)=(?=\d)~', '==', $condition);
            return eval('return ' . $condition . ';');
        }

        if (preg_match('~^\(([^)]+)([:&])(.+)\)$~u', $condition, $matches)) {
            if ($matches[2] === ':') {
                return self::test($matches[1]) or self::test($matches[3]);
            } else {
                return self::test($matches[1]) and self::test($matches[3]);
            }
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
          '\\n(.H>23', // part of a check for low resolution devices, e.g. frogatto.6
          '(\\n(.H=4u)&(1m=24u)', // ? e.g. frogatto.6
          '(\\n(.H=4u)&(1m=20u)', // ? e.g. frogatto.6
          'require_index',
          '1=0:((0\\$1)*2u>(70u-\\n(.iu))', // revisit, see urls_txt.5
          '1=0:((0\w\'/usr/share/groff/1.22.3/font/devname/DESC\'u+3n)*2u>(70u-\n(.iu))',
          '1=0:((0\w\'\fB/usr/share/groff/1.22.3/font/devlj4/DESC\'u+2n)*2u>(70u-\n(.iu))',
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
                if (!empty($matches[1]) && $matches[1] !== '\'br') {
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
