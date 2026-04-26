<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Snipptr\Csrf;
use Snipptr\Database;
use Snipptr\Paste;
use Snipptr\Request;
use Snipptr\Response;
use Snipptr\Constants\Lang;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$pdo = Database::connect();
$csrfToken = Csrf::token();
$slug = Request::getSlug();
$paste = Paste::findBySlug($pdo, $slug);
if (!$paste) {
    Response::notFound(file_get_contents(__DIR__ . '/../templates/404.php'));
}

$unlocked = !$paste->isPasswordProtected();
$wrongPass = false;

if ($paste->isPasswordProtected() && Request::isPost()) {
    Csrf::check();
    if (password_verify($_POST['password'] ?? '', $paste->getPasswordHash())) {
        $unlocked = true;
    } else {
        $wrongPass = true;
    }
}

$isFirstView = $paste->getViews() === 0;
if ($unlocked && !$wrongPass) {
    $views = Paste::incrementViews($pdo, $slug);
} else {
    $views = $paste->getViews();
}

$expiresMs = $paste->getExpiresAt() ? (int)(strtotime($paste->getExpiresAt()) * 1000) : null;
$lang = htmlspecialchars($paste->getLanguage());
$content = htmlspecialchars($paste->getContent());

$prismLang = Lang::getPrismLanguage($paste->getLanguage());
$title = "snipptr - {$lang}";
$extraStyles = [
    'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.css',
];
?>
<!DOCTYPE html>
<html lang="en">
<?php include __DIR__ . '/../templates/head.php'; ?>
<body>
<div class="page">
    <header class="site-header">
        <a href="/" class="logo">snipptr</a>
        <nav class="view-meta">
            <span class="badge"><?= $lang ?></span>
            <span class="badge"><?= $views ?> views</span>
            <?php if ($paste->getExpiresAt()): ?>
                <span class="badge" id="countdown">…</span>
            <?php endif; ?>
            <?php if ($unlocked && !$paste->isPasswordProtected()): ?>
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
        <?php if ($paste->isBurnAfterRead()): ?>
        <div class="burn-warning">
            <strong>Read and Burn:</strong> This snippet will be deleted after <?= $isFirstView ? 'the next display' : 'you close this page. Screenshot now if needed' ?>.
        </div>
        <?php endif; ?>
        <div class="copy-bar">
            <button id="copy-code-btn" class="btn-copy">Copy code</button>
            <button id="copy-url-btn" class="btn-copy">Copy URL</button>
            <?php if (!$paste->isBurnAfterRead()): ?>
            <button id="fork-btn" class="btn-copy">Fork</button>
            <?php endif; ?>
        </div>
        <pre class="line-numbers"><code class="language-<?= $prismLang ?>"><?= $content ?></code></pre>
        <?php if (!$paste->isBurnAfterRead()): ?>
        <div class="qr-section">
            <div id="qr-container"></div>
            <button id="qr-toggle" class="btn-secondary">Show QR</button>
        </div>
        <?php endif; ?>
    </div>
        </main>

    <script>
    window.snipptrData = {
        content: <?= json_encode($paste->getContent(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        slug: <?= json_encode($slug) ?>,
        expiresMs: <?= $expiresMs ?? 'null' ?>,
        url: <?= json_encode('http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:8080') . '/p/' . $slug) ?>,
        burnAfterRead: <?= $paste->isBurnAfterRead() ? 'true' : 'false' ?>,
    };

    if (window.snipptrData.burnAfterRead) {
        window.addEventListener('pagehide', () => {
            navigator.sendBeacon('/burn.php', new URLSearchParams({slug: window.snipptrData.slug}));
        });
    }
    </script>
    <script src="/assets/view.js"></script>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</body>
</html>
