<section class="page-heading">
    <p class="eyebrow">Disponueshmëria</p>
    <h1>Kërko dhoma të lira</h1>
    <p>Zgjidh datat e qëndrimit dhe numrin e mysafirëve.</p>
</section>

<section class="panel">
    <form class="form-grid" data-availability-form novalidate>
        <div>
            <label for="check-in">Data e mbërritjes</label>
            <input id="check-in" name="check_in" type="date" min="<?= e($today) ?>" required>
            <small class="field-error" data-field-error="check_in"></small>
        </div>
        <div>
            <label for="check-out">Data e largimit</label>
            <input id="check-out" name="check_out" type="date" min="<?= e($today) ?>" required>
            <small class="field-error" data-field-error="check_out"></small>
        </div>
        <div>
            <label for="guests">Mysafirë</label>
            <input id="guests" name="guests" type="number" min="1" max="6" value="1" required>
            <small class="field-error" data-field-error="guests"></small>
        </div>
        <div class="availability-actions">
            <button class="button" type="submit" data-search-button>Kërko</button>
        </div>
    </form>
</section>

<section class="availability-results" aria-live="polite" aria-atomic="true" data-results></section>
<button class="button button--light availability-retry" type="button" data-retry hidden>Provo përsëri</button>

<script src="/js/availability.js" defer></script>
