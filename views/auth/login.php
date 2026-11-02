<section class="form-card">
    <p class="eyebrow">Llogaria juaj</p>
    <h1>Hyr në HotelHub</h1>
    <?php if ($error !== null): ?>
        <p class="form-error" role="alert"><?= e($error) ?></p>
    <?php endif; ?>
    <form method="post" action="/login">
        <input type="hidden" name="_csrf" value="<?= e(\HotelHub\Core\Csrf::token()) ?>">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" maxlength="150" required autocomplete="email"
            value="<?= e($old['email'] ?? '') ?>">
        <label for="password">Fjalëkalimi</label>
        <input id="password" name="password" type="password" required autocomplete="current-password">
        <button class="button" type="submit">Hyr</button>
    </form>
    <p>Nuk ke llogari? <a href="/register">Regjistrohu</a>.</p>
</section>
