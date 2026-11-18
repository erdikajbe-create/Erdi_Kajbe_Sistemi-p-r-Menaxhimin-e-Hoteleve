<section class="panel">
    <p class="eyebrow">Gabim</p>
    <h1><?= e($title) ?></h1>
    <p><?= e($message) ?></p>
    <?php if (!empty($errors)): ?>
        <ul class="error-list">
            <?php foreach ($errors as $fieldError): ?><li><?= e($fieldError) ?></li><?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?= $details ?? '' ?>
    <p class="actions">
        <?php $backPath = parse_url((string) ($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_PATH); ?>
        <?php if (is_string($backPath) && str_starts_with($backPath, '/') && !str_starts_with($backPath, '//')): ?>
            <a class="button" href="<?= e($backPath) ?>">Kthehu prapa</a>
        <?php endif; ?>
        <a class="button button--light" href="/">Faqja kryesore</a>
    </p>
</section>
