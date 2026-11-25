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
    <div class="section-heading"><div><p class="eyebrow">Katalogu</p><h2>Shërbimet e hotelit</h2></div><span class="inventory-count"><?= count($services) ?> gjithsej</span></div>
    <?php if ($services === []): ?><p class="muted">Nuk ka ende shërbime.</p><?php endif; ?>
    <?php if ($services !== []): ?>
    <div class="collection-toolbar collection-toolbar--embedded" data-live-search data-items=".service-row" data-count-singular="shërbim" data-count-plural="shërbime">
        <div class="search-field"><span aria-hidden="true">⌕</span><label class="sr-only" for="service-search">Kërko shërbime</label><input id="service-search" type="search" data-search-input placeholder="Kërko sipas emrit ose çmimit…" autocomplete="off"></div>
        <label class="sr-only" for="service-active-filter">Filtro sipas statusit</label><select id="service-active-filter" data-search-filter aria-label="Filtro shërbimet"><option value="">Të gjitha</option><option value="active">Aktive</option><option value="inactive">Jo aktive</option></select>
        <span class="search-count" data-search-count aria-live="polite"><?= count($services) ?> shërbime</span>
    </div>
    <?php endif; ?>
    <div class="management-list" data-search-results>
        <?php foreach ($services as $service): ?>
            <?php $isEditing = $editing === (int) $service['id']; ?>
            <form method="post" action="/admin/services/<?= e($service['id']) ?>" class="management-item service-row" data-search-item data-status="<?= (bool) $service['active'] ? 'active' : 'inactive' ?>" data-search-text="<?= e($service['name'] . ' ' . $service['price']) ?>">
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
        <?php if ($services !== []): ?><p class="empty-state" data-search-empty hidden>Nuk u gjet asnjë shërbim.</p><?php endif; ?>
    </div>
</section>
