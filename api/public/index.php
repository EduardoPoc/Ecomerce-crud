<?php

declare(strict_types=1);

use Ecommerce\Api\Core\Request;
use Ecommerce\Api\Core\Response;
use Ecommerce\Api\Core\Router;
use Ecommerce\Api\Core\Logger;

$root = dirname(__DIR__);

// Autoload: usa o Composer se existir; senão, um autoloader PSR-4 mínimo (Ecommerce\Api\ -> src/).
if (is_file($root . '/vendor/autoload.php')) {
    require $root . '/vendor/autoload.php';
} else {
    spl_autoload_register(static function (string $class) use ($root): void {
        $prefix = 'Ecommerce\\Api\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $file = $root . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    });
}

$request = Request::fromGlobals();
$startedAt = hrtime(true);
$requestId = $request->header('x-request-id');
if ($requestId === null || !preg_match('/^[A-Za-z0-9._-]{1,128}$/', $requestId)) {
    $requestId = bin2hex(random_bytes(16));
}
$request->setAttribute('request_id', $requestId);

// CORS: o frontend (Vite) roda em outra porta e chama a API via Axios.
$env = is_file($root . '/.env') ? (parse_ini_file($root . '/.env', false, INI_SCANNER_RAW) ?: []) : [];
$allowedOrigins = array_map('trim', explode(',', $env['CORS_ORIGIN'] ?? 'http://localhost:5173'));
$origin = $request->header('origin');

$cors = [];
if ($origin !== null && in_array($origin, $allowedOrigins, true)) {
    $cors = [
        'Access-Control-Allow-Origin' => $origin,
        'Vary' => 'Origin',
        'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        'Access-Control-Max-Age' => '86400',
    ];
}

// Preflight do navegador responde direto, sem passar pelas rotas.
if ($request->method() === 'OPTIONS') {
    $response = Response::noContent();
} else {
    $router = new Router();
    (require $root . '/src/Routes/routes.php')($router);
    $response = $router->dispatch($request);
}

$response = $response->withHeader('X-Request-Id', $requestId);
foreach ($cors as $name => $value) {
    $response = $response->withHeader($name, $value);
}

Logger::info('http.request', [
    'request_id' => $requestId,
    'method' => $request->method(),
    'path' => $request->path(),
    'status' => $response->status(),
    'duration_ms' => round((hrtime(true) - $startedAt) / 1_000_000, 2),
]);

$response->send();
