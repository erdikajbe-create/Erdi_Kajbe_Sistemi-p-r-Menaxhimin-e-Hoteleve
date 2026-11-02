<section class="hero">
    <p class="eyebrow">Menaxhim i thjeshtë i hotelit</p>
    <h1>Mirë se vjen në HotelHub</h1>
    <p>Një vend i vetëm për rezervimet dhe përvojën e mysafirëve tuaj.</p>
    <?php if ($user === null): ?>
        <p><a class="button button--light" href="/register">Krijo llogari</a></p>
    <?php elseif (in_array($user['role'], ['admin', 'receptionist'], true)): ?>
        <p><a class="button button--light" href="/rooms">Menaxho dhomat</a></p>
    <?php endif; ?>
</section>

<section class="panel">
    <h2>HotelHub</h2>
    <p>Rezervimet dhe menaxhimi i dhomave do të jenë të disponueshme nga llogaria juaj.</p>
</section>
