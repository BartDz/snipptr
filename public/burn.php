<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Snipptr\Database;
use Snipptr\Paste;

$pdo = Database::connect();
$slug = $_POST['slug'] ?? $_GET['slug'] ?? null;

if (!$slug) {
    http_response_code(400);
    exit;
}

Paste::burnIfNeeded($pdo, $slug);
http_response_code(204);
