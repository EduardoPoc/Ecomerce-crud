<?php
declare(strict_types=1);
namespace Ecommerce\Api\Controllers;
use Ecommerce\Api\Core\Request;
use Ecommerce\Api\Core\Response;
use Ecommerce\Api\Services\CategoriaService;

final class CategoriaController
{
    private CategoriaService $service;
    public function __construct() { $this->service = new CategoriaService(); }
    public function index(Request $request): Response { return Response::json($this->service->listar()); }
    public function show(Request $request): Response { return Response::json($this->service->buscar((int) $request->param('id'))); }
}
