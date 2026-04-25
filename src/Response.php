<?php

namespace Snipptr;

class Response
{
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_THROW_ON_ERROR);
        exit;
    }

    public static function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    public static function notFound(string $html = ''): never
    {
        http_response_code(404);
        if ($html !== '') {
            echo $html;
        }
        exit;
    }

    public static function text(string $content): never
    {
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo $content;
        exit;
    }
}
