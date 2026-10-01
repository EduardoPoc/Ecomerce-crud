<?php

declare(strict_types=1);

use Ecommerce\Api\Config\Database;
use Ecommerce\Api\Controllers\AuthController;
use Ecommerce\Api\Controllers\UsuarioController;
use Ecommerce\Api\Core\Request;
use Ecommerce\Api\Core\Response;
use Ecommerce\Api\Core\Router;
use Ecommerce\Api\Middlewares\AdminMiddleware;
use Ecommerce\Api\Middlewares\AuthMiddleware;

/**
 * Todas as rotas da API ficam aqui. O frontend usa VITE_API_BASE_URL = http://host:porta/api,
 * por isso tudo fica sob o prefixo /api.
 *
 * Para criar um endpoint novo: registre a rota aqui apontando para [Controller::class, 'metodo'].
 * Enquanto o controller/método não existir, a rota responde 501 (não quebra o resto da API).
 */
return static function (Router $router): void {
    $router->group('/api', [], function (Router $api): void {

        // Verifica se a API está no ar e se o banco responde.
        $api->get('/health', static function (Request $request): Response {
            try {
                Database::connection()->query('SELECT 1');
                return Response::json(['status' => 'ok', 'banco' => 'conectado']);
            } catch (Throwable $e) {
                error_log((string) $e);
                return Response::json(['status' => 'erro', 'banco' => 'indisponivel'], 503);
            }
        });

        // ---------- Autenticação (públicas) ----------
        $api->post('/auth/login', [AuthController::class, 'login']);
        $api->post('/auth/cadastro', [AuthController::class, 'cadastro']);
                $api->get('/auth/me', [AuthController::class, 'me'], [AuthMiddleware::class]);

        // ---------- Usuários (somente ADMIN) ----------
        $api->group('/usuarios', [AuthMiddleware::class, AdminMiddleware::class], function (Router $r): void {
            $r->get('', [UsuarioController::class, 'index']);
            $r->get('/{id:\d+}', [UsuarioController::class, 'show']);
            $r->post('', [UsuarioController::class, 'store']);
            $r->put('/{id:\d+}', [UsuarioController::class, 'update']);
            $r->patch('/{id:\d+}', [UsuarioController::class, 'update']);
            $r->delete('/{id:\d+}', [UsuarioController::class, 'destroy']);
        });

        // ---------- A DEFINIR PELO TIME (o schema e o frontend já precisam disso) ----------
        // Catálogo (público):   GET /produtos, GET /produtos/{id}, GET /categorias
        // Carrinho:             GET /carrinho, POST /carrinho/itens, PATCH/DELETE /carrinho/itens/{id}
        // Pedidos (logado):     POST /pedidos, GET /pedidos, GET /pedidos/{id}
        // Endereços (logado):   GET/POST /enderecos, PUT/DELETE /enderecos/{id}
        // Admin:                POST/PUT/DELETE /produtos/{id}, PATCH /pedidos/{id}/status
    });
};