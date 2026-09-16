<?php
/**
 * Közös admin fejléc — az oldal saját kinézetével.
 * Elvárt változók: $adminTitle (string), $adminTab ('texts' | 'basics' | 'images').
 * A szövegek fülön a $langs / $lang is kell a nyelvváltóhoz.
 */
$adminTitle = $adminTitle ?? 'Szerkesztés';
$adminTab   = $adminTab   ?? 'texts';
$adminTabs  = [
    'texts'  => ['index.php',      'Szövegek'],
    'basics' => ['alapadatok.php', 'Nyitvatartás, elérhetőség, árak'],
    'images' => ['kepek.php',      'Képek'],
];
?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex,nofollow">
<title><?= h($adminTitle) ?> — Mississippi Büfé &amp; Missouri Szálláshely</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap">
<link rel="stylesheet" href="../assets/app.css?v=9">
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
<style>
  /* Csak a szerkesztéshez kell — az oldal saját színeit és formáit használja. */
  .admin-tabs { background: var(--bg-alt); border-bottom: 1px solid var(--line); }
  .admin-tabs .wrap { display: flex; gap: 2px; overflow-x: auto; }
  .admin-tabs a { padding: 14px 18px; text-decoration: none; color: var(--ink-soft); font-weight: 600;
                  border-bottom: 3px solid transparent; white-space: nowrap; font-size: .95rem; }
  .admin-tabs a:hover { color: var(--ink); }
  .admin-tabs a[aria-current="page"] { color: var(--amber-600); border-bottom-color: var(--amber); }

  .edit-main { padding: 34px 0 40px; }
  .edit-head { margin-bottom: 22px; }
  .edit-head h1 { margin: 6px 0 10px; }
  .edit-msg { margin: 0 0 20px; padding: 15px 20px; border-radius: var(--r-sm); font-weight: 500; }
  .edit-msg.is-ok  { background: rgba(63, 154, 109, .14); color: #2c7a55; border: 1px solid rgba(63, 154, 109, .35); }
  .edit-msg.is-bad { background: rgba(207, 95, 65, .13); color: #b14b30; border: 1px solid rgba(207, 95, 65, .4); }

  .edit-panel { background: var(--surface); border: 1px solid var(--line);
                border-radius: var(--r); box-shadow: var(--shadow-sm); padding: 24px 26px; margin-bottom: 18px; }
  .edit-panel h2 { font-size: 1.15rem; margin: 0 0 6px; }
  .edit-panel .hint { color: var(--ink-soft); font-size: .92rem; margin: 0 0 18px; }
  .edit-panel .hint:last-child { margin-bottom: 0; }

  .basics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 16px; }
  .edit-row label { font-size: .87rem; font-weight: 600; color: var(--ink-soft);
                    display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
  .edit-row input, .edit-row textarea, .edit-row select {
                    width: 100%; padding: 11px 14px; border: 1px solid var(--line); margin-top: 6px;
                    border-radius: var(--r-sm); background: var(--surface-2); color: var(--ink); font: inherit; }
  .edit-row textarea { min-height: 84px; resize: vertical; line-height: 1.5; }
  .edit-row input:focus, .edit-row textarea:focus, .edit-row select:focus { outline: 2px solid var(--amber); border-color: transparent; }
  .edit-row .sub { display: block; font-weight: 400; color: var(--ink-faint); font-size: .8rem; margin-top: 4px; }
  .edit-row.is-edited input, .edit-row.is-edited textarea { border-color: var(--amber); background: rgba(224, 163, 85, .06); }
  .tag-edited { font-weight: 600; color: var(--amber-600); font-size: .78rem;
                background: rgba(224, 163, 85, .16); padding: 2px 8px; border-radius: 999px; }
  .btn-reset { border: 1px solid var(--line); background: transparent; color: var(--ink-soft);
               font: inherit; font-size: .78rem; padding: 2px 10px; border-radius: 999px; cursor: pointer; }
  .btn-reset:hover { border-color: var(--amber); color: var(--amber-600); }
  .orig { font-size: .82rem; color: var(--ink-faint); margin: 0; }

  .price-table, .hours-table { width: 100%; border-collapse: collapse; }
  .price-table th, .price-table td, .hours-table th, .hours-table td { padding: 8px 10px; text-align: left; vertical-align: middle; }
  .price-table thead th, .hours-table thead th { font-size: .85rem; color: var(--ink-faint); font-weight: 600; }
  .price-table tbody th, .hours-table tbody th { font-weight: 600; white-space: nowrap; }
  .price-table input, .hours-table input[type=time] { width: 130px; padding: 10px 12px; border: 1px solid var(--line);
                       border-radius: var(--r-sm); background: var(--surface-2); color: var(--ink); font: inherit; }
  .price-table tr + tr th, .price-table tr + tr td,
  .hours-table tr + tr th, .hours-table tr + tr td { border-top: 1px solid var(--line-soft); }
  .hours-table tr.is-closed input[type=time] { opacity: .35; pointer-events: none; }
  .hours-table label.closed { display: inline-flex; align-items: center; gap: 6px; font-size: .88rem; color: var(--ink-soft); white-space: nowrap; }
  .hours-table input[type=checkbox] { width: 18px; height: 18px; accent-color: var(--amber-600); }

  .edit-tools { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 16px; }
  .edit-search { flex: 1 1 260px; padding: 12px 15px; border: 1px solid var(--line);
                 border-radius: var(--r-sm); background: var(--surface-2); color: var(--ink); font: inherit; }
  .edit-count { color: var(--ink-faint); font-size: .9rem; }

  .edit-sec { background: var(--surface); border: 1px solid var(--line);
              border-radius: var(--r); margin-bottom: 12px; overflow: hidden; }
  .edit-sec > summary { cursor: pointer; padding: 16px 22px; font-family: var(--serif);
                        font-size: 1.05rem; font-weight: 600; list-style: none; display: flex;
                        align-items: baseline; gap: 10px; flex-wrap: wrap; }
  .edit-sec > summary::-webkit-details-marker { display: none; }
  .edit-sec > summary::before { content: '▸'; color: var(--amber); font-size: .9em; }
  .edit-sec[open] > summary::before { content: '▾'; }
  .edit-sec > summary:hover { background: var(--bg-sunk); }
  .edit-sec small { color: var(--ink-faint); font-weight: 400; font-family: var(--sans); font-size: .85rem; }
  .edit-body { padding: 4px 22px 22px; }
  .edit-body > .hint { color: var(--ink-soft); font-size: .9rem; margin: 0 0 16px; }
  .edit-group { border-top: 1px solid var(--line-soft); padding-top: 16px; margin-top: 16px; }
  .edit-group:first-child { border-top: 0; padding-top: 0; margin-top: 0; }
  .edit-group h3 { font-size: .95rem; margin: 0 0 12px; color: var(--ink-soft); font-family: inherit; }
  .edit-rows { display: grid; gap: 14px; }

  .savebar { position: sticky; bottom: 0; z-index: 3; margin-top: 18px;
             background: color-mix(in srgb, var(--bg-alt) 92%, transparent);
             backdrop-filter: blur(8px); border-top: 1px solid var(--line);
             padding: 14px 0; display: flex; gap: 14px; align-items: center; justify-content: flex-end; }
  .savebar .note { margin: 0; margin-right: auto; color: var(--ink-faint); font-size: .88rem; }
  .savebar--inline { position: static; border: 0; background: none; backdrop-filter: none; padding: 16px 0 0; }

  /* Képek */
  .img-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 16px; }
  .img-card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--r);
              overflow: hidden; display: flex; flex-direction: column; }
  .img-card img { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; display: block; background: var(--bg-sunk); }
  .img-card.is-hidden img { opacity: .35; filter: grayscale(1); }
  .img-card .body { padding: 12px 14px 14px; display: grid; gap: 8px; font-size: .88rem; flex: 1; }
  .img-card .cap { font-weight: 600; }
  .img-card .use, .img-card .meta { color: var(--ink-faint); font-size: .8rem; }
  .img-card .actions { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-top: auto; }
  .img-card input[type=file] { position: absolute; width: 1px; height: 1px; opacity: 0; overflow: hidden; }
  .btn--xs { padding: 7px 14px; font-size: .82rem; }
  .btn--quiet { --btn-bg: transparent; --btn-fg: var(--ink-soft); border-color: var(--line); }
  .btn--quiet:hover { border-color: var(--amber); color: var(--amber-600); box-shadow: none; }
  .btn--danger { --btn-bg: transparent; --btn-fg: var(--warn); border-color: rgba(207, 95, 65, .5); }
  .btn--danger:hover { box-shadow: none; background: rgba(207, 95, 65, .08); }
  .img-card.is-busy { opacity: .6; pointer-events: none; }
  .upload-box { border: 2px dashed var(--line); border-radius: var(--r); padding: 22px; text-align: center;
                background: var(--surface-2); }
  .upload-box.is-over { border-color: var(--amber); background: rgba(224, 163, 85, .08); }
  .upload-box .edit-row { text-align: left; margin-top: 14px; }
  .upload-preview { max-width: 320px; max-height: 240px; border-radius: var(--r-sm); margin: 12px auto 0; display: none; }
  .upload-preview.is-on { display: block; }

  .edit-foot { border-top: 1px solid var(--line); padding: 22px 0 40px; color: var(--ink-faint); font-size: .88rem; }
  @media (max-width: 640px) {
    .edit-panel { padding: 20px 18px; }
    .edit-body { padding: 4px 16px 18px; }
    .price-table input, .hours-table input[type=time] { width: 100%; min-width: 92px; }
  }
