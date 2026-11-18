<?php
/** @var \HotelHub\Enums\ReservationStatus $status */
use HotelHub\Enums\PaymentMethod;
use HotelHub\Enums\ReservationStatus;

$scripts = ['/js/forms.js'];
$id = (int) $reservation['id'];
$balance = $invoice['balance'];
$balanceIsZero = \HotelHub\Core\Money::compare($balance, '0') === 0;
$canCancel = $status->canTransitionTo(ReservationStatus::Cancelled) && ($isStaff || $guestCanCancel);
$canAddCharges = $isStaff && in_array($status, [ReservationStatus::Confirmed, ReservationStatus::CheckedIn], true);
$canPay = $isStaff && $status->isOpen() && \HotelHub\Core\Money::compare($balance, '0') > 0;
?>
<section class="page-heading page-heading--split">
    <div>
        <p class="eyebrow">Rezervimi</p>
        <h1 class="reservation-code"><?= e($reservation['code']) ?> <?= reservation_badge($reservation['status']) ?></h1>
        <p class="muted">Krijuar më <?= e(date_al($reservation['created_at'], true)) ?><?= $reservation['created_by_name'] ? ' nga ' . e($reservation['created_by_name']) : '' ?></p>
    </div>
    <a class="link-button" href="/reservations">← Të gjitha rezervimet</a>
</section>

<?php if (isset($errors['_action'])): ?>
    <div class="flash flash--error" role="alert"><?= e($errors['_action']) ?></div>
<?php endif; ?>

