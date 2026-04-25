<?php
namespace Snipptr\Tests;

use PHPUnit\Framework\TestCase;
use Snipptr\Slug;

class SlugTest extends TestCase
{
    /**
     * @test
     */
    public function generateReturns7Chars(): void
    {
        $this->assertSame(7, strlen(Slug::generate()));
    }

    /**
     * @test
     */
    public function generateIsAlphanumeric(): void
    {
        $this->assertMatchesRegularExpression('/^[a-z0-9]+$/', Slug::generate());
    }

    /**
     * @test
     */
    public function generatesUniqueSlugs(): void
    {
        $slugs = array_map(fn($_) => Slug::generate(), range(1, 100));
        $this->assertCount(100, array_unique($slugs));
    }
}
