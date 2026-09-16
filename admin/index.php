<?php
/**
 * Szerkesztő felület — az oldal saját kinézetével, hétköznapi feliratokkal.
 *
 * A lang/*.php minden szövege és a szobaárak átírhatók böngészőből.
 * A módosítások a data/overrides.<nyelv>.json és a data/site.json fájlba
 * kerülnek, az eredeti fájlokhoz nem nyúlunk — így bármikor vissza lehet
 * térni az alapszöveghez (elég a mezőt visszaírni az eredetire).
 */
require __DIR__ . '/_auth.php';
admin_require();

$ROOT  = dirname(__DIR__);
$langs = ['hu' => 'Magyar', 'en' => 'English', 'de' => 'Deutsch'];
$lang  = (isset($_GET['lang']) && isset($langs[$_GET['lang']])) ? $_GET['lang'] : 'hu';

/** A nyelvi tömb "levél" értékei (szövegek és számok) pont-útvonallal. */
function flat_leaves(array $arr, string $prefix = ''): array
{
    $out = [];
    foreach ($arr as $k => $v) {
        $path = $prefix === '' ? (string) $k : $prefix . '.' . $k;
        if (is_array($v)) {
            $out += flat_leaves($v, $path);
        } elseif (is_string($v) || is_int($v)) {
            $out[$path] = $v;
        }
    }
    return $out;
}

$base = require $ROOT . '/lang/' . $lang . '.php';
$flat = flat_leaves($base);
foreach (array_keys($flat) as $p) {                    // technikai kulcsok nem szerkeszthetők
    if (preg_match('/^(html_lang|locale)($|\.)/', $p)) {
        unset($flat[$p]);
    }
}

