<?php
require __DIR__ . '/inc/i18n.php';

$page       = 'menu';
$meta_title = t('menu.meta_title');
$meta_desc  = t('menu.meta_desc');

/* Az étlap két fülre bomlik: Ételek és Italok.
   Egy csoport a 'tab' kulccsal sorolható be ('food' vagy 'drinks'); ha nincs megadva,
   az első MENU_FOOD_GROUPS csoport étel, a többi ital.
   Árak a lang/*.php étlapcsoportjaiban: 'price' => 2490 vagy 'price_text' => '150 Ft / dl' */
const MENU_FOOD_GROUPS = 3;

$tabs = [
    'food'   => ['label' => t('menu.tab_food'),   'hash' => 'etelek', 'groups' => []],
    'drinks' => ['label' => t('menu.tab_drinks'), 'hash' => 'italok', 'groups' => []],
];
foreach (ta('menu.groups') as $gi => $group) {
    $key = $group['tab'] ?? ($gi < MENU_FOOD_GROUPS ? 'food' : 'drinks');
    if (!isset($tabs[$key])) {
        $key = 'drinks';
    }
    $tabs[$key]['groups'][] = $group;
}

$tabIcons = [
    'food'   => '<path d="M6 2v7a2 2 0 0 0 4 0V2M8 11v11"/><path d="M18 22V2c-2.2 1.5-3 4.5-3 8 0 2 1 3 3 3"/>',
    'drinks' => '<path d="M5 4h14l-1.5 16a2 2 0 0 1-2 2h-7a2 2 0 0 1-2-2L5 4z"/><path d="M5.6 9h12.8"/>',
];

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

    <div class="menu-tabs" role="tablist" aria-label="<?= e(t('menu.title')) ?>">
      <?php $first = true; foreach ($tabs as $key => $tab): ?>
      <button class="menu-tab<?= $first ? ' is-active' : '' ?>" type="button" role="tab"
              id="tab-<?= $key ?>" aria-controls="panel-<?= $key ?>"
              aria-selected="<?= $first ? 'true' : 'false' ?>" tabindex="<?= $first ? '0' : '-1' ?>"
              data-tab="<?= $key ?>" data-hash="<?= e($tab['hash']) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $tabIcons[$key] ?></svg>
        <?= e($tab['label']) ?>
      </button>
      <?php $first = false; endforeach; ?>
    </div>

    <?php $first = true; foreach ($tabs as $key => $tab): ?>
    <div class="menu-panel" id="panel-<?= $key ?>" role="tabpanel" aria-labelledby="tab-<?= $key ?>" data-panel="<?= $key ?>"<?= $first ? '' : ' hidden' ?>>
      <div class="menu-cards">
        <?php foreach ($tab['groups'] as $group): ?>
        <article class="menu-card">
          <header class="menu-card-head">
            <h2><?= e($group['title']) ?></h2>
            <?php if (!empty($group['note'])): ?>
            <p class="menu-card-note"><?= e($group['note']) ?></p>
            <?php endif; ?>
          </header>
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
              <?php elseif (isset($item['price_text'])): ?>
              <span class="price"><?= e($item['price_text']) ?></span>
              <?php endif; ?>
            </li>
            <?php endforeach; ?>
          </ul>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
    <?php $first = false; endforeach; ?>

    <p class="menu-allergen"><?= e(t('menu.allergen')) ?></p>
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
