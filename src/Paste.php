<?php

namespace Snipptr;

use PDO;

class Paste
{
    public static function create(
        PDO $pdo,
        string $content,
        string $language,
        string $expires,
        ?string $password
    ): array {
        $slug = Slug::unique($pdo);
        $expiresAt = match ($expires) {
            '1h'  => date('Y-m-d H:i:s', strtotime('+1 hour')),
            '24h' => date('Y-m-d H:i:s', strtotime('+24 hours')),
            '7d'  => date('Y-m-d H:i:s', strtotime('+7 days')),
            default => null,
        };

        $stmt = $pdo->prepare('
            INSERT INTO pastes (slug, content, language, expires_at, password_hash)
            VALUES (:slug, :content, :language, :expires_at, :password_hash)
            RETURNING id, slug, created_at, expires_at
        ');
        $stmt->execute([
            ':slug'          => $slug,
            ':content'       => $content,
            ':language'      => $language,
            ':expires_at'    => $expiresAt,
            ':password_hash' => $password ? password_hash($password, PASSWORD_BCRYPT) : null,
        ]);

        return $stmt->fetch();
    }

    public static function findBySlug(
        PDO $pdo,
        string $slug
    ): ?array {
        $stmt = $pdo->prepare('SELECT * FROM pastes WHERE slug = ?');
        $stmt->execute([$slug]);
        $paste = $stmt->fetch();

        if (!$paste) {
            return null;
        }

        if ($paste['expires_at'] && strtotime($paste['expires_at']) < time()) {
            self::delete($pdo, $slug);
            return null;
        }

        return $paste;
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

    public static function isRateLimited(
        PDO $pdo,
        string $ip
    ): bool
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM rate_limits
            WHERE ip = ? AND created_at > NOW() - INTERVAL '1 hour'
        ");
        $stmt->execute([$ip]);
        return (int)$stmt->fetchColumn() >= 10;
    }

    public static function trackRequest(
        PDO $pdo,
        string $ip
    ): void {
        $pdo->prepare('INSERT INTO rate_limits (ip) VALUES (?)')->execute([$ip]);
        $pdo->prepare("DELETE FROM rate_limits WHERE created_at < NOW() - INTERVAL '2 hours'")->execute();
    }
}
