<?php

declare(strict_types=1);

namespace Ecommerce\Api\Middlewares;

use Ecommerce\Api\Core\Auth;
use Ecommerce\Api\Core\HttpException;
use Ecommerce\Api\Core\Request;
use Ecommerce\Api\Core\Response;
use Ecommerce\Api\Repositories\UsuarioRepository;

/** Exige token válido e anexa o usuário em $request->attribute('usuario'). */
final class AuthMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        $token = $request->bearerToken();
        if ($token === null) {
            throw new HttpException(401, 'Token não informado.');
        }

        $payload = Auth::decode($token);

        // Consulta o banco a cada requisição: se o usuário foi excluído ou perdeu o papel de
        // admin, o token antigo deixa de valer imediatamente.
        $usuario = (new UsuarioRepository())->findById((int) $payload['sub']);
        if ($usuario === null) {
            throw new HttpException(401, 'Usuário do token não existe mais.');
        }

        $request->setAttribute('usuario', $usuario);

        return $next($request);
    }
}