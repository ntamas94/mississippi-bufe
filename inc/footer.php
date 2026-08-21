</main>

<footer class="site-footer">
  <div class="wrap">
    <div class="footer-grid">
      <div>
        <h4><?= e($CFG['name']) ?></h4>
        <p>
          <?= e($CFG['zip']) ?> <?= e($CFG['city']) ?><br>
          <?= e($CFG['street']) ?>
        </p>
        <p>
          <a href="tel:<?= e($CFG['phone_raw']) ?>"><?= e($CFG['phone']) ?></a><br>
          <a href="tel:<?= e($CFG['mobile_raw']) ?>"><?= e($CFG['mobile']) ?></a>
        </p>
        <p><a href="<?= e($CFG['facebook']) ?>" target="_blank" rel="noopener"><?= e(t('common.facebook')) ?> →</a></p>
      </div>

      <div>
        <h4><?= e(t('common.hours')) ?></h4>
        <ul class="footer-hours">
          <?php foreach ([1, 2, 3, 4, 5, 6, 0] as $d):
              $h = $CFG['hours'][$d] ?? null; ?>
          <li<?= $d === $dayNow ? ' class="is-today"' : '' ?>>
            <span><?= e(ta('days')[$d]) ?></span>
            <span><?= $h ? e($h[0] . ' – ' . $h[1]) : '—' ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div>
        <h4><?= e(t('common.pages')) ?></h4>
        <ul class="footer-links">
          <?php foreach ($navItems as $key => [$href, $label]): ?>
          <li><a href="<?= e(u($href)) ?>"><?= e($label) ?></a></li>
          <?php endforeach; ?>
        </ul>
        <div class="lang-switch lang-switch--footer">
          <?php foreach (LANGS as $code => $label): ?>
          <a href="<?= e(switch_url($code)) ?>" class="lang<?= $LANG === $code ? ' is-active' : '' ?>"><?= e($label) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> <?= e($CFG['name']) ?></span>
      <span><?= e(t('common.footer_note')) ?></span>
    </div>
  </div>
</footer>

<button class="to-top" type="button" aria-label="Top">
  <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 15 6-6 6 6"/></svg>
</button>

<div class="callbar" role="region" aria-label="<?= e(t('common.call')) ?>">
  <a class="btn btn--primary" href="tel:<?= e($CFG['phone_raw']) ?>"><?= e(t('common.call')) ?></a>
  <a class="btn btn--light" href="<?= e(u('motel.php')) ?>#foglalas"><?= e(t('nav.book')) ?></a>
</div>

<div class="lightbox" id="lightbox" hidden>
  <button class="lightbox-close" type="button" aria-label="<?= e(t('gallery.close')) ?>">&times;</button>
  <button class="lightbox-nav lightbox-prev" type="button" aria-label="<?= e(t('gallery.prev')) ?>">&#8249;</button>
  <figure class="lightbox-figure">
    <img src="" alt="">
    <figcaption></figcaption>
  </figure>
  <button class="lightbox-nav lightbox-next" type="button" aria-label="<?= e(t('gallery.next')) ?>">&#8250;</button>
</div>

<script src="assets/app.js?v=5" defer></script>
</body>
</html>
