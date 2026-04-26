<?php

namespace Snipptr;

use PDO;
use Snipptr\Entity\PasteEntity;

class Paste
{
    public static function create(
        PDO $pdo,
        string $content,
        string $language,
        string $expires,
        ?string $password,
        bool $burnAfterRead = false
    ): PasteEntity {
        $slug = Slug::unique($pdo);
        $expiresAt = match ($expires) {
            '1h'  => date('Y-m-d H:i:s', strtotime('+1 hour')),
            '24h' => date('Y-m-d H:i:s', strtotime('+24 hours')),
            '7d'  => date('Y-m-d H:i:s', strtotime('+7 days')),
            default => null,
        };

        $stmt = $pdo->prepare('
            INSERT INTO pastes (slug, content, language, expires_at, password_hash, burn_after_read)
            VALUES (:slug, :content, :language, :expires_at, :password_hash, :burn_after_read)
            RETURNING *
        ');
        $stmt->execute([
            ':slug'          => $slug,
            ':content'       => $content,
            ':language'      => $language,
            ':expires_at'    => $expiresAt,
            ':password_hash' => $password ? password_hash($password, PASSWORD_BCRYPT) : null,
            ':burn_after_read' => $burnAfterRead ? 'true' : 'false',
        ]);

        return PasteEntity::fromArray($stmt->fetch());
    }

    public static function findBySlug(
        PDO $pdo,
        string $slug
    ): ?PasteEntity {
        $stmt = $pdo->prepare('SELECT * FROM pastes WHERE slug = ?');
        $stmt->execute([$slug]);
        $paste = $stmt->fetch();

        if (!$paste) {
            return null;
        }

        $entity = PasteEntity::fromArray($paste);
        if ($entity->isExpired()) {
            self::delete($pdo, $slug);
            return null;
        }

        return $entity;
    }

    public static function incrementViews(
        PDO $pdo,
        string $slug
    ): int {
        $stmt = $pdo->prepare('UPDATE pastes SET views = views + 1 WHERE slug = ? RETURNING views');
        $stmt->execute([$slug]);
        return (int)($stmt->fetchColumn() ?: 0);
    }

    public static function delete(
        PDO $pdo,
        string $slug
    ): void {
        $pdo->prepare('DELETE FROM pastes WHERE slug = ?')->execute([$slug]);
    }

    public static function burnIfNeeded(
        PDO $pdo,
        string $slug
    ): void {
        $stmt = $pdo->prepare('SELECT burn_after_read FROM pastes WHERE slug = ?');
        $stmt->execute([$slug]);
        $result = $stmt->fetch();

        if ($result && $result['burn_after_read']) {
            self::delete($pdo, $slug);
        }
    }

    private static function hashIp(string $ip): string
    {
        $salt = date('Y-m-d_H');
        return hash('sha256', $ip . $salt);
    }

    public static function isRateLimited(
        PDO $pdo,
        string $ip
    ): bool {
        $hash = self::hashIp($ip);
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM rate_limits
            WHERE ip_hash = ? AND created_at > NOW() - INTERVAL '1 hour'
        ");
        $stmt->execute([$hash]);
        return (int)$stmt->fetchColumn() >= 10;
    }

    public static function trackRequest(
        PDO $pdo,
        string $ip
    ): void {
        $hash = self::hashIp($ip);
        $pdo->prepare('INSERT INTO rate_limits (ip_hash) VALUES (?)')->execute([$hash]);
        $pdo->prepare("DELETE FROM rate_limits WHERE created_at < NOW() - INTERVAL '2 hours'")->execute();
    }

    public static function fork(
        PDO $pdo,
        string $slug
    ): ?array {
        $original = self::findBySlug($pdo, $slug);
        if (!$original) {
            return null;
        }

        return self::create(
            $pdo,
            $original->getContent(),
            $original->getLanguage(),
            $original->getExpiresAt() ? self::getExpiresOption($original->getExpiresAt()) : 'never',
            null
        );
    }

    private static function getExpiresOption(string $expiresAt): string
    {
        $expiresTime = strtotime($expiresAt);
        $now = time();
        $diff = $expiresTime - $now;

        if ($diff > 604800) return '7d';
        if ($diff > 86400) return '24h';
        if ($diff > 3600) return '1h';
        return 'never';
    }
}
