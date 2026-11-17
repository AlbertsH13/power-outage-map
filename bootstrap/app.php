<?php

if (empty($_ENV['APP_KEY']) || empty(getenv('APP_KEY'))) {
    $keyFile = dirname(__DIR__).'/storage/framework/app.key';
    if (! file_exists($keyFile)) {
        @mkdir(dirname($keyFile), 0777, true);
        $generatedKey = 'base64:'.base64_encode(random_bytes(32));
        @file_put_contents($keyFile, $generatedKey);
    }
    $persistedKey = trim((string) @file_get_contents($keyFile));
    if (! empty($persistedKey)) {
        $_ENV['APP_KEY'] = $persistedKey;
        $_SERVER['APP_KEY'] = $persistedKey;
        putenv('APP_KEY='.$persistedKey);
    }
}

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
