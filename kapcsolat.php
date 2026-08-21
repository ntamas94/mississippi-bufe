<?php
require __DIR__ . '/inc/i18n.php';

$page       = 'contact';
$meta_title = t('contact.meta_title');
$meta_desc  = t('contact.meta_desc');

require __DIR__ . '/inc/head.php';
?>

<section class="hero hero--compact">
  <div class="hero-bg" aria-hidden="true"></div>
  <div class="wrap">
    <span class="eyebrow"><?= e(t('contact.kicker')) ?></span>
    <h1><?= e(t('contact.title')) ?></h1>
    <p><?= e(t('contact.lead')) ?></p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="grid grid--split">

      <div>
        <div data-reveal>
          <h2><?= e(t('contact.data_title')) ?></h2>
          <div class="card card--pad">
            <p><strong><?= e($CFG['name']) ?></strong></p>
            <p>
              <?= e($CFG['zip']) ?> <?= e($CFG['city']) ?><br>
              <?= e($CFG['street']) ?><br>
              Vas, <?= e($CFG['country']) ?>
            </p>
            <p>
              <?= e(t('common.phone')) ?>: <a href="tel:<?= e($CFG['phone_raw']) ?>"><?= e($CFG['phone']) ?></a><br>
              Facebook: <a href="<?= e($CFG['facebook']) ?>" target="_blank" rel="noopener">Szipiszupi23</a>
            </p>
            <?php /* Ha van e-mail cím, vedd ki a megjegyzésből és írd át:
            <p>E-mail: <a href="mailto:info@mississippibufe.hu">info@mississippibufe.hu</a></p>
            */ ?>
          </div>
        </div>

        <div data-reveal style="margin-top:44px">
          <h2 id="nyitvatartas"><?= e(t('contact.hours_title')) ?></h2>
          <ul class="hours">
            <?php foreach ([1, 2, 3, 4, 5, 6, 0] as $d):
                $h = $CFG['hours'][$d] ?? null; ?>
            <li<?= $d === $dayNow ? ' class="is-today"' : '' ?>>
              <span class="day"><?= e(ta('days')[$d]) ?></span>
              <span class="time"><?= $h ? e($h[0] . ' – ' . $h[1]) : '—' ?></span>
            </li>
            <?php endforeach; ?>
          </ul>
          <p style="margin-top:16px"><small><?= e(t('contact.hours_note')) ?></small></p>
        </div>
      </div>

      <div data-reveal data-delay="1">
        <h2><?= e(t('contact.route_title')) ?></h2>
        <p><?= e(t('contact.route_text')) ?></p>
        <ul class="ticks">
          <?php foreach (ta('contact.route_list') as $step): ?>
          <li><?= e($step) ?></li>
          <?php endforeach; ?>
        </ul>

        <iframe
          class="map-frame"
          style="margin-top:24px"
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
          title="<?= e($CFG['name']) ?> — <?= e(t('contact.route_title')) ?>"
          src="https://www.google.com/maps?q=<?= rawurlencode($CFG['maps_query']) ?>&amp;hl=<?= e($LANG) ?>&amp;z=15&amp;output=embed"></iframe>

        <p style="margin-top:14px">
          <a class="link-arrow" href="https://www.google.com/maps/dir/?api=1&amp;destination=<?= rawurlencode($CFG['maps_query']) ?>" target="_blank" rel="noopener"><?= e(t('common.route')) ?></a>
        </p>
      </div>

    </div>
  </div>
</section>

<section class="section section--paper">
  <div class="wrap" style="max-width:760px">
    <?php require __DIR__ . '/inc/booking-form.php'; ?>
  </div>
</section>

<section class="cta-band">
  <div class="wrap">
    <h2><?= e(t('common.call')) ?></h2>
    <p><?= e(t('contact.lead')) ?></p>
    <div class="cta-actions">
      <a class="btn btn--primary" href="tel:<?= e($CFG['phone_raw']) ?>"><?= e($CFG['phone']) ?></a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/inc/footer.php'; ?>
