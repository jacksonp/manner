<?php


class Roff_Register
{

    static function checkEvaluate(array $lines, int $i)
    {

        if (!preg_match('~^\.nr (?<name>[-\w]+) (?<val>.+)$~u', $lines[$i], $matches)) {
            return false;
        }

        $man = Man::instance();

        $registerName = $matches['name'];
        $registerVal  = $matches['val'];
        if (mb_strlen($registerName) === 1) {
            $man->addRegister('\\n' . $registerName, $registerVal);
        }
        if (mb_strlen($registerName) === 2) {
            $man->addRegister('\\n(' . $registerName, $registerVal);
        }
        $man->addRegister('\\n[' . $registerName . ']', $registerVal);

        return ['i' => $i];

    }

}
