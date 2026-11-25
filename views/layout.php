<?php
/**
 * @var string $title
 * @var string $content
 */
?><!doctype html>
<html lang="sq">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(\HotelHub\Core\Csrf::token()) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <title><?= e($title) ?> · HotelHub</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<?php $pageKey = trim((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'), '/') ?: 'home'; ?>
<body class="page-<?= e(preg_replace('/[^a-z0-9-]/i', '-', $pageKey)) ?>">
    <header class="site-header">
        <a class="brand" href="/" aria-label="HotelHub — faqja kryesore">
            <span class="brand__mark" aria-hidden="true">H</span>
            <span class="brand__copy"><strong>HOTELHUB</strong><small>HOSPITALITY MANAGEMENT</small></span>
        </a>
        <nav class="site-nav" aria-label="Navigimi kryesor">
            <a href="/availability">Disponueshmëria</a>
            <?php $currentUser = \HotelHub\Core\Auth::user(); ?>
            <?php if ($currentUser !== null): ?>
                <?php if (in_array($currentUser['role'], ['admin', 'receptionist'], true)): ?>
                    <a href="/rooms">Dhomat</a>
                    <a href="/reservations">Rezervimet</a>
                <?php endif; ?>
                <?php if ($currentUser['role'] === 'guest'): ?>
                    <a href="/reservations">Rezervimet e mia</a>
                <?php endif; ?>
                <?php if ($currentUser['role'] === 'admin'): ?>
                    <a href="/dashboard">Dashboard</a>
                    <a href="/admin/room-types">Llojet e dhomave</a>
                    <a href="/admin/services">Shërbimet</a>
                    <a href="/admin/staff">Stafi</a>
                <?php endif; ?>
                <span><?= e($currentUser['name']) ?></span>
                <form method="post" action="/logout" class="inline-form">
                    <?= csrf_field() ?>
                    <button class="link-button" type="submit">Dil</button>
                </form>
            <?php else: ?>
                <?= $navLink('/login', 'Hyr') ?>
                <?= $navLink('/register', 'Regjistrohu') ?>
            <?php endif; ?>
        </nav>
    </header>
    <main class="container" id="main-content">
        <?php foreach (\HotelHub\Core\Flash::consumeAll() as $flash): ?>
            <div class="flash flash--<?= e($flash['type']) ?>" role="status">
                <?= e($flash['message']) ?>
            </div>
        <?php endforeach; ?>
        <?= $content ?>
    </main>
    <footer class="site-footer<?= $currentPath === '/' ? ' site-footer--home' : '' ?>">
        <?php if ($currentPath === '/'): ?>
            <div class="lux-footer"><div><a class="brand" href="/">HotelHub</a><p>Elegancë, rehati dhe mikpritje me zemër.</p></div><div><h2>Na kontaktoni</h2><p>+355 4 000 0000<br>mirëpritje@hotelhub.al</p></div><div><h2>Orari</h2><p>Recepsioni · 24 orë<br>Çdo ditë të javës</p></div><div><h2>Lidhje të shpejta</h2><a href="/availability">Disponueshmëria</a><a href="/login">Hyr në llogari</a></div><small>© <?= date('Y') ?> HotelHub · Të gjitha të drejtat e rezervuara</small></div>
        <?php else: ?>
            <p>HotelHub · Sistem për menaxhimin e hotelit</p>
        <?php endif; ?>
    </footer>
    <?php foreach (($scripts ?? []) as $script): ?>
        <script src="<?= e($script) ?>" defer></script>
    <?php endforeach; ?>
</body>
</html>
