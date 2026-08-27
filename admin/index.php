<?php
/**
 * Szövegszerkesztő: a lang/*.php fájlok minden szövege és ára átírható.
 * A módosítások a data/overrides.<nyelv>.json fájlba kerülnek, az eredeti
 * fájlokhoz nem nyúlunk — így bármikor vissza lehet térni az alapszöveghez
 * (elég a mezőt visszaírni az eredetire).
 * A szobaárak (inc/config.php) a data/site.json-ba mentődnek.
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
        $msg = $ok === false
            ? 'HIBA: nem sikerült írni a data/ mappába!'
            : 'Szövegek elmentve (' . $langs[$lang] . ') — ' . count($new) . ' mező tér el az alapszövegtől.';
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
        $ok = file_put_contents(
            $siteFile,
            json_encode($site, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
        $msg = $ok === false ? 'HIBA: nem sikerült írni a data/ mappába!' : 'Szobaárak elmentve.';
    }
}

$CFG = require $ROOT . '/inc/config.php';              // már a mentett árakkal

$sectionNames = [
    'nav'        => 'Menüsor',
    'days'       => 'Napok',
    'days_short' => 'Napok (rövid)',
    'status'     => 'Nyitva-jelző',
    'common'     => 'Általános',
    'home'       => 'Kezdőlap',
    'menu'       => 'Étlap és árak',
    'motel'      => 'Szálláshely',
    'events'     => 'Események',
    'gallery'    => 'Galéria',
    'contact'    => 'Kapcsolat',
    'booking'    => 'Foglalás',
    'footer'     => 'Lábléc',
];
$sections = [];
foreach ($flat as $path => $v) {
    $sections[explode('.', $path)[0]][$path] = $v;
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Szövegszerkesztő — Mississippi admin</title>
<style>
  :root { color-scheme: dark; }
  * { box-sizing: border-box; }
  body { margin: 0; font-family: system-ui, sans-serif; background: #191512; color: #fbf8f2; }
  header.bar { position: sticky; top: 0; z-index: 5; display: flex; align-items: center; gap: 14px;
               padding: 12px 20px; background: #221d18; border-bottom: 1px solid #3a322a; flex-wrap: wrap; }
  header.bar h1 { font-size: 1rem; margin: 0; }
  .spacer { flex: 1; }
  a { color: #e8a13c; text-decoration: none; }
  a:hover { text-decoration: underline; }
  .tabs { display: flex; gap: 6px; }
  .tabs a { padding: 6px 14px; border-radius: 999px; border: 1px solid #4a4036; color: #d8cfc3; }
  .tabs a.on { background: #e8a13c; color: #211505; border-color: #e8a13c; font-weight: 700; }
  main { max-width: 980px; margin: 0 auto; padding: 22px 20px 90px; }
  .msg { background: #1d2f1d; border: 1px solid #365f36; color: #bfe3b4;
         padding: 10px 14px; border-radius: 8px; margin-bottom: 18px; }
  .hint { color: #b5aa9c; font-size: .85rem; margin: 0 0 20px; }
  details { background: #221d18; border: 1px solid #3a322a; border-radius: 10px; margin-bottom: 12px; }
  summary { cursor: pointer; padding: 12px 16px; font-weight: 700; }
  summary small { color: #b5aa9c; font-weight: 400; margin-left: 8px; }
  .fields { padding: 4px 16px 16px; display: grid; gap: 12px; }
  .field label { display: block; font-size: .75rem; color: #b5aa9c; margin-bottom: 4px;
                 font-family: ui-monospace, monospace; overflow-wrap: anywhere; }
  .field.edited label::after { content: " ● módosítva"; color: #e8a13c; }
  input[type=text], input[type=number], textarea {
    width: 100%; padding: 8px 10px; border-radius: 7px; border: 1px solid #4a4036;
    background: #191512; color: #fbf8f2; font-size: .95rem; font-family: inherit; }
  textarea { resize: vertical; }
  input:focus, textarea:focus { outline: 2px solid #e8a13c; border-color: transparent; }
  .field.edited input, .field.edited textarea { border-color: #a06a1e; }
  .savebar { position: fixed; left: 0; right: 0; bottom: 0; padding: 12px 20px;
             background: rgba(25, 21, 18, .93); border-top: 1px solid #3a322a; text-align: right; }
  button.save { padding: 11px 26px; border: 0; border-radius: 8px; cursor: pointer;
                background: linear-gradient(135deg, #e8a13c, #c97f1e); color: #211505;
                font-weight: 700; font-size: 1rem; }
  table.prices { border-collapse: collapse; margin-top: 6px; }
  table.prices th, table.prices td { padding: 6px 10px; text-align: left; }
  table.prices input { width: 110px; }
  .card { background: #221d18; border: 1px solid #3a322a; border-radius: 10px;
          padding: 16px; margin-bottom: 22px; }
  .card h2 { font-size: 1rem; margin: 0 0 4px; }
  .card .inline-save { margin-top: 12px; text-align: right; }
</style>
</head>
<body>

<header class="bar">
  <h1>Szövegszerkesztő</h1>
  <nav class="tabs">
    <?php foreach ($langs as $code => $label): ?>
    <a href="index.php?lang=<?= h($code) ?>" class="<?= $code === $lang ? 'on' : '' ?>"><?= h($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <span class="spacer"></span>
  <a href="../index.php" target="_blank" rel="noopener">Oldal megnyitása ↗</a>
  <a href="logout.php">Kilépés</a>
</header>

<main>
  <?php if ($msg): ?><div class="msg"><?= h($msg) ?></div><?php endif; ?>
  <p class="hint">
    Írd át a mezőt és mentsd el — az oldal azonnal az új szöveget mutatja.
    Az eredeti fájlokhoz nem nyúlunk: ha egy mezőt visszaírsz az eredetire, a felülírás magától törlődik.
    A ● jel mutatja, mely mezők térnek el az alapszövegtől.
  </p>

  <!-- Szobaárak (nyelvfüggetlen) -->
  <form method="post" class="card">
    <input type="hidden" name="csrf" value="<?= h(admin_csrf()) ?>">
    <input type="hidden" name="which" value="site">
    <h2>Szobaárak (Ft / éj)</h2>
    <table class="prices">
      <tr><th></th><th>Reggeli nélkül</th><th>Reggelivel</th></tr>
      <?php foreach ($CFG['room_prices'] as $g => [$plain, $bf]): ?>
      <tr>
        <th><?= (int) $g ?> fő</th>
        <td><input type="number" name="rp[<?= (int) $g ?>][0]" value="<?= (int) $plain ?>" min="0" step="100"></td>
        <td><input type="number" name="rp[<?= (int) $g ?>][1]" value="<?= (int) $bf ?>" min="0" step="100"></td>
      </tr>
      <?php endforeach; ?>
      <tr>
        <th>Reggeli / fő</th>
        <td colspan="2"><input type="number" name="bpp" value="<?= (int) $CFG['breakfast_per_person'] ?>" min="0" step="50"></td>
      </tr>
    </table>
    <div class="inline-save"><button class="save" type="submit">Szobaárak mentése</button></div>
  </form>

  <!-- Szövegek -->
  <form method="post" id="textsForm">
    <input type="hidden" name="csrf" value="<?= h(admin_csrf()) ?>">
    <input type="hidden" name="which" value="texts">

    <?php foreach ($sections as $top => $items):
        $editedCount = 0;
        foreach ($items as $p => $v) {
            if (array_key_exists($p, $overrides)) $editedCount++;
        } ?>
    <details<?= $top === 'menu' ? ' open' : '' ?>>
      <summary>
        <?= h($sectionNames[$top] ?? $top) ?>
        <small><?= count($items) ?> mező<?= $editedCount ? ' · ' . $editedCount . ' módosítva' : '' ?></small>
      </summary>
      <div class="fields">
        <?php foreach ($items as $path => $baseVal):
            $cur    = array_key_exists($path, $overrides) ? $overrides[$path] : $baseVal;
            $edited = array_key_exists($path, $overrides);
            $label  = substr($path, strlen($top) + 1);
            $isNum  = is_int($baseVal);
            $long   = !$isNum && (mb_strlen((string) $baseVal) > 60 || str_contains((string) $baseVal, "\n")); ?>
        <div class="field<?= $edited ? ' edited' : '' ?>">
          <label for="f-<?= h(md5($path)) ?>"><?= h($label) ?></label>
          <?php if ($isNum): ?>
          <input type="number" id="f-<?= h(md5($path)) ?>" name="f[<?= h($path) ?>]" value="<?= (int) $cur ?>" min="0">
          <?php elseif ($long): ?>
          <textarea id="f-<?= h(md5($path)) ?>" name="f[<?= h($path) ?>]" rows="<?= min(6, max(2, (int) ceil(mb_strlen((string) $cur) / 90))) ?>" title="Eredeti: <?= h((string) $baseVal) ?>"><?= h((string) $cur) ?></textarea>
          <?php else: ?>
          <input type="text" id="f-<?= h(md5($path)) ?>" name="f[<?= h($path) ?>]" value="<?= h((string) $cur) ?>" title="Eredeti: <?= h((string) $baseVal) ?>">
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </details>
    <?php endforeach; ?>

    <div class="savebar">
      <button class="save" type="submit">Szövegek mentése (<?= h($langs[$lang]) ?>)</button>
    </div>
  </form>
</main>

</body>
</html>
