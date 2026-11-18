<?php
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$roomPhotos = [
    'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=900&q=85',
    'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=900&q=85',
    'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=900&q=85',
];
$icons = ['⌁', '✧', '◷', '⌖'];
?>
<div class="lux-home">
    <section class="lux-hero" aria-labelledby="hero-title">
        <div class="lux-hero__content">
            <p class="eyebrow">QËNDRIM I PAHARRUESHËM</p>
            <h1 id="hero-title">Një përvojë që meriton të përsëritet.</h1>
            <p>Elegancë e qetë, mikpritje e ngrohtë dhe çdo detaj i menduar për ju.</p>
            <div class="actions">
                <a class="button button--gold" href="/availability">Rezervo tani</a>
                <a class="button button--ghost" href="#dhomat">Shiko dhomat</a>
            </div>
        </div>
        <form class="availability-bar" method="get" action="/availability">
            <div><label for="home-check-in">Mbërritja</label><input id="home-check-in" name="check_in" type="date" min="<?= e($today) ?>" value="<?= e($today) ?>" required></div>
            <div><label for="home-check-out">Largimi</label><input id="home-check-out" name="check_out" type="date" min="<?= e($tomorrow) ?>" value="<?= e($tomorrow) ?>" required></div>
            <div><label for="home-guests">Persona</label><select id="home-guests" name="guests"><?php foreach (range(1, 6) as $count): ?><option value="<?= $count ?>" <?= $count === 2 ? 'selected' : '' ?>><?= $count ?> persona</option><?php endforeach; ?></select></div>
            <button class="button button--gold" type="submit">Kërko dhoma</button>
        </form>
    </section>

    <section class="lux-section" data-reveal>
        <div class="lux-section__heading"><p class="eyebrow">MIKPRITJE ME KUJDES</p><h2>Pse të na zgjedhësh</h2><p>Shërbime të vogla që e bëjnë qëndrimin tuaj të veçantë.</p></div>
        <div class="lux-features">
            <?php foreach ([['⌁', 'Wi-Fi falas', 'Lidhje e shpejtë në çdo hapësirë.'], ['✧', 'Mëngjes i freskët', 'Niseni ditën me shije dhe qetësi.'], ['◷', 'Pritje 24/7', 'Jemi këtu sa herë të keni nevojë.'], ['⌖', 'Parkim i sigurt', 'Komoditet që nga momenti i mbërritjes.']] as [$icon, $name, $text]): ?>
                <article class="lux-feature"><span class="lux-feature__icon" aria-hidden="true"><?= e($icon) ?></span><h3><?= e($name) ?></h3><p><?= e($text) ?></p></article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="lux-section lux-section--cream" id="dhomat" data-reveal>
        <div class="lux-section__heading"><p class="eyebrow">PUSHONI BUKUR</p><h2>Dhomë për çdo udhëtim</h2><p>Zbuloni hapësira të krijuara për rehati të vërtetë.</p></div>
        <div class="lux-room-grid">
            <?php foreach (array_slice($roomTypes, 0, 3) as $index => $type): ?>
                <article class="lux-room"><img src="<?= e($roomPhotos[$index % count($roomPhotos)]) ?>" alt="Dhomë elegante e llojit <?= e($type['name']) ?>" loading="lazy"><div class="lux-room__body"><div class="lux-room__title"><h3><?= e($type['name']) ?></h3><span><?= e(money($type['base_price'])) ?><small>/natë</small></span></div><p><?= e($type['capacity']) ?> persona · <?= e($type['description'] ?? 'Rehati elegante dhe hapësirë e menduar për ju.') ?></p><a class="text-link" href="/availability">Shiko detajet <span aria-hidden="true">→</span></a></div></article>
            <?php endforeach; ?>
            <?php if ($roomTypes === []): ?><p class="muted">Dhomat tona do të shfaqen këtu së shpejti.</p><?php endif; ?>
        </div>
    </section>

    <section class="lux-section" data-reveal>
        <div class="lux-section__heading"><p class="eyebrow">MOMENTE TË KËNDSHME</p><h2>Shërbimet tona</h2><p>Gjithçka që ju duhet për një qëndrim pa kujdes.</p></div>
        <div class="lux-services"><?php foreach ($services as $index => $service): ?><article class="lux-service"><span aria-hidden="true"><?= e($icons[$index % count($icons)]) ?></span><div><h3><?= e($service['name']) ?></h3><p><?= e(money($service['price'])) ?></p></div></article><?php endforeach; ?><?php if ($services === []): ?><p class="muted">Shërbimet tona do të shfaqen këtu së shpejti.</p><?php endif; ?></div>
    </section>

    <section class="lux-showcase" data-reveal><div class="lux-stats"><div><strong data-count="48">48</strong><span>Dhoma</span></div><div><strong data-count="1200">1,200+</strong><span>Mysafirë të kënaqur</span></div><div><strong data-count="12">12</strong><span>Vite përvojë</span></div></div><div class="lux-gallery"><img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=800&q=85" alt="Tryezë elegante në restorantin e hotelit" loading="lazy"><img src="https://images.unsplash.com/photo-1571896349842-33c89424de2d?auto=format&fit=crop&w=800&q=85" alt="Pishinë e qetë në ambientin e hotelit" loading="lazy"><img src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=800&q=85" alt="Pamja e jashtme e një hoteli luksoz" loading="lazy"></div></section>
    <section class="lux-cta" data-reveal><p class="eyebrow">MOMENTI JU PËRKET JUVE</p><h2>Pushimi juaj i radhës fillon këtu.</h2><a class="button button--gold" href="/availability">Gjeni dhomën tuaj</a></section>
</div>
