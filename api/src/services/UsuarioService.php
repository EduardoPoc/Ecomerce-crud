<?php

declare(strict_types=1);

namespace Ecommerce\Api\Services;

use Ecommerce\Api\Core\HttpException;
use Ecommerce\Api\Repositories\UsuarioRepository;
use PDOException;

/** Regras de negócio de usuários: validação, e-mail único, hash de senha e papéis. */
final class UsuarioService
{
    private const PAPEIS = ['CLIENTE', 'ADMIN'];

    private UsuarioRepository $repository;

    public function __construct(?UsuarioRepository $repository = null)
    {
        $this->repository = $repository ?? new UsuarioRepository();
    }

    public function listar(int $pagina, int $limite): array
    {
        return [
            'dados' => $this->repository->findAll($limite, ($pagina - 1) * $limite),
            'pagina' => $pagina,
            'limite' => $limite,
            'total' => $this->repository->count(),
        ];
    }

    public function buscar(int $id): array
    {
        return $this->repository->findById($id)
            ?? throw new HttpException(404, 'Usuário não encontrado.');
    }

    /**
     * @param bool $permitirPapel true só quando um ADMIN está criando o usuário.
     *   No cadastro público fica false: o papel é sempre CLIENTE, mesmo que o corpo mande "papel":"ADMIN".
     */
    public function criar(array $dados, bool $permitirPapel = false): array
    {
        [$campos, $erros] = $this->validar($dados, parcial: false, permitirPapel: $permitirPapel);
        if ($erros !== []) {
            throw new HttpException(422, 'Dados inválidos.', $erros);
        }

        if ($this->repository->emailExiste($campos['email'])) {
            throw new HttpException(409, 'Já existe um usuário com este e-mail.');
        }

        try {
            $id = $this->repository->create(
                $campos['nome'],
                $campos['email'],
                password_hash($campos['senha'], PASSWORD_BCRYPT),
                $campos['papel'] ?? 'CLIENTE'
            );
        } catch (PDOException $e) {
            $this->traduzirErroDeBanco($e);
        }

        return $this->buscar($id);
    }

    /** Atualização parcial: só os campos enviados são alterados (serve para PUT e PATCH). */
    public function atualizar(int $id, array $dados): array
    {
        $this->buscar($id);

        [$campos, $erros] = $this->validar($dados, parcial: true, permitirPapel: true);
        if ($erros !== []) {
            throw new HttpException(422, 'Dados inválidos.', $erros);
        }
        if ($campos === []) {
            throw new HttpException(422, 'Informe ao menos um campo para atualizar (nome, email, senha ou papel).');
        }

        if (isset($campos['email']) && $this->repository->emailExiste($campos['email'], $id)) {
            throw new HttpException(409, 'Já existe um usuário com este e-mail.');
        }

        if (isset($campos['senha'])) {
            $campos['senha_hash'] = password_hash($campos['senha'], PASSWORD_BCRYPT);
            unset($campos['senha']);
        }

        try {
            $this->repository->update($id, $campos);
        } catch (PDOException $e) {
            $this->traduzirErroDeBanco($e);
        }

        return $this->buscar($id);
    }

    public function remover(int $id, int $idSolicitante): void
    {
        if ($id === $idSolicitante) {
            throw new HttpException(422, 'Você não pode excluir a própria conta.');
        }
        $this->buscar($id);

        try {
            $this->repository->delete($id);
        } catch (PDOException $e) {
            $this->traduzirErroDeBanco($e);
        }
    }

    /** @return array{0: array, 1: array} [campos válidos e normalizados, erros por campo] */
    private function validar(array $dados, bool $parcial, bool $permitirPapel): array
    {
        $campos = [];
        $erros = [];

        if (!$parcial || array_key_exists('nome', $dados)) {
            $nome = is_string($dados['nome'] ?? null) ? trim($dados['nome']) : '';
            if ($this->tamanho($nome) < 2 || $this->tamanho($nome) > 150) {
                $erros['nome'] = 'Informe um nome entre 2 e 150 caracteres.';
            } else {
                $campos['nome'] = $nome;
            }
        }

        if (!$parcial || array_key_exists('email', $dados)) {
            $email = is_string($dados['email'] ?? null) ? strtolower(trim($dados['email'])) : '';
            if ($email === '' || $this->tamanho($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erros['email'] = 'Informe um e-mail válido (até 150 caracteres).';
            } else {
                $campos['email'] = $email;
            }
        }

        if (!$parcial || array_key_exists('senha', $dados)) {
            $senha = $dados['senha'] ?? null;
            // 72 bytes é o limite real do bcrypt; acima disso a senha seria truncada em silêncio.
            if (!is_string($senha) || strlen($senha) < 8 || strlen($senha) > 72) {
                $erros['senha'] = 'A senha deve ter entre 8 e 72 caracteres.';
            } else {
                $campos['senha'] = $senha;
            }
        }

        if ($permitirPapel && array_key_exists('papel', $dados)) {
            $papel = is_string($dados['papel']) ? strtoupper(trim($dados['papel'])) : '';
            if (!in_array($papel, self::PAPEIS, true)) {
                $erros['papel'] = 'Papel deve ser CLIENTE ou ADMIN.';
            } else {
                $campos['papel'] = $papel;
            }
        }

        return [$campos, $erros];
    }

    /** Conta caracteres (não bytes), sem depender da extensão mbstring. */
    private function tamanho(string $texto): int
    {
        $caracteres = preg_match_all('/./su', $texto);

        return $caracteres === false ? strlen($texto) : $caracteres;
    }

    /** Converte erros conhecidos do MySQL em respostas HTTP; os demais seguem como 500. */
    private function traduzirErroDeBanco(PDOException $e): never
    {
        $codigo = $e->errorInfo[1] ?? null;

        if ($codigo === 1062) {
            throw new HttpException(409, 'Já existe um usuário com este e-mail.');
        }
        if ($codigo === 1451) {
            throw new HttpException(409, 'Usuário possui endereços, pedidos ou carrinhos vinculados e não pode ser excluído.');
        }
        throw $e;
    }
}