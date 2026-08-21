<?php
/**
 * Közös fejléc. Használat az oldalak elején:
 *   $page = 'motel'; $meta_title = t('motel.meta_title'); …
 *   require __DIR__ . '/inc/head.php';
 */
require_once __DIR__ . '/i18n.php';

$page       = $page       ?? 'home';
$meta_title = $meta_title ?? t('home.meta_title');
$meta_desc  = $meta_desc  ?? t('home.meta_desc');
$hero_photo = $hero_photo ?? true;

$state   = open_state($CFG);
$dayNow  = (int) (new DateTime('now', new DateTimeZone('Europe/Budapest')))->format('w');
$navItems = [
    'home'    => ['index.php',     t('nav.home')],
    'menu'    => ['etlap.php',     t('nav.menu')],
    'szallas' => ['szallas.php',   t('nav.motel')],
    'gallery' => ['galeria.php',   t('nav.gallery')],
    'contact' => ['kapcsolat.php', t('nav.contact')],
];
?>
<!DOCTYPE html>
<html lang="<?= e(t('html_lang')) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="format-detection" content="telephone=yes">
<meta name="apple-mobile-web-app-title" content="Mississippi">
<meta name="mobile-web-app-capable" content="yes">
<title><?= e($meta_title) ?></title>
<meta name="description" content="<?= e($meta_desc) ?>">
<meta name="theme-color" content="#12241f">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($meta_title) ?>">
<meta property="og:description" content="<?= e($meta_desc) ?>">
<meta property="og:image" content="images/epulet.jpg">
<meta property="og:locale" content="<?= e(t('locale')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap">
<link rel="stylesheet" href="assets/app.css?v=6">
<script>
  // villanás nélküli téma: még a CSS előtt beállítjuk
  (function () {
    try {
      var t = localStorage.getItem('theme');
      if (!t) t = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
      document.documentElement.setAttribute('data-theme', t);
    } catch (e) {}
  })();
</script>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%2312241f'/><text y='70' x='50' text-anchor='middle' font-size='60' fill='%23e0a355' font-family='Georgia,serif'>M</text></svg>">
<?php foreach (LANGS as $code => $label): ?>
<link rel="alternate" hreflang="<?= e($code) ?>" href="<?= e(switch_url($code)) ?>">
<?php endforeach; ?>
<script type="application/ld+json">
<?= json_encode([
    '@context'    => 'https://schema.org',
    '@type'       => ['Restaurant', 'LodgingBusiness'],
    'name'        => $CFG['name'],
    'description' => $meta_desc,
    'servesCuisine' => 'Hungarian',
    'priceRange'  => '$',
    'telephone'   => $CFG['phone_raw'],
    'image'       => 'images/epulet.jpg',
    'address'     => [
        '@type'           => 'PostalAddress',
        'streetAddress'   => $CFG['street'],
        'addressLocality' => $CFG['city'],
        'postalCode'      => $CFG['zip'],
        'addressCountry'  => $CFG['country'],
    ],
    'openingHoursSpecification' => array_map(
        fn($d, $h) => [
            '@type'     => 'OpeningHoursSpecification',
            'dayOfWeek' => ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][$d],
            'opens'     => $h[0],
            'closes'    => $h[1],
        ],
        array_keys($CFG['hours']),
        $CFG['hours']
    ),
    'sameAs' => [$CFG['facebook']],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>
</head>
<body class="page-<?= e($page) ?>">

<a class="skip-link" href="#main"><?= e(t('nav.home')) ?></a>
<div class="progress" aria-hidden="true"></div>

<header class="site-header" id="site-header">
  <div class="wrap header-inner">
    <a class="brand" href="<?= e(u('index.php')) ?>">
      <span class="brand-mark">M</span>
      <span class="brand-text">Mississippi <em>Büfé &amp; Szálláshely</em></span>
    </a>

    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" aria-label="<?= e(t('nav.open')) ?>">
      <span class="nav-toggle-bars"></span>
    </button>

    <nav class="site-nav" id="site-nav" aria-label="<?= e(t('nav.open')) ?>">
      <ul class="nav-list">
        <?php foreach ($navItems as $key => [$href, $label]): ?>
        <li><a href="<?= e(u($href)) ?>"<?= $page === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
        <?php endforeach; ?>
      </ul>

      <div class="nav-side">
        <button class="theme-toggle" type="button" aria-label="Dark / light" aria-pressed="false">
          <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
          <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4m11.4-11.4 1.4-1.4"/></svg>
        </button>
        <div class="lang-switch" role="group" aria-label="Language">
          <?php foreach (LANGS as $code => $label): ?>
          <a href="<?= e(switch_url($code)) ?>" class="lang<?= $LANG === $code ? ' is-active' : '' ?>" hreflang="<?= e($code) ?>" title="<?= e($label) ?>"><?= e(strtoupper($code)) ?></a>
          <?php endforeach; ?>
        </div>
        <a class="btn btn--primary btn--sm" href="tel:<?= e($CFG['phone_raw']) ?>" aria-label="<?= e(t('common.call')) ?>: <?= e($CFG['phone']) ?>">
          <svg class="btn-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 3h4l2 5-2.5 1.5a12 12 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2.2 2A17 17 0 0 1 3 5.2 2 2 0 0 1 5 3Z"/></svg>
          <span class="btn-txt"><?= e($CFG['phone']) ?></span>
        </a>
      </div>
    </nav>
  </div>

  <div class="status-bar">
    <div class="wrap">
      <span class="status <?= $state['open'] ? 'is-open' : 'is-closed' ?>"
            data-hours='<?= json_encode($CFG['hours'], JSON_UNESCAPED_UNICODE) ?>'
            data-labels='<?= json_encode([
                'open'   => t('status.open'),
                'closed' => t('status.closed'),
                'until'  => t('status.until'),
                'opens'  => t('status.opens'),
                'opensDay' => t('status.opens_day'),
                'days'   => ta('days_short'),
            ], JSON_UNESCAPED_UNICODE) ?>'>
        <span class="status-dot" aria-hidden="true"></span>
        <span class="status-text">
          <?php if ($state['open']): ?>
            <strong><?= e(t('status.open')) ?></strong> · <?= e(sprintf(t('status.until'), $state['until'])) ?>
          <?php else: ?>
            <strong><?= e(t('status.closed')) ?></strong>
            <?php if (isset($state['next'])): ?>
              · <?= e(($state['nextDay'] ?? $dayNow) === $dayNow
                    ? sprintf(t('status.opens'), $state['next'])
                    : sprintf(t('status.opens_day'), ta('days_short')[$state['nextDay']] ?? '', $state['next'])) ?>
            <?php endif; ?>
          <?php endif; ?>
        </span>
      </span>
      <a class="status-link" href="<?= e(u('kapcsolat.php')) ?>#nyitvatartas"><?= e(t('common.hours')) ?> →</a>
    </div>
  </div>
</header>

<main id="main">
