<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Snipptr\Csrf;
use Snipptr\Database;
use Snipptr\Paste;
use Snipptr\PasteInput;
use Snipptr\Request;
use Snipptr\Response;

$pdo       = Database::connect();
$csrfToken = Csrf::token();
$error     = null;

if (Request::isPost()) {
    Csrf::check();

    $ip    = Request::getIp();
    $input = PasteInput::fromPost($_POST);

    if (Paste::isRateLimited($pdo, $ip)) {
        $error = 'Rate limit exceeded. Max 10 pastes per hour.';
    } elseif (!$input->isValid()) {
        $error = $input->error;
    } else {
        Paste::trackRequest($pdo, $ip);
        $paste = Paste::create($pdo, $input->content, $input->language, $input->expires, $input->password);
        Response::redirect('/p/' . $paste['slug']);
    }
}

$languages = [
    'plaintext'  => 'Plain Text',
    'php'        => 'PHP',
    'javascript' => 'JavaScript',
    'typescript' => 'TypeScript',
    'python'     => 'Python',
    'html'       => 'HTML',
    'css'        => 'CSS',
    'sql'        => 'SQL',
    'bash'       => 'Bash',
    'json'       => 'JSON',
    'xml'        => 'XML',
    'go'         => 'Go',
    'rust'       => 'Rust',
    'java'       => 'Java',
    'c'          => 'C',
    'cpp'        => 'C++',
    'markdown'   => 'Markdown',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snipptr - Share Code</title>
    <link rel="icon" type="image/png" href="/assets/favicon.png">
    <link rel="stylesheet" href="/assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/dracula.min.css">
</head>
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
                    <option value="never">Never</option>
                    <option value="1h">1 hour</option>
                    <option value="24h">24 hours</option>
                    <option value="7d">7 days</option>
                </select>

                <input type="password" name="password" placeholder="Password (optional)" autocomplete="off">
            </div>

            <textarea id="editor" name="content" placeholder="Paste your code here..."><?= isset($_POST['content']) ? htmlspecialchars($_POST['content']) : '' ?></textarea>

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
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/rust/rust.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/clike/clike.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/markdown/markdown.min.js"></script>
<script src="/assets/editor.js"></script>
</body>
</html>
