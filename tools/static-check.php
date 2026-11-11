<?php

/**
 * Dependency-free static project checks, in addition to Laravel Pint.
 * This does not replace advanced type/data-flow analysis such as PHPStan.
 * Usage: php tools/static-check.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$checked = 0;

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
);

foreach ($files as $file) {
    if (! $file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    $relative = substr($path, strlen($root) + 1);
    if (preg_match('~^(vendor|node_modules|\.git)/~', str_replace('\\', '/', $relative))) {
        continue;
    }

    // Use the executing PHP interpreter to syntax-check all source files.
    $command = escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($path).' 2>&1';
    exec($command, $output, $exitCode);
    if ($exitCode !== 0) {
        $failures[] = "Syntax error in {$relative}: ".implode(' ', $output);
    }
    $output = [];
    $checked++;
}

require_once $root.'/app/Enums/RequestStatus.php';
require_once $root.'/app/Services/RequestWorkflow.php';

$workflowClass = new ReflectionClass(\App\Services\RequestWorkflow::class);
$map = $workflowClass->getReflectionConstant('ALLOWED')->getValue();
$expectedActions = ['submit', 'approve', 'reject', 'return', 'assign', 'start', 'complete'];
if (array_keys($map) !== $expectedActions) {
    $failures[] = 'Unexpected workflow action names or ordering.';
}

foreach ($map as $action => $transitions) {
    foreach ($transitions as $from => $to) {
        if (\App\Enums\RequestStatus::tryFrom($from) === null || \App\Enums\RequestStatus::tryFrom($to) === null) {
            $failures[] = "Invalid status in {$action}: {$from} => {$to}";
        }
        if (in_array($from, ['completed', 'rejected'], true)) {
            $failures[] = "Terminal status {$from} cannot be a transition source.";
        }
    }
}

$routes = file_get_contents($root.'/routes/api.php');
if (! str_contains($routes, "Route::middleware('auth:sanctum')")) {
    $failures[] = 'API routes missing Sanctum authentication group.';
}
if (! str_contains($routes, "->middleware('throttle:10,1')")) {
    $failures[] = 'Login route missing brute-force throttling.';
}

foreach (['composer.json', 'composer.lock', 'postman/Quantix-Service-Request-API.postman_collection.json'] as $file) {
    try {
        json_decode(file_get_contents($root.'/'.$file), true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        $failures[] = "Invalid {$file}: {$exception->getMessage()}";
    }
}

$phpunit = file_get_contents($root.'/phpunit.xml');
$exampleEnv = file_get_contents($root.'/.env.example');
if (! str_contains($phpunit, 'value="quantix_requests_test"')) {
    $failures[] = 'PHPUnit must run only against the dedicated MySQL test database.';
}
if (! str_contains($exampleEnv, 'DB_CONNECTION=mysql')) {
    $failures[] = 'MySQL must remain the default project database.';
}

foreach (['README.md', 'docs/ARCHITECTURE.md', 'docs/REQUIREMENTS_CHECKLIST.md', 'docs/REFERENCES_AND_AI.md'] as $file) {
    if (! is_file($root.'/'.$file)) {
        $failures[] = "Missing deliverable: {$file}";
    }
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "FAIL: {$failure}\n");
    }
    exit(1);
}

echo "PASS: {$checked} PHP files syntax checked; workflow statuses, security route markers, JSON files, MySQL test isolation, and deliverable files checked.\n";
echo "For richer static analysis, install Larastan and run PHPStan (see docs/STATIC_ANALYSIS.md).\n";
