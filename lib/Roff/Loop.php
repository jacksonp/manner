<?php
declare(strict_types=1);

class Roff_Loop implements Roff_Template
{

    /**
     * @param array $request
     * @param array $lines
     * @param array|null $macroArguments
     * @throws Exception
     */
    static function evaluate(array $request, array &$lines, ?array $macroArguments): void
    {

        array_shift($lines);

        if (mb_strlen($request['raw_arg_string']) === 0) {
            return; // Just skip
        }

        if (preg_match('~^' . Roff_Condition::CONDITION_REGEX . ' \\\\{\s*(.*)$~u',
            $request['raw_arg_string'],
            $matches)
        ) {

            $unrollOne = Roff_Condition::test($matches[1], $macroArguments);
            $newLines  = Roff_Condition::ifBlock($lines, $matches[2], $unrollOne);

            if ($unrollOne) {
                $newLines = array_merge($newLines, ['.while ' . $matches[1] . ' \\{'], $newLines, ['\\}']);
                array_splice($lines, 0, 0, $newLines);
            }

        }

    }

}
