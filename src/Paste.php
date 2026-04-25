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
            $original['content'],
            $original['language'],
            $original['expires_at'] ? self::getExpiresOption($original['expires_at']) : 'never',
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
