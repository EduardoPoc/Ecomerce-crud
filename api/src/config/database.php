<?php

declare(strict_types=1);

namespace Ecommerce\Api\Config;

use PDO;
use RuntimeException;

/** Centraliza a conexão PDO. A conexão é aberta sob demanda e reutilizada. */
final class Database
{
    private static ?PDO $connection = null;
    private static bool $environmentLoaded = false;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        self::loadEnvironmentFile(dirname(__DIR__, 2) . '/.env');
        $host = self::environment('DB_HOST', '127.0.0.1');
        $port = self::environment('DB_PORT', '3306');
        $name = self::environment('DB_NAME');
        $user = self::environment('DB_USER');
        $password = self::environment('DB_PASS', '');

        if ($name === '' || $user === '') {
            throw new RuntimeException('Configure DB_NAME e DB_USER no arquivo api/.env.');
        }

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
        self::$connection = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$connection;
    }

    private static function loadEnvironmentFile(string $path): void
    {
        if (self::$environmentLoaded) {
            return;
        }
        self::$environmentLoaded = true;

        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim(trim($value), "\\\"'");
            if ($key !== '' && getenv($key) === false) {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
            }
        }
    }

    private static function environment(string $key, string $default = ''): string
    {
        $value = getenv($key);
        return $value === false ? $default : trim((string) $value);
    }
}
