<?php
declare(strict_types=1);

class Roff_String implements Roff_Template
{

    static function evaluate(array $request, array &$lines, ?array $macroArguments): void
    {

        array_shift($lines);

        $man = Man::instance();

        $known        = [];
        $known['C++'] = <<<'ROFF'
C+ C\v'-.1v'\h'-1p'+\h'-1p'+\v'.1v'\h'-1p'
ROFF;
        /*
        $known['ð'] = <<<'ROFF'
d- \h'0'\(pd\h'-\w'~'u'\v'-.25m'\f2\(hy\fP\v'.25m'\h'-0'
ROFF;
        $known['Ð'] = <<<'ROFF'
D- D\\k:\h'-\w'D'u'\v'-.11m'\z\(hy\v'.11m'\h'|\\n:u'
ROFF;
        $known['Þ'] = <<<'ROFF'
th \f1\v'.3m'I\v'-.3m'\h'-(\w'I'u*2/3)'o\fP
ROFF;
        $known['þ'] = <<<'ROFF'
Th \f1I\h'-\w'I'u*3/5'\v'-.3m'o\v'.3m'\fP
ROFF;
        */
        $known['ð'] = <<<'ROFF'
d- d\h'-1'\(ga
ROFF;
        $known['Ð'] = <<<'ROFF'
D- D\h'-1'\(hy
ROFF;
        $known['Þ'] = <<<'ROFF'
th \o'bp'
ROFF;
        $known['þ'] = <<<'ROFF'
Th \o'LP'
ROFF;

        if ($key = array_search($request['raw_arg_string'], $known)) {
            $man->addString($request['arguments'][0], $key);
            return;
        }

        if (!preg_match('~^(.+?)\s+(.+)$~u', $request['arg_string'], $matches)) {
            // May have just one argument, e.g. gnugo.6 - skip for now.
            return;
        }

        $newRequest = $matches[1];
        $requestVal = $matches[2];
        if (mb_substr($requestVal, 0, 1) === '"') {
            $requestVal = mb_substr($requestVal, 1);
        }

//        var_dump($newRequest);
//        var_dump($matches[2]);
//        var_dump($requestVal);
//        echo '----------------------', PHP_EOL;

        // Q and U are special cases for when replacement is in a macro argument, which are separated by double
        // quotes and otherwise get messed up.
        if (in_array($newRequest, ['C\'', 'C`'])) {
            $requestVal = '"';
        } elseif ($newRequest === 'Q' && $requestVal === '\&"') {
            $requestVal = '“';
        } elseif ($newRequest === 'U' && $requestVal === '\&"') {
            $requestVal = '”';
        }

        // See e.g. rcsfreeze.1 for a replacement including another previously defined replacement.
        $requestVal = $man->applyAllReplacements($requestVal);
        $requestVal = Roff_Macro::applyReplacements($requestVal, $macroArguments);

        $man->addString($newRequest, $requestVal);

    }

    static function substitute(string $string): string
    {

        $replacements = Man::instance()->getStrings();

        // Want to match any of: \*. \*(.. \*[....]
        return Replace::pregCallback(
            '~(?J)(?<!\\\\)(?<bspairs>(?:\\\\\\\\)*)\\\\(?:\*\[(?<str>[^\]\s]+)\]|\*\((?<str>[^\s]{2})|\*(?<str>[^\s]))~u',
            function ($matches) use (&$replacements) {
                if (isset($replacements[$matches['str']])) {
                    return $matches['bspairs'] . $replacements[$matches['str']];
                } else {
                    return $matches['bspairs']; // Follow what groff does, if string isn't set use empty string.
                }
            },
            $string);

    }

}
