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

    /**
     * @dataProvider pasteCreationProvider
     * @test
     */
    public function pasteCreationWorksCorrectly(string $content, string $language, string $expires, ?string $password): void
    {
        $result = Paste::create($this->pdo, $content, $language, $expires, $password);
        $this->assertArrayHasKey('slug', $result);
        $this->assertArrayHasKey('id', $result);
        $this->assertSame(7, strlen($result['slug']));
    }

    public static function pasteCreationProvider(): array
    {
        return [
            'php paste without password' => ['<?php echo 1;', 'php', 'never', null],
            'sql paste with expiry' => ['SELECT 1', 'sql', '1h', null],
            'protected paste' => ['secret data', 'php', 'never', 'password123'],
        ];
    }

    /**
     * @dataProvider pasteRetrievalProvider
     * @test
     */
    public function pasteRetrievalWorksCorrectly(string $slug, ?string $expectedContent): void
    {
        if ($expectedContent !== null) {
            $created = Paste::create($this->pdo, $expectedContent, 'php', 'never', null);
            $slug = $created['slug'];
        }
        
        $found = Paste::findBySlug($this->pdo, $slug);
        
        if ($expectedContent === null) {
            $this->assertNull($found);
        } else {
            $this->assertNotNull($found);
            $this->assertSame($expectedContent, $found['content']);
        }
    }

    public static function pasteRetrievalProvider(): array
    {
        return [
            'valid slug' => ['', 'SELECT 1'],
            'nonexistent slug' => ['aaaaaaa', null],
        ];
    }

    /**
     * @test
     */
    public function expiredPasteReturnsNull(): void
    {
        $this->pdo->exec("
            INSERT INTO pastes (slug, content, language, expires_at)
            VALUES ('expired1', 'old', 'php', NOW() - INTERVAL '1 hour')
        ");
        $this->assertNull(Paste::findBySlug($this->pdo, 'expired1'));
    }


    /**
     * @test
     */
    public function incrementViews(): void
    {
        $created = Paste::create($this->pdo, 'code', 'php', 'never', null);
        Paste::incrementViews($this->pdo, $created['slug']);
        $found = Paste::findBySlug($this->pdo, $created['slug']);
        $this->assertSame(1, (int)$found['views']);
    }

    /**
     * @test
     */
    public function rateLimitAfter10Requests(): void
    {
        for ($i = 0; $i < 10; $i++) {
            Paste::trackRequest($this->pdo, '1.2.3.4');
        }
        $this->assertTrue(Paste::isRateLimited($this->pdo, '1.2.3.4'));
    }

    /**
     * @test
     */
    public function passwordHashIsStored(): void
    {
        $created = Paste::create($this->pdo, 'secret', 'php', 'never', 'mypassword');
        $found = Paste::findBySlug($this->pdo, $created['slug']);
        $this->assertNotNull($found['password_hash']);
        $this->assertTrue(password_verify('mypassword', $found['password_hash']));
    }
}
