<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Snipptr\Csrf;
use Snipptr\Database;
use Snipptr\Paste;
use Snipptr\PasteInput;
use Snipptr\Request;
use Snipptr\Response;
use Snipptr\Constants\Lang;
use Snipptr\Constants\Expire;

session_start();

$pdo = Database::connect();
$csrfToken = Csrf::token();
$error = null;
$forkContent = $_SESSION['fork_content'] ?? null;
unset($_SESSION['fork_content']);

if (Request::isPost()) {
    Csrf::check();

    $ip = Request::getIp();
    $input = PasteInput::fromPost($_POST);

    if (Paste::isRateLimited($pdo, $ip)) {
        $error = 'Rate limit exceeded. Max 10 pastes per hour.';
    } elseif (!$input->isValid()) {
        $error = $input->error;
    } else {
        Paste::trackRequest($pdo, $ip);
        $paste = Paste::create($pdo, $input->content, $input->language, $input->expires, $input->password, $input->burnAfterRead);
        Response::redirect('/p/' . $paste->getSlug());
    }
}

$languages = Lang::getSelectFieldOptions();
$expirations = Expire::getSelectFieldOptions();
$title = 'Snipptr - Share Code';
$extraStyles = [
    'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/dracula.min.css',
];
?>
<!DOCTYPE html>
<html lang="en">
<?php include __DIR__ . '/../templates/head.php'; ?>
<body>
<div class="page">
    <header class="site-header">
        <h1 class="logo">snipptr</h1>
        <p class="tagline">Paste. Share. Done.</p>
    </header>

    <main class="glass-panel">
        <?php if ($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="paste-form">
            <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
            <div class="form-row">
                <select name="language" id="language-select">
                    <?php foreach ($languages as $val => $label): ?>
                        <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="expires">
                    <?php foreach ($expirations as $val => $label): ?>
                        <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>

                <input type="password" name="password" placeholder="Password (optional)" autocomplete="off">
            </div>

            <textarea id="editor" name="content" placeholder="Paste your code here..."><?= $forkContent ? htmlspecialchars($forkContent) : (isset($_POST['content']) ? htmlspecialchars($_POST['content']) : '') ?></textarea>

            <div class="form-options">
                <label class="checkbox-label">
                    <input type="checkbox" name="burn_after_read">
                    Read and burn (delete after first view)
                </label>
            </div>

            <div class="form-actions">
                <button type="button" id="detect-btn" class="btn-secondary">Auto-detect language</button>
                <button type="submit" class="btn-primary">Share Snippet →</button>
            </div>
        </form>
    </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/php/php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/python/python.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/sql/sql.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/shell/shell.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/go/go.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/clike/clike.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/markdown/markdown.min.js"></script>
<script src="/assets/editor.js"></script>
</body>
</html>
