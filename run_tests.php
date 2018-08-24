#!/usr/bin/env php
<?php
declare(strict_types=1);

spl_autoload_register(function ($class) {
    require_once 'lib/' . str_replace('_', '/', $class) . '.php';
});

/**
 * @param $filePath
 * @throws Exception
 */
function runTest($filePath)
{
    if (!is_file($filePath)) {
        exit($filePath . ' is not a file.');
    }

    $expectedOutputPath = $filePath . '.html';

    if (!is_file($expectedOutputPath)) {
        exit($expectedOutputPath . ' is not a file.');
    }

    $fileLines = file($filePath, FILE_IGNORE_NEW_LINES);

    ob_start();
    Manner::roffToHTML($fileLines, $filePath, null, true);
    $actualOutput = ob_get_contents();
    ob_end_clean();

    if ($actualOutput !== file_get_contents($expectedOutputPath)) {
        echo $filePath, PHP_EOL;
        echo '---------------------------', PHP_EOL;
        echo implode(PHP_EOL, $fileLines), PHP_EOL;
        echo '---------------------------', PHP_EOL;
        echo 'Expected:', PHP_EOL;
        echo file_get_contents($expectedOutputPath);
        echo '---------------------------', PHP_EOL;
        echo 'Got:', PHP_EOL;
        echo $actualOutput, PHP_EOL;
    }

}

$dir = new DirectoryIterator(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tests');
foreach ($dir as $fileInfo) {
    if ($fileInfo->isDot()) {
        continue;
    }
    if ($fileInfo->getExtension() === 'html') {
        continue;
    }
    runTest($fileInfo->getRealPath());
}
