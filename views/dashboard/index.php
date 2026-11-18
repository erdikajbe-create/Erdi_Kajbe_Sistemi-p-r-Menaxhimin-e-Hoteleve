<?php
$occupancy = $stats['total_rooms'] > 0 ? (int) round($stats['occupied_rooms'] / $stats['total_rooms'] * 100) : 0;
$maxRevenue = max(array_map(static fn (array $row): float => (float) $row['revenue'], $revenue) ?: [0]);
?>
<section class="page-heading page-heading--split">
    <div>
        <p class="eyebrow">Pasqyra e ditës</p>
        <h1>Dashboard</h1>
        <p class="muted"><?= e(date_al($today)) ?></p>
    </div>
    <a class="button" href="/reservations/create">+ Rezervim i ri</a>
</section>

<section class="stat-grid">
    <article class="stat">
        <p class="stat__label">Dhoma të zëna</p>
        <p class="stat__value"><?= e($stats['occupied_rooms']) ?><span>/<?= e($stats['total_rooms']) ?></span></p>
        <div class="meter" role="img" aria-label="Zënia <?= e($occupancy) ?>%"><span style="width: <?= e($occupancy) ?>%"></span></div>
        <p class="stat__hint"><?= e($occupancy) ?>% zënie<?= $stats['maintenance_rooms'] ? ' · ' . e($stats['maintenance_rooms']) . ' në mirëmbajtje' : '' ?></p>
    </article>
    <article class="stat">
        <p class="stat__label">Mbërritje sot</p>
        <p class="stat__value"><?= e($stats['arrivals_today']) ?></p>
        <p class="stat__hint"><?= e($stats['pending_reservations']) ?> rezervime presin konfirmim</p>
    </article>
    <article class="stat">
        <p class="stat__label">Largime sot</p>
        <p class="stat__value"><?= e($stats['departures_today']) ?></p>
        <p class="stat__hint <?= $stats['overdue_departures'] ? 'stat__hint--warn' : '' ?>">
            <?= $stats['overdue_departures'] ? e($stats['overdue_departures']) . ' me vonesë pa check-out' : 'Asnjë vonesë' ?>
        </p>
    </article>
    <article class="stat">
        <p class="stat__label">Të ardhurat e muajit</p>
        <p class="stat__value stat__value--money"><?= e(money($stats['month_revenue'])) ?></p>
        <p class="stat__hint">Nga pagesat e regjistruara</p>
    </article>
</section>

<div class="detail-layout">
    <section class="panel">
        <h2>Mbërritjet e sotme</h2>
        <?php if ($arrivals === []): ?>
            <p class="muted">Nuk ka mbërritje sot.</p>
        <?php else: ?>
            <ul class="stay-list">
                <?php foreach ($arrivals as $stay): ?>
                    <li>
                        <a href="/reservations/<?= e($stay['id']) ?>"><strong><?= e($stay['room_number']) ?></strong>
                            <?= e($stay['first_name'] . ' ' . $stay['last_name']) ?></a>
                        <?= reservation_badge($stay['status']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
    <section class="panel">
        <h2>Largimet</h2>
        <?php if ($departures === []): ?>
            <p class="muted">Nuk ka largime sot.</p>
        <?php else: ?>
            <ul class="stay-list">
                <?php foreach ($departures as $stay): ?>
                    <li>
                        <a href="/reservations/<?= e($stay['id']) ?>"><strong><?= e($stay['room_number']) ?></strong>
                            <?= e($stay['first_name'] . ' ' . $stay['last_name']) ?></a>
                        <?php if ($stay['check_out'] < $today): ?>
                            <span class="status-badge status-badge--cancelled">Vonesë që nga <?= e(date_al($stay['check_out'])) ?></span>
                        <?php else: ?>
                            <?= reservation_badge($stay['status']) ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<section class="panel">
    <h2>Të ardhurat (6 muajt e fundit)</h2>
    <?php if ($revenue === []): ?>
        <p class="muted">Ende pa pagesa.</p>
    <?php else: ?>
        <ul class="bar-list">
            <?php foreach ($revenue as $row): ?>
                <li>
                    <span class="bar-list__label"><?= e(date('m.Y', strtotime($row['month'] . '-01'))) ?></span>
                    <span class="bar-list__bar"><span style="width: <?= e($maxRevenue > 0 ? round((float) $row['revenue'] / $maxRevenue * 100) : 0) ?>%"></span></span>
                    <span class="bar-list__value"><?= e(money($row['revenue'])) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
