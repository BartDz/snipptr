<?php

namespace Snipptr;

use Snipptr\Constants\Lang;
use Snipptr\Constants\Expire;

class PasteInput
{
    private const MAX_LEN = 1048576;
    private const MESSAGE_EMPTY = 'Content cannot be empty.';
    private const MESSAGE_TOO_LONG = 'Content too large. Max 1 MB.';

    public readonly string  $content;
    public readonly string  $language;
    public readonly string  $expires;
    public readonly ?string $password;
    public readonly ?string $error;

    private function __construct(
        string $content,
        string $language,
        string $expires,
        ?string $password
    ) {
        $this->content  = $content;
        $this->language = $language;
        $this->expires  = $expires;
        $this->password = $password;
        $this->error    = $this->validate();
    }

    public static function fromPost(array $post): self
    {
        $lang = $post['language'] ?? '';
        $exp  = $post['expires'] ?? '';

        return new self(
            trim($post['content'] ?? ''),
            in_array($lang, self::getLangs(), true) ? $lang : 'plaintext',
            in_array($exp, self::getExpirations(), true) ? $exp : 'never',
            trim($post['password'] ?? '') ?: null,
        );
    }

    public static function fromJson(array $body): self
    {
        $lang = strtolower(preg_replace('/[^a-z0-9+]/', '', $body['language'] ?? ''));
        $exp  = $body['expires'] ?? '';

        return new self(
            trim($body['content'] ?? ''),
            in_array($lang, self::getLangs(), true) ? $lang : 'plaintext',
            in_array($exp, self::getExpirations(), true) ? $exp : 'never',
            trim($body['password'] ?? '') ?: null,
        );
    }

    public function isValid(): bool
    {
        return $this->error === null;
    }

    private function validate(): ?string
    {
        if ($this->content === '') {
            return self::MESSAGE_EMPTY;
        }
        if (mb_strlen($this->content) > self::MAX_LEN) {
            return self::MESSAGE_TOO_LONG;
        }
        return null;
    }

    private static function getLangs(): array
    {
        return Lang::getConstants();
    }

    private static function getExpirations(): array
    {
        return Expire::getConstants();
    }
}
