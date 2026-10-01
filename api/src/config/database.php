<?php

declare(strict_types=1);

namespace Ecommerce\Api\Config;

use PDO;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $envPath = dirname(__DIR__, 2) . '/.env';
        $config = is_file($envPath)
            ? parse_ini_file($envPath, false, INI_SCANNER_RAW)
            : false;

        if ($config === false) {
            throw new RuntimeException('Crie api/.env usando api/.env.example.');
        }

        $host = $config['DB_HOST'] ?? '127.0.0.1';
        $port = $config['DB_PORT'] ?? '3306';
        $name = $config['DB_NAME'] ?? '';
        $user = $config['DB_USER'] ?? '';
        $password = $config['DB_PASS'] ?? '';

        if ($name === '' || $user === '') {
            throw new RuntimeException('Preencha DB_NAME e DB_USER no api/.env.');
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

        self::$connection = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$connection;
    }
}
