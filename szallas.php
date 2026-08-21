<?php
require __DIR__ . '/inc/i18n.php';

$page       = 'szallas';
$meta_title = t('motel.meta_title');
$meta_desc  = t('motel.meta_desc');

require __DIR__ . '/inc/head.php';
?>

<section class="hero hero--compact">
  <div class="hero-bg" aria-hidden="true"></div>
  <div class="wrap">
    <span class="eyebrow"><?= e(t('motel.kicker')) ?></span>
    <h1><?= e(t('motel.title')) ?></h1>
    <p><?= e(t('motel.lead')) ?></p>
    <div class="hero-actions">
      <a class="btn btn--primary" href="#foglalas"><?= e(t('form.title')) ?></a>
      <a class="btn btn--ghost" href="tel:<?= e($CFG['phone_raw']) ?>"><?= e($CFG['phone']) ?></a>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="grid grid--split">
      <div data-reveal>
        <h2><?= e(t('motel.rooms_title')) ?></h2>
        <p><?= e(t('motel.rooms_text1')) ?></p>
        <p><?= e(t('motel.rooms_text2')) ?></p>
        <ul class="ticks">
          <?php foreach (ta('motel.features') as $feature): ?>
          <li><?= e($feature) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="gallery" data-reveal data-delay="1" style="grid-template-columns:1fr 1fr">
        <figure style="grid-column:1 / -1">
          <button class="shot" type="button" data-caption="<?= e(t('gallery.g_room')) ?>">
            <img src="images/szoba-1.jpg" alt="<?= e(t('gallery.g_room')) ?>" loading="lazy">
          </button>
          <figcaption><?= e(t('gallery.g_room')) ?></figcaption>
        </figure>
        <figure>
          <button class="shot" type="button" data-caption="<?= e(t('gallery.g_bath')) ?>">
            <img src="images/furdo.jpg" alt="<?= e(t('gallery.g_bath')) ?>" loading="lazy">
          </button>
          <figcaption><?= e(t('gallery.g_bath')) ?></figcaption>
        </figure>
        <figure>
          <button class="shot" type="button" data-caption="<?= e(t('gallery.g_night')) ?>">
            <img src="images/ejszaka.jpg" alt="<?= e(t('gallery.g_night')) ?>" loading="lazy">
          </button>
          <figcaption><?= e(t('gallery.g_night')) ?></figcaption>
        </figure>
      </div>
    </div>
  </div>
</section>

<section class="section section--paper">
  <div class="wrap">
    <div data-reveal>
      <h2><?= e(t('motel.prices_title')) ?></h2>
      <p class="lead"><?= e(t('motel.prices_lead')) ?></p>
    </div>

    <div class="table-wrap" data-reveal style="margin-top:26px">
      <table class="responsive">
        <thead>
          <tr>
            <th><?= e(t('motel.col_guests')) ?></th>
            <th class="num"><?= e(t('motel.col_no_bf')) ?></th>
            <th class="num"><?= e(t('motel.col_bf')) ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($CFG['room_prices'] as $guests => [$plain, $withBreakfast]): ?>
          <tr>
            <td data-label="<?= e(t('motel.col_guests')) ?>"><?= $guests ?> <?= e(t('motel.guests_unit')) ?></td>
            <td class="num" data-label="<?= e(t('motel.col_no_bf')) ?>"><?= e(ft($plain)) ?></td>
            <td class="num" data-label="<?= e(t('motel.col_bf')) ?>"><?= e(ft($withBreakfast)) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="note" data-reveal style="margin-top:24px"><?= e(t('motel.prices_note')) ?></div>
  </div>
</section>

<section class="section">
  <div class="wrap" style="max-width:760px">
    <?php
    if (isset($_GET['sent'])) {
        $ok = $_GET['sent'] === '1';
        echo '<div class="form-msg ' . ($ok ? 'is-ok' : 'is-bad') . '" style="margin-bottom:24px">'
            . e($ok ? t('form.success') : t('form.error'))
            . '</div>';
    }
    require __DIR__ . '/inc/booking-form.php';
    ?>
  </div>
</section>

<section class="cta-band">
  <div class="wrap">
    <h2><?= e(t('motel.cta_title')) ?></h2>
    <p><?= e(t('motel.cta_text')) ?></p>
    <div class="cta-actions">
      <a class="btn btn--primary" href="tel:<?= e($CFG['phone_raw']) ?>"><?= e($CFG['phone']) ?></a>
      <a class="btn btn--ghost" href="<?= e($CFG['facebook']) ?>" target="_blank" rel="noopener"><?= e(t('common.facebook')) ?></a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/inc/footer.php'; ?>
