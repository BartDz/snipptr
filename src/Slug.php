<?php

namespace Snipptr;

use PDO;

class Slug
{
    private const CHARS  = 'abcdefghijklmnopqrstuvwxyz0123456789';
    private const LENGTH = 7;
    private const MESSAGE_UNIQUE = 'Could not generate a unique slug.';

    public static function generate(): string
    {
        $slug = '';
        $max  = strlen(self::CHARS) - 1;
        for ($i = 0; $i < self::LENGTH; $i++) {
            $slug .= self::CHARS[random_int(0, $max)];
        }
        return $slug;
    }

    public static function unique(PDO $pdo): string
    {
        $stmt = $pdo->prepare('SELECT 1 FROM pastes WHERE slug = ?');
        for ($i = 0; $i < 10; $i++) {
            $slug = self::generate();
            $stmt->execute([$slug]);
            if (!$stmt->fetch()) {
                return $slug;
            }
        }
        throw new \RuntimeException(self::MESSAGE_UNIQUE);
    }
}
