<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Snipptr\Database;
use Snipptr\Paste;
use Snipptr\PasteInput;
use Snipptr\Request;
use Snipptr\Response;

if (!Request::isPost()) {
    Response::json(['error' => 'Method Not Allowed'], 405);
}

$pdo = Database::connect();
$ip  = Request::getIp();

if (Paste::isRateLimited($pdo, $ip)) {
    Response::json(['error' => 'Rate limit exceeded. Max 10 pastes per hour.'], 429);
}

$input = PasteInput::fromJson(Request::getJsonBody());

if (!$input->isValid()) {
    Response::json(['error' => $input->error], 400);
}

Paste::trackRequest($pdo, $ip);
$paste = Paste::create($pdo, $input->content, $input->language, $input->expires, $input->password, $input->burnAfterRead);

$scheme  = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
$baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];

Response::json([
    'url'        => $baseUrl . '/p/' . $paste->getSlug(),
    'raw'        => $baseUrl . '/p/' . $paste->getSlug() . '/raw',
    'slug'       => $paste->getSlug(),
    'expires_at' => $paste->getExpiresAt() ?? null,
]);
