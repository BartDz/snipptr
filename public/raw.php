<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Snipptr\Database;
use Snipptr\Paste;
use Snipptr\Request;
use Snipptr\Response;

$pdo = Database::connect();
$slug = Request::getSlug();
$paste = Paste::findBySlug($pdo, $slug);

if (!$paste || $paste->getPasswordHash() !== null) {
    Response::notFound();
}

Response::text($paste->getContent());
