<?php
require __DIR__ . '/inc/i18n.php';

$page       = 'gallery';
$meta_title = t('gallery.meta_title');
$meta_desc  = t('gallery.meta_desc');

require __DIR__ . '/inc/head.php';

/* Új fotó: tedd a fájlt az images mappába, vedd fel a $CFG['gallery']
   listába (inc/config.php), és adj feliratot a lang/*.php 'gallery' részében. */
?>

<section class="hero hero--compact">
  <div class="hero-bg" aria-hidden="true"></div>
  <div class="wrap">
    <span class="eyebrow"><?= e(t('gallery.kicker')) ?></span>
    <h1><?= e(t('gallery.title')) ?></h1>
    <p><?= e(t('gallery.lead')) ?></p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <?php if (t('gallery.note') !== '' && t('gallery.note') !== 'gallery.note'): ?>
    <div class="note" data-reveal style="margin-bottom:32px"><?= e(t('gallery.note')) ?></div>
    <?php endif; ?>

    <div class="gallery gallery--mosaic">
      <?php foreach ($CFG['gallery'] as $i => $shot):
          $caption = t('gallery.' . $shot['key']); ?>
      <figure data-reveal data-delay="<?= $i % 4 ?>">
        <button class="shot" type="button" data-caption="<?= e($caption) ?>">
          <img src="images/<?= e($shot['file']) ?>" alt="<?= e($caption) ?>" loading="lazy">
        </button>
        <figcaption><?= e($caption) ?></figcaption>
      </figure>
      <?php endforeach; ?>
    </div>

    <p style="margin-top:34px">
      <a class="link-arrow" href="<?= e($CFG['facebook']) ?>photos" target="_blank" rel="noopener"><?= e(t('common.more_fb')) ?></a>
    </p>
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
