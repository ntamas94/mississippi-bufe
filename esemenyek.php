<?php
require __DIR__ . '/inc/i18n.php';

$page       = 'events';
$meta_title = t('events.meta_title');
$meta_desc  = t('events.meta_desc');

require __DIR__ . '/inc/head.php';

/* A Facebook-idővonal automatikusan betöltődik. */
?>

<section class="hero hero--compact">
  <div class="hero-bg" aria-hidden="true"></div>
  <div class="wrap">
    <span class="eyebrow"><?= e(t('events.kicker')) ?></span>
    <h1><?= e(t('events.title')) ?></h1>
    <p><?= e(t('events.lead')) ?></p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="center" data-reveal>
      <div class="fb-embed" id="fbEmbed" data-page="<?= e($CFG['facebook']) ?>"></div>
      <p class="center" style="margin-top:22px">
        <a class="link-arrow" href="<?= e($CFG['facebook']) ?>" target="_blank" rel="noopener"><?= e(t('events.fb_open')) ?></a>
      </p>
    </div>
  </div>
</section>

<section class="cta-band">
  <div class="wrap">
    <h2><?= e(t('home.cta_title')) ?></h2>
    <p><?= e(t('home.cta_text')) ?></p>
    <div class="cta-actions">
      <a class="btn btn--primary" href="tel:<?= e($CFG['phone_raw']) ?>"><?= e($CFG['phone']) ?></a>
      <a class="btn btn--ghost" href="<?= e(u('kapcsolat.php')) ?>"><?= e(t('nav.contact')) ?></a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/inc/footer.php'; ?>
