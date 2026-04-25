<?php
namespace Snipptr\Tests;

use PHPUnit\Framework\TestCase;
use Snipptr\Database;
use Snipptr\Paste;

class PasteTest extends TestCase
{
    private \PDO $pdo;

    protected function setUp(): void
    {
        Database::reset();
        $this->pdo = Database::connect();
        $this->pdo->exec('TRUNCATE pastes, rate_limits RESTART IDENTITY CASCADE');
    }

    public function test_create_returns_slug_and_id(): void
    {
        $result = Paste::create($this->pdo, '<?php echo 1;', 'php', 'never', null);
        $this->assertArrayHasKey('slug', $result);
        $this->assertArrayHasKey('id', $result);
        $this->assertSame(7, strlen($result['slug']));
    }

    public function test_find_by_slug_returns_correct_content(): void
    {
        $created = Paste::create($this->pdo, 'SELECT 1', 'sql', 'never', null);
        $found   = Paste::findBySlug($this->pdo, $created['slug']);
        $this->assertNotNull($found);
        $this->assertSame('SELECT 1', $found['content']);
    }

    public function test_expired_paste_returns_null(): void
    {
        $this->pdo->exec("
            INSERT INTO pastes (slug, content, language, expires_at)
            VALUES ('expired1', 'old', 'php', NOW() - INTERVAL '1 hour')
        ");
        $this->assertNull(Paste::findBySlug($this->pdo, 'expired1'));
    }

    public function test_nonexistent_slug_returns_null(): void
    {
        $this->assertNull(Paste::findBySlug($this->pdo, 'aaaaaaa'));
    }

    public function test_increment_views(): void
    {
        $created = Paste::create($this->pdo, 'code', 'php', 'never', null);
        Paste::incrementViews($this->pdo, $created['slug']);
        $found = Paste::findBySlug($this->pdo, $created['slug']);
        $this->assertSame(1, (int)$found['views']);
    }

    public function test_rate_limit_after_10_requests(): void
    {
        for ($i = 0; $i < 10; $i++) {
            Paste::trackRequest($this->pdo, '1.2.3.4');
        }
        $this->assertTrue(Paste::isRateLimited($this->pdo, '1.2.3.4'));
    }

    public function test_password_hash_is_stored(): void
    {
        $created = Paste::create($this->pdo, 'secret', 'php', 'never', 'mypassword');
        $found   = Paste::findBySlug($this->pdo, $created['slug']);
        $this->assertNotNull($found['password_hash']);
        $this->assertTrue(password_verify('mypassword', $found['password_hash']));
    }
}
