<?php

namespace Snipptr;

class Csrf
{
    public static function token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['_csrf'] ??= bin2hex(random_bytes(32));
    }

    public static function check(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $expected = $_SESSION['_csrf'] ?? '';
        $given    = $_POST['_csrf'] ?? '';
        if ($expected === '' || !hash_equals($expected, $given)) {
            http_response_code(403);
            exit('Bad request.');
        }
    }
}
