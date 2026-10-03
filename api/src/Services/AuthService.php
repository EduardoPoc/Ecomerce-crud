<?php

declare(strict_types=1);

namespace Ecommerce\Api\Services;

use Ecommerce\Api\Core\Auth;
use Ecommerce\Api\Core\HttpException;
use Ecommerce\Api\Repositories\UsuarioRepository;

/** Login e cadastro público. Reaproveita UsuarioRepository/UsuarioService para não duplicar código. */
final class AuthService
{
    /** Hash de uma senha qualquer: usado para gastar o mesmo tempo quando o e-mail não existe. */
    private const HASH_FALSO = '$2y$10$DpaliF9qyaa4QEaydx0Yl..lQL07B521i.ywFX3UfrafRtvDDL1Qy';

    private UsuarioRepository $repository;
    private UsuarioService $usuarioService;

    public function __construct(?UsuarioRepository $repository = null, ?UsuarioService $usuarioService = null)
    {
        $this->repository = $repository ?? new UsuarioRepository();
        $this->usuarioService = $usuarioService ?? new UsuarioService($this->repository);
    }

    public function login(array $dados): array
    {
        $email = is_string($dados['email'] ?? null) ? strtolower(trim($dados['email'])) : '';
        $senha = is_string($dados['senha'] ?? null) ? $dados['senha'] : '';

        if ($email === '' || $senha === '') {
            throw new HttpException(422, 'Informe e-mail e senha.');
        }

        $usuario = $this->repository->findByEmailComSenha($email);

        // Mesma mensagem e mesmo tempo para "e-mail não existe" e "senha errada":
        // não revela quais e-mails estão cadastrados.
        $senhaConfere = password_verify($senha, $usuario['senha_hash'] ?? self::HASH_FALSO);
        if ($usuario === null || !$senhaConfere) {
            throw new HttpException(401, 'E-mail ou senha inválidos.');
        }

        unset($usuario['senha_hash']);
        return $this->comToken($usuario);
    }

    /** Cadastro público: sempre cria CLIENTE e já devolve o token (usuário entra logado). */
    public function cadastro(array $dados): array
    {
        return $this->comToken($this->usuarioService->criar($dados, permitirPapel: false));
    }

    public function atualizarPerfil(int $usuarioId, array $dados): array
    {
        return $this->usuarioService->atualizar($usuarioId, $dados);
    }

    private function comToken(array $usuario): array
    {
        $token = Auth::issueToken($usuario);

        return [
            'token' => $token['token'],
            'tipo' => 'Bearer',
            'expira_em' => $token['expira_em'],
            'usuario' => $usuario,
        ];
    }
}
