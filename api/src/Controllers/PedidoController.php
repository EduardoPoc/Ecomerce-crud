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
    public function __construct() { $this->service = new PedidoService(); }
    private function user(Request $request): array { return Auth::user($request); }
    public function index(Request $request): Response { $user = $this->user($request); return Response::json($this->service->listar($user['papel'] === 'ADMIN' ? null : (int) $user['id'])); }
    public function show(Request $request): Response { $user = $this->user($request); return Response::json($this->service->buscar((int) $request->param('id'), $user['papel'] === 'ADMIN' ? null : (int) $user['id'])); }
    public function store(Request $request): Response { $order = $this->service->criar((int) $this->user($request)['id'], $request->json()); return Response::created($order); }
    public function pay(Request $request): Response { return Response::json($this->service->pagar((int) $request->param('id'), (int) $this->user($request)['id'])); }
    public function updateStatus(Request $request): Response { return Response::json($this->service->status((int) $request->param('id'), (string) $request->input('status', ''))); }
}
