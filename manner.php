#!/usr/bin/env php
<?php

spl_autoload_register(function ($class) {
    require_once 'lib/' . str_replace('_', '/', $class) . '.php';
});

if (empty($argv[1])) {
    exit('no file.');
}

$filePath = $argv[1];

if (!is_file($filePath)) {
    exit($filePath . ' is not a file.');
}

$errorLog = '/tmp/mannerrors.log';
if (!empty($arv[2])) {
    $errorLog = $arv[2];
}

$fileLines = file($filePath, FILE_IGNORE_NEW_LINES);

try {
    Manner::roffToHTML($fileLines);
} catch (Exception $e) {
    file_put_contents($errorLog, $e->getMessage() . ' (' . basename($filePath) . ')' . PHP_EOL, FILE_APPEND);
    echo PHP_EOL, PHP_EOL, $e->getMessage(), PHP_EOL;
    echo $e->getTraceAsString(), PHP_EOL;
    exit(1);
}
