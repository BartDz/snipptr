<?php
namespace Snipptr\Tests;

use PHPUnit\Framework\TestCase;
use Snipptr\PasteInput;

class PasteInputTest extends TestCase
{
    public function test_valid_post_input(): void
    {
        $input = PasteInput::fromPost(['content' => 'echo 1', 'language' => 'php', 'expires' => '24h', 'password' => '']);
        $this->assertTrue($input->isValid());
        $this->assertSame('echo 1', $input->content);
        $this->assertSame('php', $input->language);
        $this->assertSame('24h', $input->expires);
        $this->assertNull($input->password);
    }

    public function test_empty_content_is_invalid(): void
    {
        $input = PasteInput::fromPost(['content' => '   ']);
        $this->assertFalse($input->isValid());
        $this->assertStringContainsStringIgnoringCase('empty', $input->error);
    }

    public function test_content_over_limit_is_invalid(): void
    {
        $input = PasteInput::fromPost(['content' => str_repeat('x', 64001)]);
        $this->assertFalse($input->isValid());
        $this->assertStringContainsStringIgnoringCase('too large', $input->error);
    }

    public function test_unknown_language_falls_back_to_plaintext(): void
    {
        $input = PasteInput::fromPost(['content' => 'hello', 'language' => 'cobol']);
        $this->assertSame('plaintext', $input->language);
    }

    public function test_unknown_expires_falls_back_to_never(): void
    {
        $input = PasteInput::fromPost(['content' => 'hello', 'expires' => '1year']);
        $this->assertSame('never', $input->expires);
    }

    public function test_whitespace_password_becomes_null(): void
    {
        $input = PasteInput::fromPost(['content' => 'hello', 'password' => '   ']);
        $this->assertNull($input->password);
    }

    public function test_password_is_preserved(): void
    {
        $input = PasteInput::fromPost(['content' => 'hello', 'password' => 'secret']);
        $this->assertSame('secret', $input->password);
    }

    public function test_valid_json_input(): void
    {
        $input = PasteInput::fromJson(['content' => 'SELECT 1', 'language' => 'sql', 'expires' => '1h']);
        $this->assertTrue($input->isValid());
        $this->assertSame('sql', $input->language);
        $this->assertSame('1h', $input->expires);
    }

    public function test_json_strips_invalid_language_chars(): void
    {
        $input = PasteInput::fromJson(['content' => 'hello', 'language' => 'not-a-lang!']);
        $this->assertSame('plaintext', $input->language);
    }

    public function test_json_empty_body_is_invalid(): void
    {
        $input = PasteInput::fromJson([]);
        $this->assertFalse($input->isValid());
    }
}
