<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Snipptr\Csrf;
use Snipptr\Database;
use Snipptr\Paste;
use Snipptr\Request;
use Snipptr\Response;

$pdo       = Database::connect();
$csrfToken = Csrf::token();
$slug      = Request::getSlug();
$paste = Paste::findBySlug($pdo, $slug);

if (!$paste) {
    Response::notFound('<!DOCTYPE html><html><head><title>404</title></head><body style="font-family:monospace;padding:2rem"><h1>404 - Snippet not found or expired.</h1><a href="/">← New paste</a></body></html>');
}

$hasPassword = $paste['password_hash'] !== null;
$unlocked    = !$hasPassword;
$wrongPass   = false;

if ($hasPassword && Request::isPost()) {
    Csrf::check();
    if (password_verify($_POST['password'] ?? '', $paste['password_hash'])) {
        $unlocked = true;
    } else {
        $wrongPass = true;
    }
}

if ($unlocked && !$wrongPass) {
    $views = Paste::incrementViews($pdo, $slug);
} else {
    $views = (int)$paste['views'];
}

$expiresMs = $paste['expires_at'] ? (int)(strtotime($paste['expires_at']) * 1000) : null;
$lang      = htmlspecialchars($paste['language']);
$content   = htmlspecialchars($paste['content']);

$prismLang = match ($paste['language']) {
    'html', 'xml' => 'markup',
    'bash'        => 'bash',
    'c'           => 'c',
    'cpp'         => 'cpp',
    'css'         => 'css',
    'go'          => 'go',
    'java'        => 'java',
    'javascript'  => 'javascript',
    'json'        => 'json',
    'markdown'    => 'markdown',
    'php'         => 'php',
    'python'      => 'python',
    'rust'        => 'rust',
    'sql'         => 'sql',
    'typescript'  => 'typescript',
    default       => 'none',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>snipptr - <?= $lang ?></title>
    <link rel="icon" type="image/png" href="/assets/favicon.png">
    <link rel="stylesheet" href="/assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.css">
</head>
<body>
<div class="page">
    <header class="site-header">
        <a href="/" class="logo">snipptr</a>
        <nav class="view-meta">
            <span class="badge"><?= $lang ?></span>
            <span class="badge"><?= $views ?> views</span>
            <?php if ($paste['expires_at']): ?>
                <span class="badge" id="countdown">…</span>
            <?php endif; ?>
            <?php if ($unlocked && !$hasPassword): ?>
                <a href="/p/<?= htmlspecialchars($slug) ?>/raw" class="badge badge-link">Raw</a>
            <?php endif; ?>
        </nav>
    </header>

    <?php if (!$unlocked): ?>
    <main class="glass-panel password-gate">
        <h2>This snippet is password protected</h2>
        <form method="POST">
            <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
            <input type="password" name="password" placeholder="Enter password" autofocus>
            <?php if ($wrongPass): ?>
                <p class="error-msg">Wrong password.</p>
            <?php endif; ?>
            <button type="submit" class="btn-primary">Unlock</button>
        </form>
    </main>

    <?php else: ?>
    <main class="snippet-view fade-in">
        <div class="copy-bar">
            <button id="copy-btn" class="btn-copy">Copy</button>
            <button id="fork-btn" class="btn-copy">Fork</button>
        </div>
        <pre class="line-numbers"><code class="language-<?= $prismLang ?>"><?= $content ?></code></pre>
    </main>

    <script>
    window.snipptrData = {
        content:   <?= json_encode($paste['content'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        slug:      <?= json_encode($slug) ?>,
        expiresMs: <?= $expiresMs ?? 'null' ?>,
    };
    </script>
    <script src="/assets/view.js"></script>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.js"></script>
</body>
</html>
