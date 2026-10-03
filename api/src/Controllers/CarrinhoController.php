<?php

declare(strict_types=1);

namespace Ecommerce\Api\Controllers;

use Ecommerce\Api\Core\Auth;
use Ecommerce\Api\Core\Request;
use Ecommerce\Api\Core\Response;
use Ecommerce\Api\Services\CarrinhoService;

final class CarrinhoController
{
    private CarrinhoService $service;
    public function __construct() { $this->service = new CarrinhoService(); }
    private function user(Request $request): int { return (int) Auth::user($request)['id']; }
    public function show(Request $request): Response { return Response::json($this->service->buscar($this->user($request))); }
    public function add(Request $request): Response { return Response::json($this->service->adicionar($this->user($request), $request->json())); }
    public function update(Request $request): Response
    {
        $data = $request->json();
        $data['produto_id'] = (int) $request->param('produto_id');
        return Response::json($this->service->atualizar($this->user($request), $data));
    }
    public function remove(Request $request): Response { return Response::json($this->service->remover($this->user($request), (int) $request->param('produto_id'))); }
}
