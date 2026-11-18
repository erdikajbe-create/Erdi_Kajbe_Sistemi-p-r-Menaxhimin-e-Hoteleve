<?php $scripts = ['/js/api.js', '/js/availability.js']; ?>
<section class="page-heading">
    <p class="eyebrow">Disponueshmëria</p>
    <h1>Kërko dhoma të lira</h1>
</section>

<section class="panel">
    <form method="get" action="/availability" class="search-form" id="availability-form" novalidate>
        <div>
            <label for="check_in">Mbërritja</label>
            <input id="check_in" name="check_in" type="date" min="<?= e($today) ?>" required
                value="<?= e($query['check_in']) ?>">
            <?= field_error($errors, 'check_in') ?>
        </div>
        <div>
            <label for="check_out">Largimi</label>
            <input id="check_out" name="check_out" type="date" min="<?= e($today) ?>" required
                value="<?= e($query['check_out']) ?>">
            <?= field_error($errors, 'check_out') ?>
        </div>
        <div>
            <label for="guests">Persona</label>
            <input id="guests" name="guests" type="number" min="1" max="6" required value="<?= e($query['guests']) ?>">
            <?= field_error($errors, 'guests') ?>
        </div>
        <button class="button" type="submit" data-loading-text="Duke kërkuar...">Kërko</button>
    </form>
</section>

<section id="availability-results" aria-live="polite">
    <?php if ($result !== null): ?>
        <p class="results-summary">
            <?= e(count($result['rooms'])) ?> dhoma të lira · <?= e(date_al($result['check_in'])) ?> – <?= e(date_al($result['check_out'])) ?>
            · <?= e($result['nights']) ?> <?= $result['nights'] === 1 ? 'natë' : 'netë' ?> · <?= e($result['guests']) ?> persona
        </p>
        <?php if ($result['rooms'] === []): ?>
            <div class="panel empty-state">
                <h2>Nuk ka dhoma të lira për këto data</h2>
                <p>Provo data të tjera ose një numër më të vogël personash.</p>
            </div>
        <?php else: ?>
            <div class="room-grid">
                <?php foreach ($result['rooms'] as $room): ?>
                    <article class="room-card">
                        <div class="room-card__heading">
                            <div><p class="eyebrow"><?= e($room['room_type']) ?></p><h2>Dhoma <?= e($room['number']) ?></h2></div>
                            <span class="price-tag"><?= e(money($room['price_per_night'])) ?><small>/natë</small></span>
                        </div>
                        <p class="muted">Kati <?= e($room['floor']) ?> · deri në <?= e($room['capacity']) ?> persona</p>
                        <?php if ($room['amenities'] !== []): ?>
                            <p class="chips"><?php foreach ($room['amenities'] as $amenity): ?><span class="chip"><?= e($amenity) ?></span><?php endforeach; ?></p>
                        <?php endif; ?>
                        <p class="room-card__total">Totali: <strong><?= e(money($room['total'])) ?></strong></p>
                        <a class="button" href="/reservations/create?<?= e(http_build_query([
                            'room_id' => $room['id'],
                            'check_in' => $result['check_in'],
                            'check_out' => $result['check_out'],
                            'guests' => $result['guests'],
                        ])) ?>">Rezervo</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
