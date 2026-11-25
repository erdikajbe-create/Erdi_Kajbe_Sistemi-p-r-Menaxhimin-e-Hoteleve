<?php
/**
 * @var array{id: int, name: string, email: string, role: string}|null $user
 */
?><section class="hero">
    <p class="eyebrow">Menaxhim hotelerie · i thjeshtë · i saktë</p>
    <h1>Çdo qëndrim, nën kontroll.</h1>
    <p>Rezervimet, dhomat dhe pagesat e hotelit tuaj në një hapësirë të vetme, të ndërtuar për punën e përditshme të recepsionit.</p>
    <?php if ($user === null): ?>
        <p><a class="button button--light" href="/register">Krijo llogari</a></p>
    <?php elseif (in_array($user['role'], ['admin', 'receptionist'], true)): ?>
        <p><a class="button button--light" href="/reservations">Hap rezervimet</a>
            <?php if ($user['role'] === 'admin'): ?><a class="button button--light" href="/dashboard">Hap dashboard-in</a><?php endif; ?></p>
    <?php else: ?>
        <p><a class="button button--light" href="/reservations">Rezervimet e mia</a></p>
    <?php endif; ?>
</section>

<section class="panel booking-panel">
    <div>
        <p class="eyebrow">Planifiko qëndrimin</p>
        <h2>Gjej dhomën e duhur</h2>
        <p class="muted">Kontrollo disponueshmërinë për datat e mysafirëve dhe vazhdo me rezervimin.</p>
    </div>
    <a class="button" href="/availability">Kërko disponueshmërinë <span aria-hidden="true">→</span></a>
</section>

<section class="feature-grid" aria-label="Përparësitë e HotelHub">
    <article class="feature-card">
        <span class="feature-card__number">01</span>
        <h2>Një pasqyrë e qartë</h2>
        <p>Mbërritjet, largimet dhe gjendja e dhomave duken menjëherë në dashboard.</p>
    </article>
    <article class="feature-card">
        <span class="feature-card__number">02</span>
        <h2>Rezervime të sigurta</h2>
        <p>Kontrolli i disponueshmërisë shmang përplasjet për të njëjtën dhomë dhe datë.</p>
    </article>
    <article class="feature-card">
        <span class="feature-card__number">03</span>
        <h2>Faturim i saktë</h2>
        <p>Shërbimet dhe pagesat lidhen me qëndrimin, me bilancin e përditësuar në çdo hap.</p>
    </article>
</section>
