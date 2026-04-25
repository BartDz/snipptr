<?php
namespace Snipptr\Tests;

use PHPUnit\Framework\TestCase;
use Snipptr\Database;
use PDO;

class DatabaseTest extends TestCase
{
    /**
     * @test
     */
    public function connectReturnsPdo(): void
    {
        $pdo = Database::connect();
        $this->assertInstanceOf(PDO::class, $pdo);
    }

    /**
     * @test
     */
    public function connectIsSingleton(): void
    {
        $a = Database::connect();
        $b = Database::connect();
        $this->assertSame($a, $b);
    }

    /**
     * @dataProvider tableProvider
     * @test
     */
    public function tablesExist(string $tableName): void
    {
        $pdo = Database::connect();
        $stmt = $pdo->query("SELECT to_regclass('public.{$tableName}')");
        $this->assertNotNull($stmt->fetchColumn());
    }

    public static function tableProvider(): array
    {
        return [
            'pastes table' => ['pastes'],
            'rate_limits table' => ['rate_limits'],
        ];
    }
}
