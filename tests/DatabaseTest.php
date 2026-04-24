<?php
namespace Snipptr\Tests;

use PHPUnit\Framework\TestCase;
use Snipptr\Database;
use PDO;

class DatabaseTest extends TestCase
{
    public function test_connect_returns_pdo(): void
    {
        $pdo = Database::connect();
        $this->assertInstanceOf(PDO::class, $pdo);
    }

    public function test_connect_is_singleton(): void
    {
        $a = Database::connect();
        $b = Database::connect();
        $this->assertSame($a, $b);
    }

    public function test_pastes_table_exists(): void
    {
        $pdo = Database::connect();
        $stmt = $pdo->query("SELECT to_regclass('public.pastes')");
        $this->assertNotNull($stmt->fetchColumn());
    }

    public function test_rate_limits_table_exists(): void
    {
        $pdo = Database::connect();
        $stmt = $pdo->query("SELECT to_regclass('public.rate_limits')");
        $this->assertNotNull($stmt->fetchColumn());
    }
}
