<section class="page-heading">
    <p class="eyebrow">Administrimi</p>
    <h1>Menaxhimi i stafit</h1>
</section>
<section class="panel">
    <h2>Krijo llogari recepsionisti</h2>
    <form method="post" action="/admin/staff" class="form-grid">
        <input type="hidden" name="_csrf" value="<?= e(\HotelHub\Core\Csrf::token()) ?>">
        <div>
            <label for="name">Emri i plotë</label>
            <input id="name" name="name" maxlength="100" required value="<?= e($old['name'] ?? '') ?>">
            <?php if (isset($errors['name'])): ?><small class="field-error"><?= e($errors['name']) ?></small><?php endif; ?>
        </div>
        <div>
            <label for="email">Email</label>
            <input id="email" name="email" type="email" maxlength="150" required value="<?= e($old['email'] ?? '') ?>">
            <?php if (isset($errors['email'])): ?><small class="field-error"><?= e($errors['email']) ?></small><?php endif; ?>
        </div>
        <div>
            <label for="password">Fjalëkalimi (të paktën 8 karaktere)</label>
            <input id="password" name="password" type="password" minlength="8" required autocomplete="new-password">
            <?php if (isset($errors['password'])): ?><small class="field-error"><?= e($errors['password']) ?></small><?php endif; ?>
        </div>
        <div>
            <label for="password_confirmation">Përsërit fjalëkalimin</label>
            <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" required>
            <?php if (isset($errors['password_confirmation'])): ?>
                <small class="field-error"><?= e($errors['password_confirmation']) ?></small>
            <?php endif; ?>
        </div>
        <button class="button" type="submit">Krijo recepsionistin</button>
    </form>
</section>
<section class="panel">
    <h2>Llogaritë e stafit</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Emri</th><th>Email</th><th>Roli</th></tr></thead>
            <tbody>
            <?php foreach ($staff as $member): ?>
                <tr>
                    <td><?= e($member['name']) ?></td>
                    <td><?= e($member['email']) ?></td>
                    <td><?= e($member['role'] === 'admin' ? 'Administrator' : 'Recepsionist') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
