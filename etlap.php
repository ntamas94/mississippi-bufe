<?php
require __DIR__ . '/inc/i18n.php';

$page       = 'menu';
$meta_title = t('menu.meta_title');
$meta_desc  = t('menu.meta_desc');

require __DIR__ . '/inc/head.php';
?>

<section class="hero hero--compact">
  <div class="hero-bg" aria-hidden="true"></div>
  <div class="wrap">
    <span class="eyebrow"><?= e(t('menu.kicker')) ?></span>
    <h1><?= e(t('menu.title')) ?></h1>
    <p><?= e(t('menu.lead')) ?></p>
  </div>
</section>

<section class="section">
  <div class="wrap">

    <?php /* Az árakat a lang/*.php étlapcsoportjaiban lehet megadni: 'price' => 2490 */ ?>
    <div class="note" data-reveal style="margin-bottom:28px"><?= e(t('menu.todo')) ?></div>

    <div class="menu-filter" role="tablist" aria-label="<?= e(t('menu.title')) ?>">
      <button class="chip is-active" type="button" data-filter="all"><?= e(t('menu.all')) ?></button>
      <?php foreach (ta('menu.groups') as $gi => $group): ?>
      <button class="chip" type="button" data-filter="g<?= $gi ?>"><?= e($group['title']) ?></button>
      <?php endforeach; ?>
    </div>

    <?php foreach (ta('menu.groups') as $gi => $group): ?>
    <div class="menu-group" data-group="g<?= $gi ?>" data-reveal>
      <div class="menu-group-head">
        <h2><?= e($group['title']) ?></h2>
        <?php if (!empty($group['note'])): ?>
        <span class="note-text" style="color:var(--ink-faint);font-size:.92rem"><?= e($group['note']) ?></span>
        <?php endif; ?>
      </div>
      <ul class="menu-list">
        <?php foreach ($group['items'] as $item): ?>
        <li class="menu-item">
          <span class="name">
            <?= e($item['name']) ?>
            <?php if (!empty($item['desc'])): ?><span class="desc"><?= e($item['desc']) ?></span><?php endif; ?>
          </span>
          <span class="dots" aria-hidden="true"></span>
          <?php if (isset($item['price'])): ?>
          <span class="price"><?= e(ft((int) $item['price'])) ?></span>
          <?php else: ?>
          <span class="price price--todo">••• Ft</span>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endforeach; ?>

    <p class="lead"><?= e(t('menu.allergen')) ?></p>
  </div>
</section>

<section class="cta-band">
  <div class="wrap">
    <h2><?= e(t('menu.cta_title')) ?></h2>
    <p><?= e(t('menu.cta_text')) ?></p>
    <div class="cta-actions">
      <a class="btn btn--primary" href="tel:<?= e($CFG['phone_raw']) ?>"><?= e($CFG['phone']) ?></a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/inc/footer.php'; ?>