$ovFile    = ADMIN_DATA . '/overrides.' . $lang . '.json';
$overrides = is_file($ovFile) ? (json_decode((string) file_get_contents($ovFile), true) ?: []) : [];
$siteFile  = ADMIN_DATA . '/site.json';
$msg       = '';
$msgOk     = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_check();
    if (!is_dir(ADMIN_DATA)) {
        mkdir(ADMIN_DATA, 0775, true);
    }

    // ---- Szövegek mentése ----
    if (($_POST['which'] ?? '') === 'texts' && isset($_POST['f']) && is_array($_POST['f'])) {
        $new = [];
        foreach ($flat as $path => $baseVal) {
            if (!array_key_exists($path, $_POST['f'])) {
                continue;
            }
            $val = str_replace("\r\n", "\n", (string) $_POST['f'][$path]);
            if (is_int($baseVal)) {                    // ár: csak számjegyek
                $num = preg_replace('/\D/', '', $val);
                if ($num === '') {
                    continue;
                }
                $val = (int) $num;
                if ($val === $baseVal) {
                    continue;                          // egyezik az alappal → nincs felülírás
                }
            } elseif ($val === $baseVal) {
                continue;
            }
            $new[$path] = $val;
        }
        $ok = file_put_contents(
            $ovFile,
            json_encode($new, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
        $overrides = $new;
        $msgOk = $ok !== false;
        $msg   = $ok === false
            ? 'Nem sikerült menteni. A szerveren a data mappa nem írható.'
            : 'Elmentve. Az oldalon már az új szöveg látszik. Jelenleg ' . count($new)
              . ' mező tér el az eredetitől (' . $langs[$lang] . ').';
    }

    // ---- Szobaárak mentése ----
    if (($_POST['which'] ?? '') === 'site') {
        $site = is_file($siteFile) ? (json_decode((string) file_get_contents($siteFile), true) ?: []) : [];
        $rp = [];
        foreach ([1, 2, 3, 4] as $g) {
            $plain = (int) preg_replace('/\D/', '', (string) ($_POST['rp'][$g][0] ?? ''));
            $bf    = (int) preg_replace('/\D/', '', (string) ($_POST['rp'][$g][1] ?? ''));
            if ($plain > 0 && $bf > 0) {
                $rp[$g] = [$plain, $bf];
            }
        }
        if ($rp) {
            $site['room_prices'] = $rp;
        }
        $bpp = (int) preg_replace('/\D/', '', (string) ($_POST['bpp'] ?? ''));
        if ($bpp > 0) {
            $site['breakfast_per_person'] = $bpp;
        }
        $ok    = file_put_contents(
            $siteFile,
            json_encode($site, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
        $msgOk = $ok !== false;
        $msg   = $ok === false
            ? 'Nem sikerült menteni. A szerveren a data mappa nem írható.'
            : 'Szobaárak elmentve.';
    }
}

$CFG = require $ROOT . '/inc/config.php';              // már a mentett árakkal

/* ---------------------------------------------------------------
   Emberi feliratok: a technikai kulcsok helyett magyar megnevezés
   --------------------------------------------------------------- */

$sectionNames = [
    'nav'        => 'Menüsor',
    'days'       => 'Napok neve',
    'days_short' => 'Napok rövidítve',
    'status'     => 'Nyitva / zárva jelző',
    'common'     => 'Visszatérő feliratok',
    'home'       => 'Kezdőlap',
    'menu'       => 'Étlap és árak',
    'motel'      => 'Szálláshely oldal',
    'events'     => 'Események oldal',
    'gallery'    => 'Galéria',
    'contact'    => 'Kapcsolat oldal',
    'form'       => 'Foglalási űrlap',
    'booking'    => 'Foglalás',
    'footer'     => 'Lábléc',
];

$sectionHints = [
    'menu'    => 'Az étlap tételei és áraik. Az ár mezőbe csak számot írj, a „Ft” magától kerül oda.',
    'home'    => 'A kezdőlap szövegei fentről lefelé haladva.',
    'motel'   => 'A szálláshely oldal szövegei: szobák, szolgáltatások, árakhoz tartozó megjegyzések.',
    'contact' => 'Elérhetőségek környéki szövegek. A telefonszám és a cím nem itt, hanem a fejlécben mindenhol együtt változik.',
    'form'    => 'A foglalási űrlap feliratai és hibaüzenetei.',
    'common'  => 'Olyan feliratok, amelyek több oldalon is megjelennek (például „Tovább”, „Hívás”).',
];

$leafLabels = [
    'title'        => 'Cím',
    'lead'         => 'Bevezető szöveg',
    'desc'         => 'Leírás',
    'text'         => 'Szöveg',
    'note'         => 'Megjegyzés',
    'name'         => 'Név',
    'price'        => 'Ár (Ft)',
    'price_text'   => 'Ár szövegesen',
    'label'        => 'Felirat',
    'num'          => 'Szám',
    'kicker'       => 'Kis felirat a cím fölött',
    'eyebrow'      => 'Kis felirat a cím fölött',
    'meta_title'   => 'Böngésző címsora (ez látszik a Google találatban)',
    'meta_desc'    => 'Rövid leírás a Google találatban',
    'cta_title'    => 'Kiemelt doboz címe',
    'cta_text'     => 'Kiemelt doboz szövege',
    'cta_menu'     => 'Kiemelt doboz: étlap gomb',
    'cta_book'     => 'Kiemelt doboz: foglalás gomb',
    'btn'          => 'Gomb felirata',
    'quote'        => 'Idézet',
    'quote_src'    => 'Idézet forrása',
    'allergen'     => 'Allergén tájékoztató',
    'phone'        => 'Telefon felirat',
    'address'      => 'Cím felirat',
    'hours'        => 'Nyitvatartás felirat',
    'call'         => 'Hívás gomb',
    'pages'        => 'Oldalak felirat',
    'facebook'     => 'Facebook link felirata',
    'footer_note'  => 'Lábléc megjegyzés',
    'map_title'    => 'Térkép címe',
    'map_text'     => 'Térkép melletti szöveg',
    'map_open'     => 'Térkép megnyitása gomb',
    'street_view'  => 'Street View gomb',
    'route'        => 'Útvonal gomb felirata',
    'route_title'  => 'Útvonal címe',
    'route_text'   => 'Útvonal szövege',
    'open'         => 'Nyitva felirat',
    'closed'       => 'Zárva felirat',
    'until'        => 'Meddig van nyitva',
    'opens'        => 'Mikor nyit',
    'opens_day'    => 'Mikor nyit (másik nap)',
    'about_title'  => 'Bemutatkozás címe',
    'about_text'   => 'Bemutatkozás szövege',
    'buffet_tag'   => 'Büfé – kis felirat',
    'buffet_title' => 'Büfé – cím',
    'buffet_text'  => 'Büfé – szöveg',
    'buffet_btn'   => 'Büfé – gomb felirata',
    'motel_tag'    => 'Szálláshely – kis felirat',
    'motel_title'  => 'Szálláshely – cím',
    'motel_text'   => 'Szálláshely – szöveg',
    'motel_btn'    => 'Szálláshely – gomb felirata',
    'terrace_tag'  => 'Terasz – kis felirat',
    'terrace_title' => 'Terasz – cím',
    'terrace_text' => 'Terasz – szöveg',
    'reviews'      => 'Vélemények felirat',
    'reviews_link' => 'Vélemények link felirata',
    'score_google' => 'Google értékelés felirata',
    'score_fb'     => 'Facebook értékelés felirata',
    'fb_text'      => 'Facebook doboz szövege',
    'fb_load'      => 'Facebook: betöltés felirat',
    'fb_open'      => 'Facebook: megnyitás gomb',
    'more_fb'      => 'Több bejegyzés gomb',
    'all'          => '„Összes” felirat',
    'tab_food'     => 'Ételek fül felirata',
    'tab_drinks'   => 'Italok fül felirata',
    'rooms_title'  => 'Szobák – cím',
    'rooms_text1'  => 'Szobák – első bekezdés',
    'rooms_text2'  => 'Szobák – második bekezdés',
    'prices_title' => 'Árak – cím',
    'prices_lead'  => 'Árak – bevezető',
    'prices_note'  => 'Árak – megjegyzés',
    'col_guests'   => 'Ártáblázat: vendégek oszlop',
    'col_no_bf'    => 'Ártáblázat: reggeli nélkül oszlop',
    'col_bf'       => 'Ártáblázat: reggelivel oszlop',
    'guests_unit'  => 'Vendégek mértékegysége (fő)',
    'data_title'   => 'Elérhetőségek – cím',
    'hours_title'  => 'Nyitvatartás – cím',
    'hours_note'   => 'Nyitvatartás – megjegyzés',
    'close'        => 'Bezárás gomb',
    'prev'         => 'Előző gomb',
    'next'         => 'Következő gomb',
    'email'        => 'E-mail mező felirata',
    'arrival'      => 'Érkezés mező felirata',
    'nights'       => 'Éjszakák mező felirata',
    'guests'       => 'Vendégek mező felirata',
    'breakfast'    => 'Reggeli mező felirata',
    'message'      => 'Üzenet mező felirata',
    'message_ph'   => 'Üzenet mező segédszövege',
    'submit'       => 'Küldés gomb',
    'sending'      => 'Küldés közbeni felirat',
    'estimate'     => 'Becsült ár felirata',
    'estimate_na'  => 'Becsült ár – ha nincs adat',
    'success'      => 'Sikeres küldés üzenete',
    'error'        => 'Hibaüzenet',
    'required'     => 'Kötelező mező üzenete',
    'bad_email'    => 'Hibás e-mail üzenete',
    'bad_date'     => 'Hibás dátum üzenete',
    'privacy'      => 'Adatkezelési megjegyzés',
];

/** Teljes útvonalra szóló feliratok (ahol a kulcs neve önmagában félrevezető). */
$pathLabels = [
    'nav.home'      => 'Kezdőlap menüpont',
    'nav.menu'      => 'Étlap menüpont',
    'nav.motel'     => 'Szálláshely menüpont',
    'nav.events'    => 'Események menüpont',
    'nav.gallery'   => 'Galéria menüpont',
    'nav.contact'   => 'Kapcsolat menüpont',
    'nav.book'      => 'Foglalás gomb a menüben',
    'nav.open'      => 'Menü gomb felirata (mobilon)',
    'menu.allergen' => 'Allergén tájékoztató az étlap alján',
];

/** Felsorolások megnevezése (ahol a lista elemei sima szövegek). */
$containerLabels = [
    'home.badges'       => 'Címkék a főcím alatt',
    'home.buffet_list'  => 'Büfé – felsorolás',
    'home.motel_list'   => 'Szálláshely – felsorolás',
    'home.quotes'       => 'Vendégvélemények',
    'motel.features'    => 'Szolgáltatások listája',
    'contact.route_list' => 'Útvonal lépései',
];

/** Galéria: kulcs → fájlnév, hogy látszódjon, melyik képhez tartozik a felirat. */
$galleryFiles = [];
foreach ($CFG['gallery'] ?? [] as $g) {
    if (isset($g['key'], $g['file'])) {
        $galleryFiles[$g['key']] = $g['file'];
    }
}

/** Konténer-kulcsok, amiket nem írunk ki külön morzsaként (a listaelemnek saját neve van). */
$skipLabels = ['groups', 'items', 'list', 'rows', 'stats', 'shots', 'cards', 'steps', 'faq', 'links'];

$dict = [
    'leaf'    => $leafLabels,
    'path'    => $pathLabels,
    'cont'    => $containerLabels,
    'section' => $sectionNames,
    'skip'    => $skipLabels,
    'gallery' => $galleryFiles,
];

function human_key(string $k, array $labels): string
{
    return $labels[$k] ?? ucfirst(str_replace('_', ' ', $k));
}

/**
 * Egy pont-útvonalból emberi morzsákat csinál:
 *   menu.groups.0.items.2.name → ['Étlap és árak', 'Pizzák', 'Hawaii', 'Név']
 *   home.badges.1             → ['Kezdőlap', 'Címkék a főcím alatt', 'Hazai ízek']
 */
function human_crumbs(array $base, string $path, array $d): array
{
    $segs   = explode('.', $path);
    $crumbs = [];
    $node   = $base;
    $walked = '';

    foreach ($segs as $i => $s) {
        $node   = (is_array($node) && array_key_exists($s, $node)) ? $node[$s] : null;
        $walked = $walked === '' ? $s : $walked . '.' . $s;
        $next   = $segs[$i + 1] ?? null;

        // sorszám: a tétel saját neve vagy maga a szöveg legyen a felirat
        if (ctype_digit($s)) {
            if (is_array($node)) {
                $name = null;
                foreach (['title', 'name', 'label', 'q'] as $k) {
                    if (isset($node[$k]) && is_string($node[$k]) && $node[$k] !== '') {
                        $name = $node[$k];
                        break;
                    }
                }
                $crumbs[] = $name ?? ((int) $s + 1) . '.';
            } else {
                $txt      = is_string($node) ? trim($node) : '';
                $crumbs[] = $txt === '' ? ((int) $s + 1) . '.' : mb_strimwidth($txt, 0, 46, '…');
            }
            continue;
        }

        if (isset($d['path'][$walked])) {               // útvonalra szabott felirat
            $crumbs[] = $d['path'][$walked];
            continue;
        }

        if (isset($d['cont'][$walked])) {               // megnevezett felsorolás
            $crumbs[] = $d['cont'][$walked];
            continue;
        }

        if ($next !== null && ctype_digit($next)) {      // lista következik
            $childIsObject = is_array($node) && isset($node[$next]) && is_array($node[$next]);
            if ($childIsObject && in_array($s, $d['skip'], true)) {
                continue;                                // „Pizzák” önmagában elég
            }
            $crumbs[] = $d['cont'][$walked] ?? human_key($s, $d['leaf']);
            continue;
        }

        if ($i === 0) {
            $crumbs[] = $d['section'][$s] ?? human_key($s, $d['leaf']);
            continue;
        }

        if (isset($d['gallery'][$s]) || str_starts_with($s, 'g_')) {   // galéria képaláírás
            $crumbs[] = 'Képaláírás — ' . ($d['gallery'][$s] ?? str_replace('_', ' ', substr($s, 2)));
            continue;
        }

        $crumbs[] = human_key($s, $d['leaf']);
    }

    return $crumbs;
}

/* Csoportosítás: szakasz → azon belül a közös szülő (egy étlaptétel, egy szoba stb.) */
$sections = [];
foreach ($flat as $path => $v) {
    $top    = explode('.', $path)[0];
    $dot    = strrpos($path, '.');
    $parent = $dot === false ? $path : substr($path, 0, $dot);
    $sections[$top][$parent][$path] = $v;
}

$editedTotal = count($overrides);
?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex,nofollow">
<title>Szerkesztés — Mississippi Büfé &amp; Missouri Szálláshely</title>
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

  .price-table { width: 100%; border-collapse: collapse; }
  .price-table th, .price-table td { padding: 8px 10px; text-align: left; vertical-align: middle; }
  .price-table thead th { font-size: .85rem; color: var(--ink-faint); font-weight: 600; }
  .price-table tbody th { font-weight: 600; white-space: nowrap; }
  .price-table input { width: 130px; padding: 10px 12px; border: 1px solid var(--line);
                       border-radius: var(--r-sm); background: var(--surface-2); color: var(--ink); font: inherit; }
  .price-table tr + tr th, .price-table tr + tr td { border-top: 1px solid var(--line-soft); }

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
  .edit-sec small { color: var(--ink-faint); font-weight: 400; font-family: var(--sans, inherit); font-size: .85rem; }
  .edit-body { padding: 4px 22px 22px; }
  .edit-body > .hint { color: var(--ink-soft); font-size: .9rem; margin: 0 0 16px; }

  .edit-group { border-top: 1px solid var(--line-soft); padding-top: 16px; margin-top: 16px; }
  .edit-group:first-child { border-top: 0; padding-top: 0; margin-top: 0; }
  .edit-group h3 { font-size: .95rem; margin: 0 0 12px; color: var(--ink-soft); font-family: inherit; }
  .edit-group h3 span { color: var(--ink-faint); font-weight: 400; }

  .edit-rows { display: grid; gap: 14px; }
  .edit-row label { font-size: .87rem; font-weight: 600; color: var(--ink-soft);
                    display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
  .edit-row input, .edit-row textarea { width: 100%; padding: 11px 14px; border: 1px solid var(--line);
                    border-radius: var(--r-sm); background: var(--surface-2); color: var(--ink); font: inherit; }
  .edit-row textarea { min-height: 84px; resize: vertical; line-height: 1.5; }
  .edit-row input:focus, .edit-row textarea:focus { outline: 2px solid var(--amber); border-color: transparent; }
  .edit-row.is-edited input, .edit-row.is-edited textarea { border-color: var(--amber); background: rgba(224, 163, 85, .06); }
  .tag-edited { font-weight: 600; color: var(--amber-600); font-size: .78rem;
                background: rgba(224, 163, 85, .16); padding: 2px 8px; border-radius: 999px; }
  .btn-reset { border: 1px solid var(--line); background: transparent; color: var(--ink-soft);
               font: inherit; font-size: .78rem; padding: 2px 10px; border-radius: 999px; cursor: pointer; }
  .btn-reset:hover { border-color: var(--amber); color: var(--amber-600); }
  .orig { font-size: .82rem; color: var(--ink-faint); margin: 0; }

  .savebar { position: sticky; bottom: 0; z-index: 3; margin-top: 18px;
             background: color-mix(in srgb, var(--bg-alt) 92%, transparent);
             backdrop-filter: blur(8px); border-top: 1px solid var(--line);
             padding: 14px 0; display: flex; gap: 14px; align-items: center; justify-content: flex-end; }
  .savebar .note { margin: 0; margin-right: auto; color: var(--ink-faint); font-size: .88rem; }

  .edit-foot { border-top: 1px solid var(--line); padding: 22px 0 40px; color: var(--ink-faint); font-size: .88rem; }
  @media (max-width: 640px) {
    .edit-panel { padding: 20px 18px; }
    .edit-body { padding: 4px 16px 18px; }
    .price-table input { width: 100%; min-width: 92px; }
  }
</style>
</head>
<body class="page-admin">

<a class="skip-link" href="#main">Ugrás a szerkesztéshez</a>

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
        <div class="lang-switch" role="group" aria-label="Szerkesztett nyelv">
          <?php foreach ($langs as $code => $label): ?>
          <a href="index.php?lang=<?= h($code) ?>" class="lang<?= $code === $lang ? ' is-active' : '' ?>" title="<?= h($label) ?>"><?= h(strtoupper($code)) ?></a>
          <?php endforeach; ?>
        </div>
        <a class="btn btn--primary btn--sm" href="logout.php">Kilépés</a>
      </div>
    </nav>
  </div>
</header>

<main id="main" class="edit-main">
  <div class="wrap">

    <div class="edit-head">
      <p class="eyebrow">Szerkesztés</p>
      <h1>Az oldal szövegei és árai</h1>
      <p class="lead">
        Írd át a mezőt, aztán kattints a mentésre. Az oldalon azonnal az új szöveg jelenik meg.
        Elrontani nem lehet: minden mező mellett ott a <em>Vissza az eredetire</em> gomb.
      </p>
    </div>

    <?php if ($msg): ?>
    <div class="edit-msg <?= $msgOk ? 'is-ok' : 'is-bad' ?>"><?= h($msg) ?></div>
    <?php endif; ?>

    <!-- Szobaárak: nyelvtől független -->
    <section class="edit-panel">
      <h2>Szobaárak</h2>
      <p class="hint">Egy éjszakára, forintban. Mindhárom nyelven ugyanez jelenik meg.</p>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= h(admin_csrf()) ?>">
        <input type="hidden" name="which" value="site">
        <table class="price-table">
          <thead>
            <tr><th>Hányan alszanak</th><th>Reggeli nélkül</th><th>Reggelivel</th></tr>
          </thead>
          <tbody>
          <?php foreach ($CFG['room_prices'] as $g => [$plain, $bf]): ?>
            <tr>
              <th scope="row"><?= (int) $g ?> fő</th>
              <td><input type="number" name="rp[<?= (int) $g ?>][0]" value="<?= (int) $plain ?>" min="0" step="100" aria-label="<?= (int) $g ?> fő, reggeli nélkül"></td>
              <td><input type="number" name="rp[<?= (int) $g ?>][1]" value="<?= (int) $bf ?>" min="0" step="100" aria-label="<?= (int) $g ?> fő, reggelivel"></td>
            </tr>
          <?php endforeach; ?>
            <tr>
              <th scope="row">Reggeli egy főre</th>
              <td colspan="2"><input type="number" name="bpp" value="<?= (int) $CFG['breakfast_per_person'] ?>" min="0" step="50" aria-label="Reggeli ára egy főre"></td>
            </tr>
          </tbody>
        </table>
        <div class="savebar" style="position: static; border: 0; background: none; padding: 16px 0 0;">
          <button class="btn btn--primary" type="submit">Szobaárak mentése</button>
        </div>
      </form>
    </section>

    <!-- Szövegek -->
    <div class="edit-tools">
      <input type="search" id="q" class="edit-search" placeholder="Keresés a szövegek közt — például: pizza, reggeli, kutya">
      <span class="edit-count">
        Szerkesztett nyelv: <strong><?= h($langs[$lang]) ?></strong>
        <?= $editedTotal ? ' · ' . $editedTotal . ' mező tér el az eredetitől' : '' ?>
      </span>
    </div>

    <form method="post" id="textsForm">
      <input type="hidden" name="csrf" value="<?= h(admin_csrf()) ?>">
      <input type="hidden" name="which" value="texts">

      <?php foreach ($sections as $top => $groups):
          $fieldCount = 0;
          $editedHere = 0;
          foreach ($groups as $items) {
              foreach ($items as $p => $v) {
                  $fieldCount++;
                  if (array_key_exists($p, $overrides)) {
                      $editedHere++;
                  }
              }
          } ?>
      <details class="edit-sec"<?= $top === 'menu' ? ' open' : '' ?>>
        <summary>
          <?= h($sectionNames[$top] ?? ucfirst($top)) ?>
          <small><?= $fieldCount ?> mező<?= $editedHere ? ' · ' . $editedHere . ' módosítva' : '' ?></small>
        </summary>
        <div class="edit-body">
          <?php if (isset($sectionHints[$top])): ?>
          <p class="hint"><?= h($sectionHints[$top]) ?></p>
          <?php endif; ?>

          <?php foreach ($groups as $parent => $items):
              $crumbs = human_crumbs($base, $parent, $dict);
              array_shift($crumbs);                    // a szakasz neve már a fejlécben van
              $heading = implode(' › ', $crumbs); ?>
          <div class="edit-group">
            <?php if ($heading !== ''): ?>
            <h3><?= h($heading) ?></h3>
            <?php endif; ?>
            <div class="edit-rows">
              <?php foreach ($items as $path => $baseVal):
                  $cur     = array_key_exists($path, $overrides) ? $overrides[$path] : $baseVal;
                  $edited  = array_key_exists($path, $overrides);
                  $all     = human_crumbs($base, $path, $dict);
                  $label   = end($all);
                  $isNum   = is_int($baseVal);
                  $long    = !$isNum && (mb_strlen((string) $baseVal) > 70 || str_contains((string) $baseVal, "\n"));
                  $id      = 'f-' . md5($path);
                  $search  = mb_strtolower($label . ' ' . $heading . ' ' . (string) $cur . ' ' . (string) $baseVal); ?>
              <div class="edit-row<?= $edited ? ' is-edited' : '' ?>" data-search="<?= h($search) ?>">
                <label for="<?= h($id) ?>">
                  <?= h($label) ?>
                  <?php if ($edited): ?><span class="tag-edited">módosítva</span><?php endif; ?>
                  <button class="btn-reset" type="button" data-reset="<?= h($id) ?>" data-base="<?= h((string) $baseVal) ?>">Vissza az eredetire</button>
                </label>
                <?php if ($isNum): ?>
                <input type="number" id="<?= h($id) ?>" name="f[<?= h($path) ?>]" value="<?= (int) $cur ?>" min="0" data-base="<?= (int) $baseVal ?>">
                <?php elseif ($long): ?>
                <textarea id="<?= h($id) ?>" name="f[<?= h($path) ?>]" rows="<?= min(8, max(3, (int) ceil(mb_strlen((string) $cur) / 80))) ?>" data-base="<?= h((string) $baseVal) ?>"><?= h((string) $cur) ?></textarea>
                <?php else: ?>
                <input type="text" id="<?= h($id) ?>" name="f[<?= h($path) ?>]" value="<?= h((string) $cur) ?>" data-base="<?= h((string) $baseVal) ?>">
                <?php endif; ?>
                <?php if ($edited): ?>
                <p class="orig">Eredeti: <?= h((string) $baseVal) ?></p>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </details>
      <?php endforeach; ?>

      <div class="savebar">
        <p class="note">A mentés az összes fenti szöveget elmenti ezen a nyelven.</p>
        <button class="btn btn--primary" type="submit">Szövegek mentése (<?= h($langs[$lang]) ?>)</button>
      </div>
    </form>

    <p class="edit-foot">
      Az eredeti szövegek érintetlenek maradnak. Ha egy mezőt visszaírsz az eredetire,
      a módosítás magától törlődik. A fotókat és a képaláírásokat nem itt, hanem a képek
      mappájában és a galéria beállításai közt lehet cserélni.
    </p>

  </div>
</main>

<script src="../assets/app.js?v=8" defer></script>
<script>
(function () {
  // keresés a mezők közt
  var q = document.getElementById('q');
  if (q) {
    q.addEventListener('input', function () {
      var s = q.value.trim().toLowerCase();
      document.querySelectorAll('.edit-sec').forEach(function (sec) {
        var hits = 0;
        sec.querySelectorAll('.edit-row').forEach(function (row) {
          var show = !s || (row.dataset.search || '').indexOf(s) !== -1;
          row.hidden = !show;
          if (show) hits++;
        });
        sec.querySelectorAll('.edit-group').forEach(function (g) {
          g.hidden = !g.querySelector('.edit-row:not([hidden])');
        });
        sec.hidden = !!s && hits === 0;
        if (s && hits) sec.open = true;
      });
    });
  }

  // vissza az eredetire
  document.querySelectorAll('.btn-reset').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var f = document.getElementById(btn.dataset.reset);
      if (!f) return;
      f.value = btn.dataset.base;
      f.dispatchEvent(new Event('input', { bubbles: true }));
      f.focus();
    });
  });

  // jelöljük, ha a mező eltér az eredetitől
  document.querySelectorAll('#textsForm [data-base]').forEach(function (el) {
    if (el.tagName === 'BUTTON') return;
    el.addEventListener('input', function () {
      var row = el.closest('.edit-row');
      if (row) row.classList.toggle('is-edited', el.value !== el.dataset.base);
    });
  });

  // figyelmeztetés mentetlen változásnál
  var form = document.getElementById('textsForm');
  var dirty = false;
  if (form) {
    form.addEventListener('input', function () { dirty = true; });
    form.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (e) {
      if (!dirty) return;
      e.preventDefault();
      e.returnValue = '';
    });
  }
})();
</script>
</body>
</html>
