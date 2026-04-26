<?php
namespace Snipptr\Tests;

use PHPUnit\Framework\TestCase;
use Snipptr\PasteInput;

class PasteInputTest extends TestCase
{
    /**
     * @dataProvider invalidContentProvider
     * @test
     */
    public function invalidContentIsRejected(string $content, string $expectedError): void
    {
        $input = PasteInput::fromPost(['content' => $content]);
        $this->assertFalse($input->isValid());
        $this->assertStringContainsStringIgnoringCase($expectedError, $input->error);
    }

    public static function invalidContentProvider(): array
    {
        return [
            'empty content' => ['   ', 'empty'],
            'content over limit' => [str_repeat('x', 1048577), 'too large'],
        ];
    }

    /**
     * @dataProvider fallbackValueProvider
     * @test
     */
    public function invalidValuesFallBackToDefaults(array $input, string $field, string $expected): void
    {
        $pasteInput = PasteInput::fromPost(['content' => 'hello', ...$input]);
        $this->assertSame($expected, $pasteInput->$field);
    }

    public static function fallbackValueProvider(): array
    {
        return [
            'unknown language' => [['language' => 'cobol'], 'language', 'plaintext'],
            'unknown expires' => [['expires' => '1year'], 'expires', 'never'],
        ];
    }

    /**
     * @dataProvider passwordProvider
     * @test
     */
    public function passwordHandlingWorksCorrectly(string $password, ?string $expected): void
    {
        $input = PasteInput::fromPost(['content' => 'hello', 'password' => $password]);
        $this->assertSame($expected, $input->password);
    }

    public static function passwordProvider(): array
    {
        return [
            'whitespace password' => ['   ', null],
            'valid password' => ['secret', 'secret'],
        ];
    }
    /**
     * @test
     */
    public function validPostInput(): void
    {
        $input = PasteInput::fromPost(['content' => 'echo 1', 'language' => 'php', 'expires' => '24h', 'password' => '']);
        $this->assertTrue($input->isValid());
        $this->assertSame('echo 1', $input->content);
        $this->assertSame('php', $input->language);
        $this->assertSame('24h', $input->expires);
        $this->assertNull($input->password);
    }


    /**
     * @dataProvider jsonValidationProvider
     * @test
     */
    public function jsonInputValidationWorksCorrectly(array $input, bool $shouldBeValid, array $expectedValues = []): void
    {
        $pasteInput = PasteInput::fromJson($input);
        $this->assertSame($shouldBeValid, $pasteInput->isValid());

        if (!empty($expectedValues)) {
            foreach ($expectedValues as $field => $expected) {
                $this->assertSame($expected, $pasteInput->$field);
            }
        }
    }

    public static function jsonValidationProvider(): array
    {
        return [
            'valid json input' => [
                ['content' => 'SELECT 1', 'language' => 'sql', 'expires' => '1h'],
                true,
                ['language' => 'sql', 'expires' => '1h']
            ],
            'json strips invalid language chars' => [
                ['content' => 'hello', 'language' => 'not-a-lang!'],
                true,
                ['language' => 'plaintext']
            ],
            'json empty body is invalid' => [
                [],
                false
            ],
        ];
    }

    /**
     * @test
     */
    public function burnAfterReadFromPostWhenChecked(): void
    {
        $input = PasteInput::fromPost(['content' => 'test', 'burn_after_read' => 'on']);
        $this->assertTrue($input->burnAfterRead);
    }

    /**
     * @test
     */
    public function burnAfterReadFromPostDefaultsFalse(): void
    {
        $input = PasteInput::fromPost(['content' => 'test']);
        $this->assertFalse($input->burnAfterRead);
    }

    /**
     * @test
     */
    public function burnAfterReadFromJsonWhenTrue(): void
    {
        $input = PasteInput::fromJson(['content' => 'test', 'burn_after_read' => true]);
        $this->assertTrue($input->burnAfterRead);
    }

    /**
     * @test
     */
    public function burnAfterReadFromJsonDefaultsFalse(): void
    {
        $input = PasteInput::fromJson(['content' => 'test']);
        $this->assertFalse($input->burnAfterRead);
    }

    /**
     * @test
     */
    public function burnAfterReadFromJsonIgnoresFalseValue(): void
    {
        $input = PasteInput::fromJson(['content' => 'test', 'burn_after_read' => false]);
        $this->assertFalse($input->burnAfterRead);
    }
}
