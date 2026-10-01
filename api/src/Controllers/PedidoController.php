<?php

declare(strict_types=1);

namespace Ecommerce\Api\Controllers;

use Ecommerce\Api\Services\PedidoService;
use Ecommerce\Api\Core\Request;
use Ecommerce\Api\Core\Response;

final class PedidoController
{
    private PedidoService $service;

    public function __construct()
    {
        $this->service = new PedidoService();
    }

    public function getAll(Request $request): void
    {
        $pedidos = $this->service->getAll();
        Response::json($pedidos);
    }

    public function getById(Request $request): void
    {
        $id = (int)($request->getAttribute('id') ?? 0);
        if ($id <= 0) {
            Response::json(['error' => 'ID inválido'], 400);
            return;
        }

        $pedido = $this->service->getById($id);
        if ($pedido === null) {
            Response::json(['error' => 'Pedido não encontrado'], 404);
            return;
        }

        Response::json($pedido);
    }

    public function create(Request $request): void
    {
        $data = $request->getJson();

        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::json(['error' => 'Dados JSON inválidos'], 400);
            return;
        }

        // Basic validation - you can expand this
        if (!isset($data['usuario_id']) || !isset($data['status'])) {
            Response::json(['error' => 'Campos obrigatórios missing: usuario_id, status'], 400);
            return;
        }

        try {
            $id = $this->service->create($data);
            Response::json(['id' => $id, 'message' => 'Pedido criado com sucesso'], 201);
        } catch (\Exception $e) {
            Response::json(['error' => 'Erro ao criar pedido: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request): void
    {
        $id = (int)($request->getAttribute('id') ?? 0);
        if ($id <= 0) {
            Response::json(['error' => 'ID inválido'], 400);
            return;
        }

        $data = $request->getJson();

        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::json(['error' => 'Dados JSON inválidos'], 400);
            return;
        }

        // Remove id from data if present to prevent updating the ID
        unset($data['id']);

        try {
            $success = $this->service->update($id, $data);
            if ($success) {
                Response::json(['message' => 'Pedido atualizado com sucesso']);
            } else {
                Response::json(['error' => 'Falha ao atualizar pedido'], 500);
            }
        } catch (\Exception $e) {
            Response::json(['error' => 'Erro ao atualizar pedido: ' . $e->getMessage()], 500);
        }
    }

    public function delete(Request $request): void
    {
        $id = (int)($request->getAttribute('id') ?? 0);
        if ($id <= 0) {
            Response::json(['error' => 'ID inválido'], 400);
            return;
        }

        try {
            $success = $this->service->delete($id);
            if ($success) {
                Response::json(['message' => 'Pedido excluído com sucesso']);
            } else {
                Response::json(['error' => 'Falha ao excluir pedido'], 500);
            }
        } catch (\Exception $e) {
            Response::json(['error' => 'Erro ao excluir pedido: ' . $e->getMessage()], 500);
        }
    }
}
