<!doctype html>
<html lang="sq">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · HotelHub</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="/">HotelHub</a>
        <span class="site-caption">Menaxhimi i hotelit</span>
    </header>
    <main class="container">
        <?php foreach (\HotelHub\Core\Flash::consumeAll() as $flash): ?>
            <div class="flash flash--<?= e($flash['type']) ?>" role="status">
                <?= e($flash['message']) ?>
            </div>
        <?php endforeach; ?>
        <?= $content ?>
    </main>
    <footer class="site-footer">
        <p>HotelHub · Sistem për menaxhimin e hotelit</p>
    </footer>
</body>
</html>
