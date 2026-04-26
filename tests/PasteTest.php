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
        $this->assertNotNull($result->getId());
        $this->assertNotNull($result->getSlug());
        $this->assertSame(7, strlen($result->getSlug()));
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
            $slug = $created->getSlug();
        }
        
        $found = Paste::findBySlug($this->pdo, $slug);
        
        if ($expectedContent === null) {
            $this->assertNull($found);
        } else {
            $this->assertNotNull($found);
            $this->assertSame($expectedContent, $found->getContent());
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
        Paste::incrementViews($this->pdo, $created->getSlug());
        $found = Paste::findBySlug($this->pdo, $created->getSlug());
        $this->assertSame(1, $found->getViews());
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
        $found = Paste::findBySlug($this->pdo, $created->getSlug());
        $this->assertNotNull($found->getPasswordHash());
        $this->assertTrue(password_verify('mypassword', $found->getPasswordHash()));
    }

    /**
     * @test
     */
    public function burnAfterReadIsStoredWhenTrue(): void
    {
        $created = Paste::create($this->pdo, 'secret', 'php', 'never', null, true);
        $found = Paste::findBySlug($this->pdo, $created->getSlug());
        $this->assertTrue($found->isBurnAfterRead());
    }

    /**
     * @test
     */
    public function burnAfterReadIsStoredWhenFalse(): void
    {
        $created = Paste::create($this->pdo, 'secret', 'php', 'never', null, false);
        $found = Paste::findBySlug($this->pdo, $created->getSlug());
        $this->assertFalse($found->isBurnAfterRead());
    }

    /**
     * @test
     */
    public function burnAfterReadDefaultsFalse(): void
    {
        $created = Paste::create($this->pdo, 'secret', 'php', 'never', null);
        $found = Paste::findBySlug($this->pdo, $created->getSlug());
        $this->assertFalse($found->isBurnAfterRead());
    }

    /**
     * @test
     */
    public function burnIfNeededDeletesSnippet(): void
    {
        $created = Paste::create($this->pdo, 'secret', 'php', 'never', null, true);
        $slug = $created->getSlug();

        // Verify it exists before burn
        $before = Paste::findBySlug($this->pdo, $slug);
        $this->assertNotNull($before);

        // Burn it
        Paste::burnIfNeeded($this->pdo, $slug);

        // Verify it's gone after burn
        $after = Paste::findBySlug($this->pdo, $slug);
        $this->assertNull($after);
    }

    /**
     * @test
     */
    public function burnIfNeededDoesNothingWhenBurnFalse(): void
    {
        $created = Paste::create($this->pdo, 'secret', 'php', 'never', null, false);
        $slug = $created->getSlug();

        Paste::burnIfNeeded($this->pdo, $slug);

        // Snippet should still exist
        $found = Paste::findBySlug($this->pdo, $slug);
        $this->assertNotNull($found);
        $this->assertSame('secret', $found->getContent());
    }

    /**
     * @test
     */
    public function burnWithPasswordProtection(): void
    {
        $created = Paste::create($this->pdo, 'secret', 'php', 'never', 'pass123', true);
        $slug = $created->getSlug();

        $before = Paste::findBySlug($this->pdo, $slug);
        $this->assertTrue($before->isBurnAfterRead());
        $this->assertNotNull($before->getPasswordHash());

        Paste::burnIfNeeded($this->pdo, $slug);

        $after = Paste::findBySlug($this->pdo, $slug);
        $this->assertNull($after);
    }

    /**
     * @test
     */
    public function burnAfterViewIncrement(): void
    {
        $created = Paste::create($this->pdo, 'secret', 'php', 'never', null, true);
        $slug = $created->getSlug();

        // Increment views (simulating a view)
        $views = Paste::incrementViews($this->pdo, $slug);
        $this->assertSame(1, $views);

        // Burn it
        Paste::burnIfNeeded($this->pdo, $slug);

        // Should be deleted
        $found = Paste::findBySlug($this->pdo, $slug);
        $this->assertNull($found);
    }

    /**
     * @test
     */
    public function snippetAvailableAfterViewUntilBurned(): void
    {
        $created = Paste::create($this->pdo, 'shareable secret', 'php', 'never', null, true);
        $slug = $created->getSlug();

        // User views the snippet
        $paste1 = Paste::findBySlug($this->pdo, $slug);
        $this->assertNotNull($paste1);
        Paste::incrementViews($this->pdo, $slug);

        // Snippet still exists (can share URL)
        $paste2 = Paste::findBySlug($this->pdo, $slug);
        $this->assertNotNull($paste2);
        $this->assertSame('shareable secret', $paste2->getContent());

        // User leaves page - snippet burned
        Paste::burnIfNeeded($this->pdo, $slug);

        // Now it's gone
        $paste3 = Paste::findBySlug($this->pdo, $slug);
        $this->assertNull($paste3);
    }
}
