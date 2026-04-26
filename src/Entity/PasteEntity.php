<?php

namespace Snipptr\Entity;

class PasteEntity
{
    private int $id;
    private string $slug;
    private string $content;
    private string $language;
    private ?string $expiresAt;
    private ?string $passwordHash;
    private bool $burnAfterRead;
    private int $views;
    private string $createdAt;

    public function __construct(
        int $id,
        string $slug,
        string $content,
        string $language,
        ?string $expiresAt,
        ?string $passwordHash,
        bool $burnAfterRead,
        int $views,
        string $createdAt
    ) {
        $this->id = $id;
        $this->slug = $slug;
        $this->content = $content;
        $this->language = $language;
        $this->expiresAt = $expiresAt;
        $this->passwordHash = $passwordHash;
        $this->burnAfterRead = $burnAfterRead;
        $this->views = $views;
        $this->createdAt = $createdAt;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int)$data['id'],
            $data['slug'],
            $data['content'],
            $data['language'],
            $data['expires_at'] ?? null,
            $data['password_hash'] ?? null,
            (bool)$data['burn_after_read'],
            (int)$data['views'],
            $data['created_at']
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getExpiresAt(): ?string
    {
        return $this->expiresAt;
    }

    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function getBurnAfterRead(): bool
    {
        return $this->burnAfterRead;
    }

    public function isBurnAfterRead(): bool
    {
        return $this->burnAfterRead;
    }
    public function getViews(): int
    {
        return $this->views;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function isPasswordProtected(): bool
    {
        return $this->passwordHash !== null;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt && strtotime($this->expiresAt) < time();
    }
}
