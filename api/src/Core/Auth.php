<?php

declare(strict_types=1);

namespace Ecommerce\Api\Core;

use RuntimeException;

/**
 * Autenticação por token JWT (HS256), sem bibliotecas externas.
 * Configuração no .env: JWT_SECRET (mín. 32 caracteres) e JWT_TTL (segundos, padrão 7200).
 */
final class Auth
{
    /** Gera o token de um usuário. Retorna ['token' => ..., 'expira_em' => timestamp]. */
    public static function issueToken(array $usuario): array
    {
        $agora = time();
        $expira = $agora + (int) Env::get('JWT_TTL', '7200');

        $header = self::encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = self::encode([
            'sub' => (int) $usuario['id'],
            'papel' => $usuario['papel'],
            'iat' => $agora,
            'exp' => $expira,
        ]);
        $assinatura = self::sign("{$header}.{$payload}");

        return ['token' => "{$header}.{$payload}.{$assinatura}", 'expira_em' => $expira];
    }

    /** Valida assinatura e validade. Qualquer problema vira HttpException 401. */
    public static function decode(string $token): array
    {
        $partes = explode('.', $token);
        if (count($partes) !== 3) {
            throw new HttpException(401, 'Token inválido.');
        }

        [$header, $payload, $assinatura] = $partes;

        if (!hash_equals(self::sign("{$header}.{$payload}"), $assinatura)) {
            throw new HttpException(401, 'Token inválido.');
        }

        $cabecalho = json_decode(self::base64UrlDecode($header), true);
        $dados = json_decode(self::base64UrlDecode($payload), true);

        if (!is_array($cabecalho) || ($cabecalho['alg'] ?? null) !== 'HS256' || !is_array($dados)) {
            throw new HttpException(401, 'Token inválido.');
        }
        if (!isset($dados['sub'], $dados['exp']) || (int) $dados['exp'] < time()) {
            throw new HttpException(401, 'Token expirado. Faça login novamente.');
        }

        return $dados;
    }

    /** Usuário autenticado desta requisição (preenchido pelo AuthMiddleware) ou null. */
    public static function user(Request $request): ?array
    {
        return $request->attribute('usuario');
    }

    private static function sign(string $dados): string
    {
        return self::base64UrlEncode(hash_hmac('sha256', $dados, self::secret(), true));
    }

    private static function secret(): string
    {
        $secret = Env::get('JWT_SECRET', '');
        if (strlen($secret) < 32) {
            // Falha fechada: sem segredo forte não emitimos nem aceitamos tokens.
            throw new RuntimeException('Defina JWT_SECRET com pelo menos 32 caracteres no api/.env.');
        }
        return $secret;
    }

    private static function encode(array $dados): string
    {
        return self::base64UrlEncode(json_encode($dados, JSON_THROW_ON_ERROR));
    }

    private static function base64UrlEncode(string $dados): string
    {
        return rtrim(strtr(base64_encode($dados), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $dados): string
    {
        return (string) base64_decode(strtr($dados, '-_', '+/'), false);
    }
}