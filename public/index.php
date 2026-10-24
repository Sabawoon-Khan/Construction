<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

register_shutdown_function(static function (): void {
    $error = error_get_last();

    if ($error === null) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

    if (! in_array($error['type'], $fatalTypes, true)) {
        return;
    }

    $line = sprintf(
        "[%s] FATAL %s in %s:%s — %s\n",
        date('c'),
        $error['type'],
        $error['file'] ?? 'unknown',
        $error['line'] ?? 0,
        $error['message'] ?? ''
    );

    @file_put_contents(
        dirname(__DIR__).'/storage/logs/fatal.log',
        $line,
        FILE_APPEND | LOCK_EX
    );
});

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
