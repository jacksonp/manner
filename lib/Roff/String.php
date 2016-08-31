<?php


class Roff_String
{

    static function evaluate(array $request, array $lines, int $i, $macroArguments)
    {

        if (!preg_match('~^(.+?)\s+(.+)$~u', $request['arg_string'], $matches)) {
            // May have just one argument, e.g. gnugo.6 - skip for now.
            return ['i' => $i];
        }

        $man = Man::instance();

        if (empty($matches[2])) {
            return ['i' => $i];
        }

        $newRequest = $matches[1];
        $requestVal = Request::simplifyRequest($matches[2]);
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
        } elseif ($newRequest === 'Q' and $requestVal === '\&"') {
            $requestVal = '“';
        } elseif ($newRequest === 'U' and $requestVal === '\&"') {
            $requestVal = '”';
        }

        // See e.g. rcsfreeze.1 for a replacement including another previously defined replacement.
        $requestVal = $man->applyAllReplacements($requestVal);
        $requestVal = Roff_Macro::applyReplacements($requestVal, $macroArguments);
//        $requestVal = TextContent::interpretString($requestVal);

        $man->addString($newRequest, $requestVal);

        return ['i' => $i];

    }

    static function substitute(string $string, array &$replacements) :string
    {

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