<div class="detail-layout">
    <section class="panel">
        <h2>Qëndrimi</h2>
        <dl class="detail-list">
            <dt>Mysafiri</dt>
            <dd><?= e($reservation['first_name'] . ' ' . $reservation['last_name']) ?>
                <?php if ($isStaff): ?>
                    <span class="muted"><?= e(implode(' · ', array_filter([$reservation['guest_phone'], $reservation['guest_email'], $reservation['document_number']]))) ?></span>
                <?php endif; ?>
            </dd>
            <dt>Dhoma</dt>
            <dd><?= e($reservation['room_number']) ?> · <?= e($reservation['room_type_name']) ?>
                <?php if ($isStaff): ?><span class="status-badge status-badge--<?= e($reservation['room_status']) ?>"><?= e(match ($reservation['room_status']) {
                    'available' => 'E lirë', 'occupied' => 'E zënë', 'cleaning' => 'Pastrim', 'maintenance' => 'Mirëmbajtje', default => $reservation['room_status'],
                }) ?></span><?php endif; ?>
            </dd>
            <dt>Mbërritja</dt>
            <dd><?= e(date_al($reservation['check_in'])) ?> <span class="muted">nga ora 14:00</span></dd>
            <dt>Largimi</dt>
            <dd><?= e(date_al($reservation['check_out'])) ?> <span class="muted">deri në 11:00</span></dd>
            <dt>Persona</dt>
            <dd><?= e($reservation['adults']) ?> të rritur<?= (int) $reservation['children'] > 0 ? ', ' . e($reservation['children']) . ' fëmijë' : '' ?></dd>
            <?php if ($reservation['notes']): ?>
                <dt>Shënime</dt>
                <dd><?= nl2br(e($reservation['notes'])) ?></dd>
            <?php endif; ?>
        </dl>

        <div class="actions">
            <?php if ($isStaff && $status === ReservationStatus::Pending): ?>
                <form method="post" action="/reservations/<?= e($id) ?>/confirm" class="inline-form">
                    <?= csrf_field() ?>
                    <button class="button" type="submit">Konfirmo</button>
                </form>
            <?php endif; ?>
            <?php if ($isStaff && $status === ReservationStatus::Confirmed): ?>
                <form method="post" action="/reservations/<?= e($id) ?>/check-in" class="inline-form">
                    <?= csrf_field() ?>
                    <button class="button" type="submit" <?= $canCheckInToday ? '' : 'disabled' ?>>Check-in</button>
                </form>
                <?php if (!$canCheckInToday): ?>
                    <span class="muted">Check-in lejohet nga <?= e(date_al($reservation['check_in'])) ?>.</span>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($isStaff && $status === ReservationStatus::CheckedIn): ?>
                <form method="post" action="/reservations/<?= e($id) ?>/check-out" class="inline-form">
                    <?= csrf_field() ?>
                    <button class="button" type="submit" <?= $balanceIsZero ? '' : 'disabled' ?>>Check-out</button>
                </form>
                <?php if (!$balanceIsZero): ?>
                    <span class="muted">Check-out lejohet kur bilanci është 0.</span>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($canCancel): ?>
                <form method="post" action="/reservations/<?= e($id) ?>/cancel" class="inline-form"
                    data-confirm="Ta anulojmë rezervimin <?= e($reservation['code']) ?>? Ky veprim nuk kthehet.">
                    <?= csrf_field() ?>
                    <button class="button button--danger" type="submit">Anulo rezervimin</button>
                </form>
            <?php elseif (!$isStaff && $status->canTransitionTo(ReservationStatus::Cancelled)): ?>
                <p class="muted">Anulimi online mbyllet 24 orë para mbërritjes. Për ndryshime kontaktoni recepsionin.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="panel invoice">
        <h2>Fatura</h2>
        <table>
            <tbody>
                <tr>
                    <td><?= e($invoice['nights']) ?> <?= $invoice['nights'] === 1 ? 'natë' : 'netë' ?> × <?= e(money($invoice['price_per_night'])) ?></td>
                    <td class="num"><?= e(money($invoice['nights_total'])) ?></td>
                </tr>
                <?php foreach ($invoice['services'] as $line): ?>
                    <tr>
                        <td><?= e($line['name']) ?> × <?= e($line['quantity']) ?> <span class="muted">(<?= e(money($line['unit_price'])) ?> · <?= e(date_al($line['added_at'])) ?>)</span></td>
                        <td class="num"><?= e(money($line['line_total'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($invoice['services'] === []): ?>
                    <tr><td colspan="2" class="muted">Ende pa shërbime shtesë.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr><th>Totali</th><td class="num"><strong><?= e(money($invoice['grand_total'])) ?></strong></td></tr>
                <tr><th>Paguar</th><td class="num"><?= e(money($invoice['paid_total'])) ?></td></tr>
                <tr class="invoice__balance <?= $balanceIsZero ? 'is-settled' : '' ?>">
                    <th>Mbetur</th><td class="num"><strong><?= e(money($balance)) ?></strong></td>
                </tr>
            </tfoot>
        </table>

        <?php if ($invoice['payments'] !== []): ?>
            <h3>Pagesat</h3>
            <ul class="payment-list">
                <?php foreach ($invoice['payments'] as $payment): ?>
                    <li>
                        <span><?= e(date_al($payment['paid_at'], true)) ?> · <?= e($payment['method_label']) ?><?= $payment['reference'] ? ' · ' . e($payment['reference']) : '' ?></span>
                        <strong><?= e(money($payment['amount'])) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<?php if ($canAddCharges || $canPay): ?>
<div class="detail-layout">
    <?php if ($canAddCharges): ?>
        <section class="panel">
            <h2>Shto shërbim</h2>
            <?php if ($services === []): ?>
                <p class="muted">Nuk ka shërbime aktive në katalog.</p>
            <?php else: ?>
                <form method="post" action="/reservations/<?= e($id) ?>/services" class="form-grid" data-submit-once>
                    <?= csrf_field() ?>
                    <div>
                        <label for="service_id">Shërbimi</label>
                        <select id="service_id" name="service_id" required>
                            <?php foreach ($services as $service): ?>
                                <option value="<?= e($service['id']) ?>" <?= (string) ($old['service_id'] ?? '') === (string) $service['id'] ? 'selected' : '' ?>>
                                    <?= e($service['name']) ?> · <?= e(money($service['price'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_error($errors, 'service_id') ?>
                    </div>
                    <div>
                        <label for="quantity">Sasia</label>
                        <input id="quantity" name="quantity" type="number" min="1" max="99" required value="<?= e($old['quantity'] ?? '1') ?>">
                        <?= field_error($errors, 'quantity') ?>
                    </div>
                    <button class="button" type="submit">Shto në faturë</button>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    <?php if ($canPay): ?>
        <section class="panel">
            <h2>Regjistro pagesë</h2>
            <form method="post" action="/reservations/<?= e($id) ?>/payments" class="form-grid" data-submit-once>
                <?= csrf_field() ?>
                <div>
                    <label for="amount">Shuma (€)</label>
                    <input id="amount" name="amount" inputmode="decimal" required value="<?= e($old['amount'] ?? $balance) ?>">
                    <?= field_error($errors, 'amount') ?>
                </div>
                <div>
                    <label for="method">Metoda</label>
                    <select id="method" name="method" required>
                        <?php foreach (PaymentMethod::cases() as $method): ?>
                            <option value="<?= e($method->value) ?>" <?= ($old['method'] ?? '') === $method->value ? 'selected' : '' ?>><?= e($method->label()) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= field_error($errors, 'method') ?>
                </div>
                <div class="form-grid__wide">
                    <label for="reference">Referenca (opsionale)</label>
                    <input id="reference" name="reference" maxlength="100" value="<?= e($old['reference'] ?? '') ?>" placeholder="p.sh. Paradhënie, nr. transaksioni">
                    <?= field_error($errors, 'reference') ?>
                </div>
                <button class="button" type="submit">Regjistro pagesën</button>
            </form>
        </section>
    <?php endif; ?>
</div>
<?php endif; ?>
