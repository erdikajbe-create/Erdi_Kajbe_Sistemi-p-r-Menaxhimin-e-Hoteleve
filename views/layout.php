<!doctype html>
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
<body>
    <?php
    $currentUser = \HotelHub\Core\Auth::user();
    $role = $currentUser['role'] ?? null;
    $currentPath = \HotelHub\Core\Request::path();
    $navLink = static function (string $href, string $label) use ($currentPath): string {
        $active = $currentPath === $href || ($href !== '/' && str_starts_with($currentPath, $href . '/'));

        return '<a href="' . e($href) . '"' . ($active ? ' aria-current="page"' : '') . '>' . e($label) . '</a>';
    };
    ?>
    <header class="site-header<?= $currentPath === '/' ? ' site-header--home' : '' ?>">
        <a class="brand" href="/">HotelHub</a>
        <nav class="site-nav" aria-label="Navigimi kryesor">
            <?= $navLink('/availability', 'Kërko dhoma') ?>
            <?php if ($role === 'guest'): ?>
                <?= $navLink('/reservations', 'Rezervimet e mia') ?>
            <?php elseif (in_array($role, ['admin', 'receptionist'], true)): ?>
                <?= $navLink('/reservations', 'Rezervimet') ?>
                <?= $navLink('/rooms', 'Dhomat') ?>
            <?php endif; ?>
            <?php if ($role === 'admin'): ?>
                <?= $navLink('/dashboard', 'Dashboard') ?>
                <details class="nav-menu">
                    <summary>Administrimi</summary>
                    <div class="nav-menu__items">
                        <?= $navLink('/admin/room-types', 'Llojet e dhomave') ?>
                        <?= $navLink('/admin/services', 'Shërbimet') ?>
                        <?= $navLink('/admin/staff', 'Stafi') ?>
                    </div>
                </details>
            <?php endif; ?>
            <?php if ($currentUser !== null): ?>
                <span class="nav-user"><?= e($currentUser['name']) ?></span>
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
    <main class="container<?= $currentPath === '/' ? ' container--home' : '' ?>">
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
    <?php foreach ($scripts ?? [] as $script): ?>
        <script src="<?= e($script) ?>" defer></script>
    <?php endforeach; ?>
    <?php if ($currentPath === '/'): ?><script src="/js/home.js" defer></script><?php endif; ?>
</body>
</html>
