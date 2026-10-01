<?php

declare(strict_types=1);

namespace Ecommerce\Api\Middlewares;

use Ecommerce\Api\Core\Auth;
use Ecommerce\Api\Core\HttpException;
use Ecommerce\Api\Core\Request;
use Ecommerce\Api\Core\Response;

/** Exige papel ADMIN. Deve vir DEPOIS do AuthMiddleware na lista de middlewares. */
final class AdminMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        $usuario = Auth::user($request);

        if ($usuario === null) {
            throw new HttpException(401, 'Não autenticado.');
        }
        if ($usuario['papel'] !== 'ADMIN') {
            throw new HttpException(403, 'Acesso restrito a administradores.');
        }

        return $next($request);
    }
}