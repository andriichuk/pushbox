<?php

declare(strict_types=1);
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

// Run against an isolated Laravel application, never a user's existing app.
$directory = $argv[1] ?? throw new RuntimeException('Pass a fresh Laravel app directory.');
$expected = (int) ($argv[2] ?? 200);
require $directory.'/vendor/autoload.php';
$app = require $directory.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$request = Request::create('/pushbox', 'GET');
$response = $kernel->handle($request);
if ($response->getStatusCode() !== $expected) {
    fwrite(STDERR, 'Expected '.$expected.', received '.$response->getStatusCode().PHP_EOL);
    exit(1);
}
$kernel->terminate($request, $response);
echo '/pushbox '.$expected.PHP_EOL;
