<?php


class Roff_String
{

    static function checkEvaluate(array $lines, int $i)
    {

        if (!preg_match('~^\.ds1? (.*?) (.*)$~u', $lines[$i], $matches)) {
            if (preg_match('~^\.ds~u', $lines[$i])) {
                return ['i' => $i]; // ignore any .ds that didn't match first preg_match.
            }

            return false;
        }


        $man = Man::instance();

        if (empty($matches[2])) {
            return ['i' => $i];
        }

        $newRequest = $matches[1];
        $requestVal = Macro::simplifyRequest($matches[2]);

        // Q and U are special cases for when replacement is in a macro argument, which are separated by double
        // quotes and otherwise get messed up.
        if (in_array($newRequest, ['C\'', 'C`'])) {
            $requestVal = '"';
        } elseif (in_array($newRequest, ['L"', 'R"'])) {
            return ['i' => $i];
        } elseif ($newRequest === 'Q' and $requestVal === '\&"') {
            $requestVal = '“';
        } elseif ($newRequest === 'U' and $requestVal === '\&"') {
            $requestVal = '”';
        }

        // See e.g. rcsfreeze.1 for a replacement including another previously defined replacement.
        $requestVal = $man->applyStringReplacement($requestVal);

        $man->addString($newRequest, $requestVal);

        return ['i' => $i];

    }

}
