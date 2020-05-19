#!/usr/bin/env php
<?php
declare(strict_types=1);

use Manner\Manner;

require_once 'autoload.php';

if (empty($argv[1])) {
    exit('no file.');
}

$filePath = $argv[1];

if (!is_file($filePath)) {
    exit($filePath . ' is not a file.');
}

$errorLog = '/tmp/mannerrors.log';

$test = false;
if (in_array(@$argv[2], ['-t', '--test'])) {
    $test = true;
}

$fileLines = file($filePath, FILE_IGNORE_NEW_LINES);

try {
    Manner::roffToHTML($fileLines, $filePath, null, $test);
} catch (Exception $e) {
    file_put_contents($errorLog, $e->getMessage() . ' (' . basename($filePath) . ')' . PHP_EOL, FILE_APPEND);
    echo PHP_EOL, PHP_EOL, $e->getMessage(), PHP_EOL;
    echo $e->getTraceAsString(), PHP_EOL;
    exit(1);
}
