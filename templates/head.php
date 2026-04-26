<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Snipptr' ?></title>
    <link rel="icon" type="image/png" href="/assets/favicon.png">
    <link rel="stylesheet" href="/assets/style.css">
    <?php if (isset($extraStyles)): ?>
        <?php foreach ($extraStyles as $style): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($style) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
