<?php
require __DIR__ . '/inc/i18n.php';

$page       = 'home';
$meta_title = t('home.meta_title');
$meta_desc  = t('home.meta_desc');

require __DIR__ . '/inc/head.php';
?>

<section class="hero">
  <div class="hero-bg" aria-hidden="true"></div>
  <div class="wrap">
    <span class="eyebrow"><?= e(t('home.kicker')) ?></span>
    <h1>Mississippi Büfé<br><span class="accent">&amp; Missouri Szálláshely</span></h1>
    <p><?= e(t('home.lead')) ?></p>
    <div class="hero-actions">
      <a class="btn btn--primary" href="<?= e(u('szallas.php')) ?>#foglalas"><?= e(t('home.cta_book')) ?></a>
      <a class="btn btn--ghost" href="<?= e(u('etlap.php')) ?>"><?= e(t('home.cta_menu')) ?></a>
    </div>
    <div class="hero-badges">
      <?php foreach (ta('home.badges') as $badge): ?>
      <span class="hero-badge"><?= e($badge) ?></span>
      <?php endforeach; ?>
    </div>
    <span class="scroll-hint" aria-hidden="true"></span>
  </div>
</section>

<div class="infobar">
  <div class="wrap">
    <div class="infobar-item">
      <span class="ico" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
      </span>
      <div>
        <strong><?= e(t('common.address')) ?></strong>
        <a href="https://www.google.com/maps/search/?api=1&amp;query=<?= rawurlencode($CFG['maps_query']) ?>" target="_blank" rel="noopener">
          <?= e($CFG['zip'] . ' ' . $CFG['city'] . ', ' . $CFG['street']) ?>
        </a>
      </div>
    </div>
    <div class="infobar-item">
      <span class="ico" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
      </span>
      <div>
        <strong><?= e(t('common.hours')) ?></strong>
        <a href="<?= e(u('kapcsolat.php')) ?>#nyitvatartas">
          <?= e(ta('days_short')[1] . '–' . ta('days_short')[5]) ?> 10–22 ·
          <?= e(ta('days_short')[6]) ?> 16–22 ·
          <?= e(ta('days_short')[0]) ?> 16–21
        </a>
      </div>
    </div>
    <div class="infobar-item">
      <span class="ico" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 3h4l2 5-2.5 1.5a12 12 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2.2 2A17 17 0 0 1 3 5.2 2 2 0 0 1 5 3Z"/></svg>
      </span>
      <div>
        <strong><?= e(t('common.phone')) ?></strong>
        <a href="tel:<?= e($CFG['phone_raw']) ?>"><?= e($CFG['phone']) ?></a>
      </div>
    </div>
  </div>
</div>

