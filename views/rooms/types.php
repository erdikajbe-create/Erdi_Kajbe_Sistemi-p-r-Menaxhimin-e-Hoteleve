<section class="page-heading">
    <p class="eyebrow">Administrimi</p>
    <h1>Llojet e dhomave dhe pajisjet</h1>
</section>

<section class="panel">
    <h2>Shto lloj dhome</h2>
    <form method="post" action="/admin/room-types" class="form-grid">
        <input type="hidden" name="_csrf" value="<?= e(\HotelHub\Core\Csrf::token()) ?>">
        <div>
            <label for="name">Emri</label>
            <input id="name" name="name" maxlength="80" required value="<?= e($old['name'] ?? '') ?>">
            <?php if (isset($errors['name'])): ?><small class="field-error"><?= e($errors['name']) ?></small><?php endif; ?>
        </div>
        <div>
            <label for="base_price">Çmimi për natë (€)</label>
            <input id="base_price" name="base_price" inputmode="decimal" required
                value="<?= e($old['base_price'] ?? '') ?>">
            <?php if (isset($errors['base_price'])): ?><small class="field-error"><?= e($errors['base_price']) ?></small><?php endif; ?>
        </div>
        <div>
            <label for="capacity">Kapaciteti</label>
            <input id="capacity" name="capacity" type="number" min="1" max="6" required
                value="<?= e($old['capacity'] ?? '1') ?>">
            <?php if (isset($errors['capacity'])): ?><small class="field-error"><?= e($errors['capacity']) ?></small><?php endif; ?>
        </div>
        <div class="form-grid__wide">
            <label for="description">Përshkrimi</label>
            <textarea id="description" name="description" rows="3"><?= e($old['description'] ?? '') ?></textarea>
            <?php if (isset($errors['description'])): ?><small class="field-error"><?= e($errors['description']) ?></small><?php endif; ?>
        </div>
        <fieldset class="form-grid__wide checkbox-group">
            <legend>Pajisjet</legend>
            <?php foreach ($amenities as $amenity): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="amenities[]" value="<?= e($amenity['id']) ?>">
                    <?= e($amenity['name']) ?>
                </label>
            <?php endforeach; ?>
            <?php if (isset($errors['amenities'])): ?><small class="field-error"><?= e($errors['amenities']) ?></small><?php endif; ?>
        </fieldset>
        <button class="button" type="submit">Shto llojin</button>
    </form>
</section>

