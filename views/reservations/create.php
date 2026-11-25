<section class="page-heading">
    <p class="eyebrow">Rezervim i ri</p>
    <h1><?= $room !== null ? 'Dhoma ' . e($room['number']) . ' · ' . e($room['room_type_name']) : 'Rezervim i ri' ?></h1>
    <?php if ($room !== null): ?>
        <p class="muted">Kati <?= e($room['floor']) ?> · deri në <?= e($room['capacity']) ?> persona</p>
    <?php endif; ?>
</section>

<?php if ($conflict !== null): ?>
    <div class="flash flash--error" role="alert">
        <?= e($conflict) ?> <a href="/availability?<?= e(http_build_query([
            'check_in' => $old['check_in'] ?? '',
            'check_out' => $old['check_out'] ?? '',
            'guests' => (int) ($old['adults'] ?? 1) + (int) ($old['children'] ?? 0),
        ])) ?>">Kërko dhoma të tjera</a>.
    </div>
<?php endif; ?>

<?php if ($room === null && !$isStaff): ?>
    <section class="panel empty-state">
        <h2>Zgjidhni fillimisht një dhomë</h2>
        <p>Kërkoni dhoma të lira sipas datave dhe klikoni "Rezervo".</p>
        <a class="button" href="/availability">Kërko dhoma</a>
    </section>
<?php else: ?>
<section class="panel">
    <form method="post" action="/reservations" class="form-grid" data-submit-once>
        <?= csrf_field() ?>
        <?php if ($isStaff): ?>
            <div class="form-grid__wide">
                <label for="room_id">Dhoma</label>
                <select id="room_id" name="room_id" required>
                    <option value="">Zgjidh dhomën</option>
                    <?php foreach ($rooms as $option): ?>
                        <option value="<?= e($option['id']) ?>" <?= (string) ($old['room_id'] ?? '') === (string) $option['id'] ? 'selected' : '' ?>
                            <?= $option['status'] === 'maintenance' ? 'disabled' : '' ?>>
                            <?= e($option['number']) ?> · <?= e($option['room_type_name']) ?> (<?= e($option['capacity']) ?> persona)<?= $option['status'] === 'maintenance' ? ' · mirëmbajtje' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= field_error($errors, 'room_id') ?>
            </div>
        <?php else: ?>
            <input type="hidden" name="room_id" value="<?= e($old['room_id'] ?? '') ?>">
            <?= field_error($errors, 'room_id') ?>
        <?php endif; ?>

        <div>
            <label for="check_in">Mbërritja</label>
            <input id="check_in" name="check_in" type="date" min="<?= e($today) ?>" required value="<?= e($old['check_in'] ?? '') ?>">
            <?= field_error($errors, 'check_in') ?>
        </div>
        <div>
            <label for="check_out">Largimi</label>
            <input id="check_out" name="check_out" type="date" min="<?= e($today) ?>" required value="<?= e($old['check_out'] ?? '') ?>">
            <?= field_error($errors, 'check_out') ?>
        </div>
        <div>
            <label for="adults">Të rritur</label>
            <input id="adults" name="adults" type="number" min="1" max="6" required value="<?= e($old['adults'] ?? '1') ?>">
            <?= field_error($errors, 'adults') ?>
        </div>
        <div>
            <label for="children">Fëmijë</label>
            <input id="children" name="children" type="number" min="0" max="5" value="<?= e($old['children'] ?? '0') ?>">
            <?= field_error($errors, 'children') ?>
        </div>

        <?php if ($isStaff): ?>
            <fieldset class="form-grid__wide guest-fieldset">
                <legend>Mysafiri</legend>
                <div>
                    <label for="guest_id">Mysafir ekzistues</label>
                    <select id="guest_id" name="guest_id">
                        <option value="new">+ Mysafir i ri (plotëso më poshtë)</option>
                        <?php foreach ($guests as $guest): ?>
                            <option value="<?= e($guest['id']) ?>" <?= (string) ($old['guest_id'] ?? '') === (string) $guest['id'] ? 'selected' : '' ?>>
                                <?= e($guest['last_name'] . ' ' . $guest['first_name']) ?><?= $guest['phone'] ? ' · ' . e($guest['phone']) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= field_error($errors, 'guest_id') ?>
                </div>
                <div class="form-grid" data-new-guest>
                    <div>
                        <label for="first_name">Emri</label>
                        <input id="first_name" name="first_name" maxlength="80" value="<?= e($old['first_name'] ?? '') ?>">
                        <?= field_error($errors, 'first_name') ?>
                    </div>
                    <div>
                        <label for="last_name">Mbiemri</label>
                        <input id="last_name" name="last_name" maxlength="80" value="<?= e($old['last_name'] ?? '') ?>">
                        <?= field_error($errors, 'last_name') ?>
                    </div>
                    <div>
                        <label for="phone">Telefoni</label>
                        <input id="phone" name="phone" type="tel" maxlength="30" value="<?= e($old['phone'] ?? '') ?>">
                        <?= field_error($errors, 'phone') ?>
                    </div>
                    <div>
                        <label for="email">Email (opsional)</label>
                        <input id="email" name="email" type="email" maxlength="150" value="<?= e($old['email'] ?? '') ?>">
                        <?= field_error($errors, 'email') ?>
                    </div>
                    <div>
                        <label for="document_number">Nr. i dokumentit (opsional)</label>
                        <input id="document_number" name="document_number" maxlength="50" value="<?= e($old['document_number'] ?? '') ?>">
                        <?= field_error($errors, 'document_number') ?>
                    </div>
                </div>
            </fieldset>
        <?php endif; ?>

        <div class="form-grid__wide">
            <label for="notes">Shënime (opsional)</label>
            <textarea id="notes" name="notes" rows="2" maxlength="1000"><?= e($old['notes'] ?? '') ?></textarea>
            <?= field_error($errors, 'notes') ?>
        </div>

        <div class="form-grid__wide">
            <p class="muted">
                <?= $isStaff
                    ? 'Rezervimi nga recepsioni krijohet direkt i konfirmuar.'
                    : 'Rezervimi krijohet "Në pritje" dhe konfirmohet nga recepsioni. Mund ta anuloni vetë deri 24 orë para mbërritjes.' ?>
            </p>
            <button class="button" type="submit" data-loading-text="Duke ruajtur...">Konfirmo rezervimin</button>
        </div>
    </form>
</section>
<?php endif; ?>
<?php $scripts = ['/js/forms.js']; ?>