</style>
</head>
<body class="page-admin">

<a class="skip-link" href="#main">Ugrás a tartalomhoz</a>

<header class="site-header" id="site-header">
  <div class="wrap header-inner">
    <a class="brand" href="../index.php">
      <span class="brand-mark">M</span>
      <span class="brand-text">Mississippi <em>Büfé &amp; Szálláshely</em></span>
    </a>

    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" aria-label="Menü">
      <span class="nav-toggle-bars"></span>
    </button>

    <nav class="site-nav" id="site-nav" aria-label="Menü">
      <ul class="nav-list">
        <li><a href="../index.php">Kezdőlap</a></li>
        <li><a href="../etlap.php">Étlap</a></li>
        <li><a href="../szallas.php">Szálláshely</a></li>
        <li><a href="../esemenyek.php">Események</a></li>
        <li><a href="../galeria.php">Galéria</a></li>
        <li><a href="../kapcsolat.php">Kapcsolat</a></li>
        <li><a href="index.php" aria-current="page">Szerkesztés</a></li>
      </ul>

      <div class="nav-side">
        <button class="theme-toggle" type="button" aria-label="Sötét / világos" aria-pressed="false">
          <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
          <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4m11.4-11.4 1.4-1.4"/></svg>
        </button>
        <?php if ($adminTab === 'texts' && isset($langs, $lang)): ?>
        <div class="lang-switch" role="group" aria-label="Szerkesztett nyelv">
          <?php foreach ($langs as $code => $label): ?>
          <a href="index.php?lang=<?= h($code) ?>" class="lang<?= $code === $lang ? ' is-active' : '' ?>" title="<?= h($label) ?>"><?= h(strtoupper($code)) ?></a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <a class="btn btn--primary btn--sm" href="logout.php">Kilépés</a>
      </div>
    </nav>
  </div>
</header>

<nav class="admin-tabs" aria-label="Szerkesztés részei">
  <div class="wrap">
    <?php foreach ($adminTabs as $key => [$href, $label]): ?>
    <a href="<?= h($href) ?>"<?= $key === $adminTab ? ' aria-current="page"' : '' ?>><?= h($label) ?></a>
    <?php endforeach; ?>
  </div>
</nav>

<main id="main" class="edit-main">
  <div class="wrap">
