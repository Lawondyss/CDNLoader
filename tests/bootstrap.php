<?php

require __DIR__ . '/../vendor/autoload.php';

if (!class_exists('Tester\Assert')) {
  echo 'Install Nette Tester using "composer update --dev"', PHP_EOL;
  exit(1);
}

define('TEMP_DIR', __DIR__ . '/temp');

if (!file_exists(TEMP_DIR)) {
  echo 'Create directory for temporary testing files in path "' . TEMP_DIR . '".', PHP_EOL;
  exit(1);
}

if (!is_readable(TEMP_DIR) || !is_writable(TEMP_DIR)) {
  echo 'Set permissions for directory "' . TEMP_DIR . '" to full read and write.', PHP_EOL;
  exit(1);
}

\Tester\Environment::setup();