<section class="section">
  <div class="wrap center">
    <div data-reveal>
      <h2><span class="h-underline"><?= e(t('home.about_title')) ?></span></h2>
      <p class="lead"><?= e(t('home.about_text')) ?></p>
    </div>

    <div class="stats">
      <?php foreach (ta('home.stats') as $i => $stat): ?>
      <div class="stat" data-reveal data-delay="<?= $i % 4 ?>">
        <span class="stat-num" data-count="<?= e($stat['num']) ?>"><?= e($stat['num']) ?></span>
        <span class="stat-label"><?= e($stat['label']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--paper">
  <div class="wrap">
    <div class="grid grid--2">
      <article class="card" data-reveal>
        <div class="card-media">
          <img src="images/pizza.jpg" alt="<?= e(t('gallery.g_pizza')) ?>" loading="lazy" width="1125" height="844">
        </div>
        <div class="card-body">
          <span class="tag"><?= e(t('home.buffet_tag')) ?></span>
          <h3><?= e(t('home.buffet_title')) ?></h3>
          <p><?= e(t('home.buffet_text')) ?></p>
          <ul class="ticks">
            <?php foreach (ta('home.buffet_list') as $item): ?>
            <li><?= e($item) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="card-foot">
          <a class="btn btn--dark btn--sm" href="<?= e(u('etlap.php')) ?>"><?= e(t('home.buffet_btn')) ?></a>
        </div>
      </article>

      <article class="card" data-reveal data-delay="1">
        <div class="card-media">
          <img src="images/motel-udvar.jpg" alt="<?= e(t('gallery.g_motel')) ?>" loading="lazy" width="1400" height="1050">
        </div>
        <div class="card-body">
          <span class="tag"><?= e(t('home.motel_tag')) ?></span>
          <h3><?= e(t('home.motel_title')) ?></h3>
          <p><?= e(t('home.motel_text')) ?></p>
          <ul class="ticks">
            <?php foreach (ta('home.motel_list') as $item): ?>
            <li><?= e($item) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="card-foot">
          <a class="btn btn--dark btn--sm" href="<?= e(u('szallas.php')) ?>"><?= e(t('home.motel_btn')) ?></a>
        </div>
      </article>
    </div>
  </div>
</section>

<section class="section section--sunk">
  <div class="wrap">
    <div class="grid grid--split">
      <div data-reveal>
        <span class="eyebrow"><?= e(t('home.terrace_tag')) ?></span>
        <h2><?= e(t('home.terrace_title')) ?></h2>
        <p class="lead"><?= e(t('home.terrace_text')) ?></p>
        <p style="margin-top:22px"><a class="link-arrow" href="<?= e(u('galeria.php')) ?>"><?= e(t('nav.gallery')) ?></a></p>
      </div>
      <div class="gallery" data-reveal data-delay="1" style="grid-template-columns:1fr 1fr">
        <figure style="grid-column:1 / -1">
          <button class="shot" type="button" data-caption="<?= e(t('gallery.g_terrace')) ?>">
            <img src="images/terasz.jpg" alt="<?= e(t('gallery.g_terrace')) ?>" loading="lazy" width="1280" height="960">
          </button>
        </figure>
        <figure>
          <button class="shot" type="button" data-caption="<?= e(t('gallery.g_terrace_tables')) ?>">
            <img src="images/terasz-asztalok.jpg" alt="<?= e(t('gallery.g_terrace_tables')) ?>" loading="lazy" width="1385" height="520">
          </button>
        </figure>
        <figure>
          <button class="shot" type="button" data-caption="<?= e(t('gallery.g_buffet')) ?>">
            <img src="images/bufe-epulet.jpg" alt="<?= e(t('gallery.g_buffet')) ?>" loading="lazy" width="500" height="374">
          </button>
        </figure>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <h2 class="center" data-reveal><?= e(t('home.reviews')) ?></h2>
    <div class="grid grid--3" style="margin-top:34px">
      <?php foreach (ta('home.quotes') as $i => $quote): ?>
      <blockquote class="quote" data-reveal data-delay="<?= $i ?>">
        <div class="stars" aria-label="5/5">★★★★★</div>
        <p><?= e($quote) ?></p>
        <cite><?= e(t('home.quote_src')) ?></cite>
      </blockquote>
      <?php endforeach; ?>

      <a class="quote quote--score" data-reveal data-delay="3" href="https://www.google.com/maps/search/?api=1&amp;query=<?= rawurlencode($CFG['maps_query']) ?>" target="_blank" rel="noopener">
        <span class="score-num" data-count="4,6">4,6</span>
        <span class="stars" aria-label="4,6/5">★★★★★</span>
        <span class="score-label"><?= e(t('home.score_google')) ?></span>
      </a>
      <a class="quote quote--score" data-reveal data-delay="4" href="<?= e($CFG['facebook']) ?>reviews" target="_blank" rel="noopener">
        <span class="score-num" data-count="5,0">5,0</span>
        <span class="stars" aria-label="5/5">★★★★★</span>
        <span class="score-label"><?= e(t('home.score_fb')) ?></span>
      </a>
    </div>
    <p class="center" style="margin-top:28px">
      <a class="link-arrow" href="<?= e($CFG['facebook']) ?>reviews" target="_blank" rel="noopener"><?= e(t('home.reviews_link')) ?></a>
    </p>
  </div>
</section>

<section class="section section--paper">
  <div class="wrap">
    <div data-reveal>
      <h2><?= e(t('home.map_title')) ?></h2>
      <p class="lead"><?= e(t('home.map_text')) ?></p>
    </div>
    <iframe
      class="map-frame"
      loading="lazy"
      referrerpolicy="no-referrer-when-downgrade"
      title="<?= e($CFG['name']) ?> — <?= e(t('home.map_title')) ?>"
      src="https://www.google.com/maps?q=<?= rawurlencode($CFG['maps_query']) ?>&amp;hl=<?= e($LANG) ?>&amp;z=15&amp;output=embed"
      style="margin-top:24px"></iframe>
    <p style="margin-top:16px">
      <a class="link-arrow" href="https://www.google.com/maps/search/?api=1&amp;query=<?= rawurlencode($CFG['maps_query']) ?>" target="_blank" rel="noopener"><?= e(t('common.map_open')) ?></a>
    </p>
  </div>
</section>

<section class="cta-band">
  <div class="wrap">
    <h2><?= e(t('home.cta_title')) ?></h2>
    <p><?= e(t('home.cta_text')) ?></p>
    <div class="cta-actions">
      <a class="btn btn--primary" href="tel:<?= e($CFG['phone_raw']) ?>"><?= e($CFG['phone']) ?></a>
      <a class="btn btn--ghost" href="<?= e(u('szallas.php')) ?>#foglalas"><?= e(t('form.title')) ?></a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/inc/footer.php'; ?>
