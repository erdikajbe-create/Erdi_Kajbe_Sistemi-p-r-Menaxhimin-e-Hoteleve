<?php
$q = (string) ($filters['q'] ?? '');
$thekso = static function (string $value, string $needle): string {
    $safe = e($value);
    if ($needle === '') {
        return $safe;
    }
    $parts = preg_split('/(' . preg_quote($needle, '/') . ')/iu', $value, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$value];
    return implode('', array_map(static fn (string $part): string => mb_strtolower($part) === mb_strtolower($needle) ? '<mark>' . e($part) . '</mark>' : e($part), $parts));
};
?>
<section class="page-heading page-heading--split">
    <div><p class="eyebrow"><?= $isStaff ? 'Recepsioni' : 'Llogaria juaj' ?></p><h1><?= e($title) ?></h1></div>
    <a class="button" href="<?= $isStaff ? '/reservations/create' : '/availability' ?>">+ Rezervim i ri</a>
</section>

<section class="panel reservation-search-panel">
    <form method="get" action="/reservations" class="reservation-filters" id="reservation-search" data-api="/api/reservations" data-staff="<?= $isStaff ? '1' : '0' ?>">
        <div class="reservation-search-box"><label class="sr-only" for="q">Kërko rezervime</label><span aria-hidden="true">⌕</span><input id="q" name="q" maxlength="60" autocomplete="off" value="<?= e($q) ?>" placeholder="Kërko me emër, ID ose kod rezervimi…"><button type="button" class="clear-search" aria-label="Pastro kërkimin" <?= $q === '' ? 'hidden' : '' ?>>×</button></div>
        <div><label for="status">Statusi</label><select id="status" name="status"><option value="">Të gjitha</option><?php foreach ($statuses as $status): ?><option value="<?= e($status->value) ?>" <?= ($filters['status'] ?? '') === $status->value ? 'selected' : '' ?>><?= e($status->label()) ?></option><?php endforeach; ?></select></div>
        <div><label for="date">Data e qëndrimit</label><input id="date" name="date" type="date" value="<?= e($filters['date'] ?? '') ?>"></div>
        <div class="filter-form__actions"><button class="button" type="submit">Filtro</button><?php if ($isStaff): ?><a class="link-button" href="/reservations?date=<?= e(date('Y-m-d')) ?>">Sot</a><?php endif; ?><a class="link-button" href="/reservations">Pastro</a></div>
        <p class="search-status sr-only" aria-live="polite"></p>
    </form>
</section>

<section class="panel" id="reservation-results" aria-live="polite">
    <?php if ($reservations === []): ?><div class="empty-state"><h2><?= $q !== '' ? 'Nuk u gjet asnjë rezervim për «' . e($q) . '»' : 'Nuk ka rezervime' ?></h2><p><?= $filters !== [] ? 'Asnjë rezervim nuk përputhet me filtrat.' : 'Ende nuk ka rezervime.' ?></p><a class="button" href="<?= $isStaff ? '/reservations/create' : '/availability' ?>">Krijo të parin</a></div>
    <?php else: ?><div class="table-wrap"><table class="responsive-table"><thead><tr><th>Kodi</th><?php if ($isStaff): ?><th>Mysafiri</th><?php endif; ?><th>Dhoma</th><th>Datat</th><th>Persona</th><th>Totali i netëve</th><th>Statusi</th></tr></thead><tbody>
        <?php foreach ($reservations as $reservation): ?><tr><td data-label="Kodi"><a href="/reservations/<?= e($reservation['id']) ?>"><strong><?= $thekso((string) $reservation['code'], $q) ?></strong></a></td><?php if ($isStaff): ?><td data-label="Mysafiri"><?= $thekso($reservation['first_name'] . ' ' . $reservation['last_name'], $q) ?></td><?php endif; ?><td data-label="Dhoma"><?= e($reservation['room_number']) ?> · <?= e($reservation['room_type_name']) ?></td><td data-label="Datat"><?= e(date_al($reservation['check_in'])) ?> – <?= e(date_al($reservation['check_out'])) ?></td><td data-label="Persona"><?= e((int) $reservation['adults'] + (int) $reservation['children']) ?></td><td data-label="Totali"><?= e(money($reservation['total_amount'])) ?></td><td data-label="Statusi"><?= reservation_badge($reservation['status']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div><?php endif; ?>
</section>
