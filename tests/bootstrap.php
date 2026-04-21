<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$_ENV['APP_ENV'] = 'testing';
$_SERVER['APP_ENV'] = 'testing';
putenv('APP_ENV=testing');

$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = ':memory:';
$_SERVER['DB_CONNECTION'] = 'sqlite';
$_SERVER['DB_DATABASE'] = ':memory:';
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');

foreach (['config.php', 'routes-v7.php'] as $file) {
    $path = $root.'/bootstrap/cache/'.$file;
    if (is_file($path)) {
        @unlink($path);
    }
}

require $root.'/vendor/autoload.php';
