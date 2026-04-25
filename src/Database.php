<?php

namespace Snipptr;

use PDO;

class Database
{
    private static ?PDO $connection = null;

    public static function connect(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $host = getenv('DB_HOST') ?: 'db';
        $name = getenv('DB_NAME') ?: 'snipptr';
        $user = getenv('DB_USER') ?: 'snipptr';
        $pass = getenv('DB_PASS') ?: 'secret';

        self::$connection = new PDO(
            "pgsql:host={$host};dbname={$name}",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );

        self::migrate(self::$connection);

        return self::$connection;
    }

    public static function reset(): void
    {
        self::$connection = null;
    }

    private static function migrate(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS pastes (
                id SERIAL PRIMARY KEY,
                slug VARCHAR(8) UNIQUE NOT NULL,
                content TEXT NOT NULL,
                language VARCHAR(50) NOT NULL DEFAULT 'plaintext',
                expires_at TIMESTAMP NULL,
                password_hash VARCHAR(255) NULL,
                views INTEGER NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS rate_limits (
                id SERIAL PRIMARY KEY,
                ip_hash VARCHAR(64) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE INDEX IF NOT EXISTS idx_rate_limits_ip ON rate_limits(ip_hash, created_at);
        ");
    }
}
