<?php

declare(strict_types=1);

namespace Ecommerce\Api\Controllers;

use Ecommerce\Api\Core\Auth;
use Ecommerce\Api\Core\Request;
use Ecommerce\Api\Core\Response;
use Ecommerce\Api\Services\PedidoService;

final class PedidoController
{
    private PedidoService $service;

    public function __construct()
    {
        $this->service = new PedidoService();
    }

    /** GET /pedidos: cliente vê os seus; admin vê todos. */
    public function index(Request $request): Response
    {
        $pagina = max(1, (int) $request->query('pagina', 1));
        $limite = min(100, max(1, (int) $request->query('limite', 20)));

        return Response::json($this->service->listar(Auth::user($request), $pagina, $limite));
    }

    /** GET /pedidos/{id}: dono do pedido ou admin. */
    public function show(Request $request): Response
    {
        return Response::json($this->service->buscar((int) $request->param('id'), Auth::user($request)));
    }

    /** POST /pedidos: checkout. */
    public function store(Request $request): Response
    {
        return Response::created($this->service->criar(Auth::user($request), $request->json()));
    }

    /** PATCH /pedidos/{id}/status (admin). */
    public function atualizarStatus(Request $request): Response
    {
        return Response::json($this->service->atualizarStatus((int) $request->param('id'), $request->json()));
    }
}