<section class="panel">
    <h2>Llojet ekzistuese</h2>
    <?php if ($types === []): ?><p>Nuk ka ende lloje dhomash.</p><?php endif; ?>
    <div class="management-list">
        <?php foreach ($types as $type): ?>
            <article class="management-item">
                <form method="post" action="/admin/room-types/<?= e($type['id']) ?>" class="form-grid">
                    <input type="hidden" name="_csrf" value="<?= e(\HotelHub\Core\Csrf::token()) ?>">
                    <input type="hidden" name="id" value="<?= e($type['id']) ?>">
                    <div>
                        <label for="type-name-<?= e($type['id']) ?>">Emri</label>
                        <input id="type-name-<?= e($type['id']) ?>" name="name" maxlength="80" required
                            value="<?= e($formType === (int) $type['id'] ? ($old['name'] ?? $type['name']) : $type['name']) ?>">
                        <?php if ($formType === (int) $type['id'] && isset($errors['name'])): ?>
                            <small class="field-error"><?= e($errors['name']) ?></small>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="type-price-<?= e($type['id']) ?>">Çmimi (€)</label>
                        <input id="type-price-<?= e($type['id']) ?>" name="base_price" inputmode="decimal" required
                            value="<?= e($formType === (int) $type['id'] ? ($old['base_price'] ?? $type['base_price']) : $type['base_price']) ?>">
                        <?php if ($formType === (int) $type['id'] && isset($errors['base_price'])): ?>
                            <small class="field-error"><?= e($errors['base_price']) ?></small>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="type-capacity-<?= e($type['id']) ?>">Kapaciteti</label>
                        <input id="type-capacity-<?= e($type['id']) ?>" name="capacity" type="number" min="1" max="6" required
                            value="<?= e($formType === (int) $type['id'] ? ($old['capacity'] ?? $type['capacity']) : $type['capacity']) ?>">
                        <?php if ($formType === (int) $type['id'] && isset($errors['capacity'])): ?>
                            <small class="field-error"><?= e($errors['capacity']) ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="form-grid__wide">
                        <label for="type-description-<?= e($type['id']) ?>">Përshkrimi</label>
                        <textarea id="type-description-<?= e($type['id']) ?>" name="description" rows="2"><?= e($formType === (int) $type['id'] ? ($old['description'] ?? $type['description']) : $type['description']) ?></textarea>
                    </div>
                    <fieldset class="form-grid__wide checkbox-group">
                        <legend>Pajisjet</legend>
                        <?php foreach ($amenities as $amenity): ?>
                            <?php
                            $selectedAmenities = $formType === (int) $type['id'] && isset($old['amenities'])
                                ? array_map('intval', (array) $old['amenities'])
                                : array_map('intval', array_column($type['amenities'], 'id'));
                            ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="amenities[]" value="<?= e($amenity['id']) ?>"
                                    <?= in_array((int) $amenity['id'], $selectedAmenities, true) ? 'checked' : '' ?>>
                                <?= e($amenity['name']) ?>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                    <?php if ($formType === (int) $type['id'] && isset($errors['name'])): ?>
                        <small class="field-error"><?= e($errors['name']) ?></small>
                    <?php endif; ?>
                    <button class="button" type="submit">Ruaj ndryshimet</button>
                </form>
                <form method="post" action="/admin/room-types/<?= e($type['id']) ?>/delete" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e(\HotelHub\Core\Csrf::token()) ?>">
                    <button class="button button--danger" type="submit">Fshi llojin</button>
                </form>
                <p class="muted">Pajisje: <?= e(implode(', ', array_column($type['amenities'], 'name')) ?: 'Pa pajisje') ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel">
    <h2>Pajisjet</h2>
    <form method="post" action="/admin/amenities" class="inline-form">
        <input type="hidden" name="_csrf" value="<?= e(\HotelHub\Core\Csrf::token()) ?>">
        <label for="amenity-name">Emri i pajisjes</label>
        <input id="amenity-name" name="name" maxlength="80" required>
        <?php if (isset($errors['amenity_name'])): ?><small class="field-error"><?= e($errors['amenity_name']) ?></small><?php endif; ?>
        <?php if (isset($errors['name']) && $formType === null): ?><small class="field-error"><?= e($errors['name']) ?></small><?php endif; ?>
        <button class="button" type="submit">Shto pajisjen</button>
    </form>
    <ul class="amenity-list">
        <?php foreach ($amenities as $amenity): ?>
            <li>
                <form method="post" action="/admin/amenities/<?= e($amenity['id']) ?>" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e(\HotelHub\Core\Csrf::token()) ?>">
                    <label class="sr-only" for="amenity-<?= e($amenity['id']) ?>">Emri i pajisjes</label>
                    <input id="amenity-<?= e($amenity['id']) ?>" name="name" maxlength="80" required
                        value="<?= e($amenity['name']) ?>">
                    <button class="link-button" type="submit">Ruaj</button>
                </form>
                <?php if (isset($errors['amenity_name_' . $amenity['id']])): ?>
                    <small class="field-error"><?= e($errors['amenity_name_' . $amenity['id']]) ?></small>
                <?php endif; ?>
                <form method="post" action="/admin/amenities/<?= e($amenity['id']) ?>/delete" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e(\HotelHub\Core\Csrf::token()) ?>">
                    <button class="link-button" type="submit">Fshi</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
