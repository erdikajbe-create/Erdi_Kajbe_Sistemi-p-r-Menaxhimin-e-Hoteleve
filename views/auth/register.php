<section class="form-card">
    <p class="eyebrow">Llogari e re</p>
    <h1>Regjistrohu si mysafir</h1>
    <form method="post" action="/register">
        <input type="hidden" name="_csrf" value="<?= e(\HotelHub\Core\Csrf::token()) ?>">
        <label for="name">Emri dhe mbiemri</label>
        <input id="name" name="name" maxlength="100" required autocomplete="name"
            value="<?= e($old['name'] ?? '') ?>">
        <?php if (isset($errors['name'])): ?><small class="field-error"><?= e($errors['name']) ?></small><?php endif; ?>
        <label for="email">Email</label>
        <input id="email" name="email" type="email" maxlength="150" required autocomplete="email"
            value="<?= e($old['email'] ?? '') ?>">
        <?php if (isset($errors['email'])): ?><small class="field-error"><?= e($errors['email']) ?></small><?php endif; ?>
        <label for="password">Fjalëkalimi (të paktën 8 karaktere)</label>
        <input id="password" name="password" type="password" minlength="8" required autocomplete="new-password">
        <?php if (isset($errors['password'])): ?><small class="field-error"><?= e($errors['password']) ?></small><?php endif; ?>
        <label for="password_confirmation">Përsërit fjalëkalimin</label>
        <input id="password_confirmation" name="password_confirmation" type="password" minlength="8"
            required autocomplete="new-password">
        <?php if (isset($errors['password_confirmation'])): ?>
            <small class="field-error"><?= e($errors['password_confirmation']) ?></small>
        <?php endif; ?>
        <button class="button" type="submit">Krijo llogari</button>
    </form>
    <p>Ke llogari? <a href="/login">Hyr këtu</a>.</p>
</section>
