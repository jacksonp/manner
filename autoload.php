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

/**
 * An example of a project-specific implementation.
 *
 * After registering this autoload function with SPL, the following line
 * would cause the function to attempt to load the \Foo\Bar\Baz\Qux class
 * from /path/to/project/src/Baz/Qux.php:
 *
 *      new \Foo\Bar\Baz\Qux;
 *
 * @param string $class The fully-qualified class name.
 * @return void
 */
spl_autoload_register(
  function ($class) {
      // project-specific namespace prefix
      $prefix = 'Manner\\';

      // base directory for the namespace prefix
      $base_dir = __DIR__ . '/lib/';

      // does the class use the namespace prefix?
      $len = strlen($prefix);
      if (strncmp($prefix, $class, $len) !== 0) {
          // no, move to the next registered autoloader
          return;
      }

      // get the relative class name
      $relative_class = substr($class, $len);

      // replace the namespace prefix with the base directory, replace namespace
      // separators with directory separators in the relative class name, append
      // with .php
      $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

      // if the file exists, require it
      if (file_exists($file)) {
          require $file;
      }
  }
);