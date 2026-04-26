<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Snipptr\Database;
use Snipptr\Paste;
use Snipptr\Request;
use Snipptr\Response;

if (!Request::isPost()) {
    Response::json(['error' => 'Method Not Allowed'], 405);
}

$pdo = Database::connect();
$slug = Request::getSlug();

$originalPaste = Paste::findBySlug($pdo, $slug);
if (!$originalPaste) {
    Response::json(['error' => 'Original snippet not found or expired'], 404);
}

session_start();
$_SESSION['fork_content'] = $originalPaste->getContent();

$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
$baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];

Response::json([
    'url' => $baseUrl . '/',
]);
