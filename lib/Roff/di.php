<?php


class Roff_di
{

    static function evaluate(array $request, array &$lines, int $i)
    {

        $numLines = count($lines);
        for ($i = $i + 1; $i < $numLines; ++$i) {
            $request = Request::getLine($lines, $i);
            if ($request['request'] === 'di') {
                return ['i' => $i];
            }
        }
        throw new Exception('.di with no end .di');

    }

}
