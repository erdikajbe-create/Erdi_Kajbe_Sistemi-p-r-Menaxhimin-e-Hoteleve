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
        <nav class="site-nav" aria-label="Navigimi kryesor">
            <?php $currentUser = \HotelHub\Core\Auth::user(); ?>
            <?php if ($currentUser !== null): ?>
                <?php if (in_array($currentUser['role'], ['admin', 'receptionist'], true)): ?>
                    <a href="/rooms">Dhomat</a>
                <?php endif; ?>
                <?php if ($currentUser['role'] === 'admin'): ?>
                    <a href="/admin/room-types">Llojet e dhomave</a>
                    <a href="/admin/staff">Stafi</a>
                <?php endif; ?>
                <span><?= e($currentUser['name']) ?></span>
                <form method="post" action="/logout" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e(\HotelHub\Core\Csrf::token()) ?>">
                    <button class="link-button" type="submit">Dil</button>
                </form>
            <?php else: ?>
                <a href="/login">Hyr</a>
                <a href="/register">Regjistrohu</a>
            <?php endif; ?>
        </nav>
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
