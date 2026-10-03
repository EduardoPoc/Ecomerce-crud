<?php

declare(strict_types=1);

namespace Ecommerce\Api\Core;

use Closure;
use Throwable;

/**
 * Compara método + caminho da requisição com as rotas registradas, executa os
 * middlewares da rota e chama o handler (controller).
 *
 * Handler:     [UsuarioController::class, 'index']  ou  uma Closure(Request): Response
 * Middleware:  classe com  handle(Request $request, callable $next): Response
 * Parâmetros:  /usuarios/{id}   ou com regex:  /usuarios/{id:\d+}
 */
final class Router
{
    /** @var array<int, array{method:string, regex:string, handler:mixed, middlewares:array}> */
    private array $routes = [];

    private string $prefix = '';

    /** @var string[] */
    private array $groupMiddlewares = [];

    public function get(string $path, array|Closure $handler, array $middlewares = []): void
    {
        $this->add('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, array|Closure $handler, array $middlewares = []): void
    {
        $this->add('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, array|Closure $handler, array $middlewares = []): void
    {
        $this->add('PUT', $path, $handler, $middlewares);
    }

    public function patch(string $path, array|Closure $handler, array $middlewares = []): void
    {
        $this->add('PATCH', $path, $handler, $middlewares);
    }

    public function delete(string $path, array|Closure $handler, array $middlewares = []): void
    {
        $this->add('DELETE', $path, $handler, $middlewares);
    }

    /** Agrupa rotas sob um prefixo e/ou middlewares comuns. Grupos podem ser aninhados. */
    public function group(string $prefix, array $middlewares, callable $callback): void
    {
        $previousPrefix = $this->prefix;
        $previousMiddlewares = $this->groupMiddlewares;

        $this->prefix .= $prefix;
        $this->groupMiddlewares = [...$this->groupMiddlewares, ...$middlewares];

        $callback($this);

        $this->prefix = $previousPrefix;
        $this->groupMiddlewares = $previousMiddlewares;
    }

    private function add(string $method, string $path, array|Closure $handler, array $middlewares): void
    {
        $full = '/' . trim($this->prefix . $path, '/');

        $regex = preg_replace_callback(
            '#\{(\w+)(?::([^}]+))?\}#',
            static fn(array $m): string => '(?P<' . $m[1] . '>' . ($m[2] ?? '[^/]+') . ')',
            $full
        );

        $this->routes[] = [
            'method' => $method,
            'regex' => '#^' . $regex . '$#',
            'handler' => $handler,
            'middlewares' => [...$this->groupMiddlewares, ...$middlewares],
        ];
    }

    public function dispatch(Request $request): Response
    {
        try {
            return $this->resolve($request);
        } catch (HttpException $e) {
            return Response::error($e->getMessage(), $e->status(), $e->errors());
        } catch (Throwable $e) {
            // Detalhes só no log do servidor: nunca vazar SQL/credenciais para o cliente.
            Logger::exception($e, 'request.unhandled_exception', [
                'request_id' => $request->attribute('request_id'),
                'method' => $request->method(),
                'path' => $request->path(),
            ]);
            return Response::error('Erro interno do servidor.', 500);
        }
    }

    private function resolve(Request $request): Response
    {
        $allowed = [];

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $request->path(), $matches)) {
                continue;
            }

            if ($route['method'] !== $request->method()) {
                $allowed[] = $route['method'];
                continue;
            }

            $request->setParams(array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));

            return $this->runPipeline($route, $request);
        }

        if ($allowed !== []) {
            return Response::error('Método não permitido.', 405)
                ->withHeader('Allow', implode(', ', array_unique($allowed)));
        }

        return Response::error('Rota não encontrada.', 404);
    }

    private function runPipeline(array $route, Request $request): Response
    {
        $core = fn(Request $req): Response => $this->callHandler($route['handler'], $req);

        $pipeline = array_reduce(
            array_reverse($route['middlewares']),
            fn(callable $next, string $middleware): callable => function (Request $req) use ($middleware, $next): Response {
                // Falha fechada: middleware ainda não implementado NÃO pode ser ignorado,
                // senão uma rota protegida ficaria pública por engano.
                if (!class_exists($middleware) || !method_exists($middleware, 'handle')) {
                    throw new HttpException(501, 'Middleware ainda não implementado: ' . $middleware);
                }
                return (new $middleware())->handle($req, $next);
            },
            $core
        );

        return $pipeline($request);
    }

    private function callHandler(array|Closure $handler, Request $request): Response
    {
        if ($handler instanceof Closure) {
            $response = $handler($request);
        } else {
            [$class, $method] = $handler;

            if (!class_exists($class) || !method_exists($class, $method)) {
                throw new HttpException(501, "Endpoint ainda não implementado: {$class}::{$method}");
            }
            $response = (new $class())->$method($request);
        }

        if (!$response instanceof Response) {
            throw new \LogicException('O handler da rota deve retornar um Response.');
        }
        return $response;
    }
}
