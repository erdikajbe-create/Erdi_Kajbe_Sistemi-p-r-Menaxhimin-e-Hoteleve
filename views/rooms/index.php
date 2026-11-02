<section class="page-heading">
    <p class="eyebrow">Menaxhimi</p>
    <h1>Dhomat</h1>
</section>

<?php if ($isAdmin): ?>
    <section class="panel">
        <h2>Shto dhomë</h2>
        <form method="post" action="/rooms" class="form-grid">
            <input type="hidden" name="_csrf" value="<?= e(\HotelHub\Core\Csrf::token()) ?>">
            <div>
                <label for="room-number">Numri</label>
                <input id="room-number" name="number" maxlength="10" required value="<?= e($old['number'] ?? '') ?>">
                <?php if (isset($errors['number'])): ?><small class="field-error"><?= e($errors['number']) ?></small><?php endif; ?>
            </div>
            <div>
                <label for="room-type">Lloji</label>
                <select id="room-type" name="room_type_id" required>
                    <option value="">Zgjidh llojin</option>
                    <?php foreach ($types as $type): ?>
                        <option value="<?= e($type['id']) ?>" <?= (string) ($old['room_type_id'] ?? '') === (string) $type['id'] ? 'selected' : '' ?>>
                            <?= e($type['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['room_type_id'])): ?><small class="field-error"><?= e($errors['room_type_id']) ?></small><?php endif; ?>
            </div>
            <div>
                <label for="room-floor">Kati</label>
                <input id="room-floor" name="floor" type="number" min="-128" max="127" required value="<?= e($old['floor'] ?? '1') ?>">
                <?php if (isset($errors['floor'])): ?><small class="field-error"><?= e($errors['floor']) ?></small><?php endif; ?>
            </div>
            <button class="button" type="submit">Shto dhomë</button>
        </form>
    </section>
<?php endif; ?>

<section class="room-grid">
    <?php foreach ($rooms as $room): ?>
        <article class="room-card">
            <div class="room-card__heading">
                <div><p class="eyebrow">Dhoma</p><h2><?= e($room['number']) ?></h2></div>
                <span class="status-badge status-badge--<?= e($room['status']) ?>"><?= e(match ($room['status']) {
                    'available' => 'E lirë',
                    'occupied' => 'E zënë',
                    'cleaning' => 'Pastrim',
                    'maintenance' => 'Mirëmbajtje',
                    default => $room['status'],
                }) ?></span>
            </div>
            <p><?= e($room['room_type_name']) ?> · Kati <?= e($room['floor']) ?> · <?= e($room['capacity']) ?> persona</p>
            <form method="post" action="/rooms/<?= e($room['id']) ?>/status" class="inline-form">
                <input type="hidden" name="_csrf" value="<?= e(\HotelHub\Core\Csrf::token()) ?>">
                <label for="status-<?= e($room['id']) ?>">Statusi</label>
                <select id="status-<?= e($room['id']) ?>" name="status">
                    <?php foreach (['available' => 'E lirë', 'occupied' => 'E zënë', 'cleaning' => 'Pastrim', 'maintenance' => 'Mirëmbajtje'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $room['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="button" type="submit">Ndrysho</button>
            </form>
            <?php if ($isAdmin): ?>
                <details class="edit-details">
                    <summary>Ndrysho të dhënat</summary>
                    <form method="post" action="/rooms/<?= e($room['id']) ?>" class="form-grid">
                        <input type="hidden" name="_csrf" value="<?= e(\HotelHub\Core\Csrf::token()) ?>">
                        <div>
                            <label for="number-<?= e($room['id']) ?>">Numri</label>
                            <input id="number-<?= e($room['id']) ?>" name="number" maxlength="10" required value="<?= e($room['number']) ?>">
                        </div>
                        <div>
                            <label for="type-<?= e($room['id']) ?>">Lloji</label>
                            <select id="type-<?= e($room['id']) ?>" name="room_type_id" required>
                                <?php foreach ($types as $type): ?>
                                    <option value="<?= e($type['id']) ?>" <?= (int) $room['room_type_id'] === (int) $type['id'] ? 'selected' : '' ?>>
                                        <?= e($type['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="floor-<?= e($room['id']) ?>">Kati</label>
                            <input id="floor-<?= e($room['id']) ?>" name="floor" type="number" min="-128" max="127" required value="<?= e($room['floor']) ?>">
                        </div>
                        <button class="button" type="submit">Ruaj</button>
                    </form>
                    <form method="post" action="/rooms/<?= e($room['id']) ?>/delete" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= e(\HotelHub\Core\Csrf::token()) ?>">
                        <button class="button button--danger" type="submit">Fshi dhomën</button>
                    </form>
                </details>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
    <?php if ($rooms === []): ?><p class="panel">Nuk ka dhoma të regjistruara.</p><?php endif; ?>
</section>
