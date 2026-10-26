<?php

declare(strict_types=1);

$directory = $argv[1];
$source = $argv[2];
$manifest = json_decode(file_get_contents($directory.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
$manifest['repositories'] = [['type' => 'path', 'url' => $source, 'options' => ['symlink' => true]]];
$manifest['require']['andriichuk/pushbox'] = '@dev';
file_put_contents($directory.'/composer.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
$environment = file_get_contents($directory.'/.env.example');
$environment = str_replace(
    ['APP_KEY=', 'SESSION_DRIVER=database', 'CACHE_STORE=database'],
    ['APP_KEY=base64:'.base64_encode(random_bytes(32)), 'SESSION_DRIVER=file', 'CACHE_STORE=file'],
    $environment,
);
file_put_contents($directory.'/.env', $environment);
