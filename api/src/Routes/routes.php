<?php

declare(strict_types=1);

use Ecommerce\Api\Config\Database;
use Ecommerce\Api\Controllers\AuthController;
use Ecommerce\Api\Controllers\ProdutoController;
use Ecommerce\Api\Controllers\UsuarioController;
use Ecommerce\Api\Controllers\CategoriaController;
use Ecommerce\Api\Controllers\CarrinhoController;
use Ecommerce\Api\Controllers\EnderecoController;
use Ecommerce\Api\Controllers\PedidoController;
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
        $api->patch('/auth/me', [AuthController::class, 'updateMe'], [AuthMiddleware::class]);

        // Catálogo público de livros.
        $api->get('/produtos', [ProdutoController::class, 'index']);
        $api->get('/produtos/admin', [ProdutoController::class, 'adminIndex'], [AuthMiddleware::class, AdminMiddleware::class]);
        $api->get('/produtos/{id:\\d+}', [ProdutoController::class, 'show']);

        // ---------- Usuários (somente ADMIN) ----------
        $api->group('/usuarios', [AuthMiddleware::class, AdminMiddleware::class], function (Router $r): void {
            $r->get('', [UsuarioController::class, 'index']);
            $r->get('/{id:\d+}', [UsuarioController::class, 'show']);
            $r->post('', [UsuarioController::class, 'store']);
            $r->put('/{id:\d+}', [UsuarioController::class, 'update']);
            $r->patch('/{id:\d+}', [UsuarioController::class, 'update']);
            $r->delete('/{id:\d+}', [UsuarioController::class, 'destroy']);
        });

        // Categorias (públicas)
        $api->get('/categorias', [CategoriaController::class, 'index']);
        $api->get('/categorias/{id:\\d+}', [CategoriaController::class, 'show']);

        // Operação de vendedores: produtos e estoque. Categorias são somente leitura.
        $api->group('/produtos', [AuthMiddleware::class, AdminMiddleware::class], function (Router $r): void {
            $r->post('', [ProdutoController::class, 'store']);
            $r->post('/{id:\\d+}/imagem', [ProdutoController::class, 'uploadImage']);
            $r->put('/{id:\\d+}', [ProdutoController::class, 'update']);
            $r->patch('/{id:\\d+}', [ProdutoController::class, 'update']);
            $r->delete('/{id:\\d+}', [ProdutoController::class, 'destroy']);
        });

        // Carrinho e checkout exigem login; não existe carrinho de visitante.
        $api->group('/carrinho', [AuthMiddleware::class], function (Router $r): void {
            $r->get('', [CarrinhoController::class, 'show']);
            $r->post('/itens', [CarrinhoController::class, 'add']);
            $r->patch('/itens/{produto_id:\\d+}', [CarrinhoController::class, 'update']);
            $r->delete('/itens/{produto_id:\\d+}', [CarrinhoController::class, 'remove']);
        });

        $api->group('/enderecos', [AuthMiddleware::class], function (Router $r): void {
            $r->get('', [EnderecoController::class, 'index']);
            $r->post('', [EnderecoController::class, 'store']);
            $r->put('/{id:\\d+}', [EnderecoController::class, 'update']);
            $r->patch('/{id:\\d+}', [EnderecoController::class, 'update']);
            $r->delete('/{id:\\d+}', [EnderecoController::class, 'destroy']);
        });

        $api->group('/pedidos', [AuthMiddleware::class], function (Router $r): void {
            $r->get('', [PedidoController::class, 'index']);
            $r->get('/{id:\\d+}', [PedidoController::class, 'show']);
            $r->post('', [PedidoController::class, 'store']);
            $r->post('/{id:\\d+}/pagar', [PedidoController::class, 'pay']);
            $r->delete('/{id:\\d+}', [PedidoController::class, 'destroy']);
        });

        $api->patch('/pedidos/{id:\\d+}/status', [PedidoController::class, 'updateStatus'], [AuthMiddleware::class, AdminMiddleware::class]);
    });
};
