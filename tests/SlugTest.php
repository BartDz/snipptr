<?php
namespace Snipptr\Tests;

use PHPUnit\Framework\TestCase;
use Snipptr\Slug;

class SlugTest extends TestCase
{
    public function test_generate_returns_7_chars(): void
    {
        $this->assertSame(7, strlen(Slug::generate()));
    }

    public function test_generate_is_alphanumeric(): void
    {
        $this->assertMatchesRegularExpression('/^[a-z0-9]+$/', Slug::generate());
    }

    public function test_generates_unique_slugs(): void
    {
        $slugs = array_map(fn($_) => Slug::generate(), range(1, 100));
        $this->assertCount(100, array_unique($slugs));
    }
}
