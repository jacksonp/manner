#!/usr/bin/env php
<?php

//<editor-fold desc="Setup">
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
//</editor-fold>

Manner::roffToHTML($filePath, $errorLog);
