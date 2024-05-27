#!/usr/bin/env php
<?php
/**
 * manner: convert troff man pages to semantic HTML
 * Copyright (C) 2024  Jackson Pauls
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

use Manner\Manner;

require_once 'autoload.php';

/**
 * @param $filePath
 * @throws Exception
 */
function runTest($filePath): void
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
    Manner::roffToHTML($fileLines, null, true);
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
