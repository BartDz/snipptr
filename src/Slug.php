<?php
// src/Slug.php
namespace Snipptr;

use PDO;

class Slug
{
    private const CHARS  = 'abcdefghijklmnopqrstuvwxyz0123456789';
    private const LENGTH = 7;

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
        do {
            $slug = self::generate();
            $stmt = $pdo->prepare('SELECT 1 FROM pastes WHERE slug = ?');
            $stmt->execute([$slug]);
        } while ($stmt->fetch());

        return $slug;
    }
}
