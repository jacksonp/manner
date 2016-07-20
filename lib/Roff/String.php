<?php


class Roff_String
{

    static function checkEvaluate(array $lines, int $i)
    {

        if (!preg_match('~^\.\s*ds1? (.*?) (.*)$~u', $lines[$i], $matches)) {
            if (preg_match('~^\.\s*ds~u', $lines[$i])) {
                return ['i' => $i]; // ignore any .ds that didn't match first preg_match.
            }

            return false;
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
