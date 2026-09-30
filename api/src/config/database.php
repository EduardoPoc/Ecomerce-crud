<?php

/**
 * Carrega as variáveis do arquivo .env para $_ENV.
 * Ignora linhas vazias e comentários (#).
 */
function carregarEnv(string $caminho): void
{
    if (!file_exists($caminho)) {
        return;
    }

    $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($linhas as $linha) {
        $linha = trim($linha);

        if ($linha === '' || str_starts_with($linha, '#') || !str_contains($linha, '=')) {
            continue;
        }

        [$chave, $valor] = explode('=', $linha, 2);
        $_ENV[trim($chave)] = trim($valor, " \t\n\r\0\x0B\"'");
    }
}

/**
 * Retorna a conexão PDO com o MySQL.
 * A mesma conexão é reaproveitada durante toda a requisição.
 *
 * Uso:
 *   $pdo = getConnection();
 *   $stmt = $pdo->prepare('SELECT * FROM produto WHERE id = ?');
 *   $stmt->execute([$id]);
 */
function getConnection(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    carregarEnv(__DIR__ . '/../../.env');

    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $nome = $_ENV['DB_NAME'] ?? '';
    $user = $_ENV['DB_USER'] ?? '';
    $pass = $_ENV['DB_PASS'] ?? '';

    if ($nome === '' || $user === '') {
        registrarErro('Variáveis DB_NAME e DB_USER não definidas. Verifique o arquivo .env');
        responderErroInterno();
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$nome};charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // lança exceções em erros de SQL
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // retorna arrays associativos
            PDO::ATTR_EMULATE_PREPARES   => false,                   // prepared statements reais
        ]);
    } catch (PDOException $e) {
        registrarErro('Erro de conexão com o banco: ' . $e->getMessage());
        responderErroInterno();
    }

    return $pdo;
}

/**
 * Grava o erro real em logs/erros.log (o cliente nunca vê o detalhe).
 */
function registrarErro(string $mensagem): void
{
    $pasta = __DIR__ . '/../../logs';

    if (!is_dir($pasta)) {
        @mkdir($pasta, 0755, true);
    }

    error_log(
        '[' . date('Y-m-d H:i:s') . '] ' . $mensagem . PHP_EOL,
        3,
        $pasta . '/erros.log'
    );
}

/**
 * Responde com erro 500 genérico em JSON e encerra a execução.
 */
function responderErroInterno(): never
{
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['erro' => 'Erro interno do servidor']);
    exit;
}