<?php

  function includeIfExists($file) {
    return file_exists($file) ? include $file : FALSE;
  }

  if ((!$loader = includeIfExists(__DIR__ . '/vendor/autoload.php'))) {
    echo 'You must set up the project dependencies, run the following commands:' . PHP_EOL .
        '{{ instructions go here }}' . PHP_EOL;
    exit(1);
  }

  return $loader;