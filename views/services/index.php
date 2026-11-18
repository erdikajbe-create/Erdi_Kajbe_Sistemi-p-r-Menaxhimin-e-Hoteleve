<section class="page-heading">
    <p class="eyebrow">Administrimi</p>
    <h1>Shërbimet shtesë</h1>
    <p class="muted">Ndryshimi i çmimit vlen vetëm për shtimet e reja; faturat ekzistuese mbajnë çmimin e momentit të shtimit.</p>
</section>

<section class="panel">
    <h2>Shto shërbim</h2>
    <form method="post" action="/admin/services" class="form-grid">
        <?= csrf_field() ?>
        <div>
            <label for="name">Emri</label>
            <input id="name" name="name" maxlength="80" required value="<?= e($editing === null ? ($old['name'] ?? '') : '') ?>">
            <?= $editing === null ? field_error($errors, 'name') : '' ?>
        </div>
        <div>
            <label for="price">Çmimi (€)</label>
            <input id="price" name="price" inputmode="decimal" required value="<?= e($editing === null ? ($old['price'] ?? '') : '') ?>">
            <?= $editing === null ? field_error($errors, 'price') : '' ?>
        </div>
        <button class="button" type="submit">Shto shërbimin</button>
    </form>
</section>

<section class="panel">
    <h2>Katalogu</h2>
    <?php if ($services === []): ?><p class="muted">Nuk ka ende shërbime.</p><?php endif; ?>
    <div class="management-list">
        <?php foreach ($services as $service): ?>
            <?php $isEditing = $editing === (int) $service['id']; ?>
            <form method="post" action="/admin/services/<?= e($service['id']) ?>" class="management-item service-row">
                <?= csrf_field() ?>
                <div>
                    <label for="service-name-<?= e($service['id']) ?>">Emri</label>
                    <input id="service-name-<?= e($service['id']) ?>" name="name" maxlength="80" required
                        value="<?= e($isEditing ? ($old['name'] ?? $service['name']) : $service['name']) ?>">
                    <?= $isEditing ? field_error($errors, 'name') : '' ?>
                </div>
                <div>
                    <label for="service-price-<?= e($service['id']) ?>">Çmimi (€)</label>
                    <input id="service-price-<?= e($service['id']) ?>" name="price" inputmode="decimal" required
                        value="<?= e($isEditing ? ($old['price'] ?? $service['price']) : $service['price']) ?>">
                    <?= $isEditing ? field_error($errors, 'price') : '' ?>
                </div>
                <label class="checkbox-label">
                    <input type="checkbox" name="active" value="1" <?= (bool) $service['active'] ? 'checked' : '' ?>> Aktiv
                </label>
                <button class="button" type="submit">Ruaj</button>
            </form>
        <?php endforeach; ?>
    </div>
</section>
