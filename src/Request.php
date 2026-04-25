<?php

namespace Snipptr;

class Request
{
    public static function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    public static function getIp(): string
    {
        $ip = $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'];
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : $_SERVER['REMOTE_ADDR'];
    }

    public static function getSlug(): string
    {
        return preg_replace('/[^a-z0-9]/i', '', $_GET['slug'] ?? '');
    }

    public static function getJsonBody(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
}
