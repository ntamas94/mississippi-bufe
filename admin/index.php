<?php
/**
 * Szövegszerkesztő — az oldal minden felirata, hétköznapi megnevezésekkel.
 *
 * A lang/*.php szövegei átírhatók böngészőből. A módosítások a
 * data/overrides.<nyelv>.json fájlba kerülnek, az eredeti fájlokhoz nem
 * nyúlunk — így bármikor vissza lehet térni az alapszöveghez.
 *
 * Nyitvatartás, elérhetőség, szobaárak: alapadatok.php · Képek: kepek.php
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

$CFG = require $ROOT . '/inc/config.php';

// Az adminból feltöltött galériaképek felirata nincs az alap nyelvi fájlban —
// ezeket üres alapértékkel vesszük fel, hogy itt is szerkeszthetők legyenek.
$extraKeys = [];
foreach ($CFG['gallery'] as $g) {
    $p = 'gallery.' . $g['key'];
    if (!array_key_exists($p, $flat)) {
        $flat[$p]       = '';
        $extraKeys[$p]  = true;
    }
}

$ovFile    = ADMIN_DATA . '/overrides.' . $lang . '.json';
$overrides = is_file($ovFile) ? (json_decode((string) file_get_contents($ovFile), true) ?: []) : [];
$msg       = '';
$msgOk     = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_check();
    if (!is_dir(ADMIN_DATA)) {
        mkdir(ADMIN_DATA, 0775, true);
    }

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
            } elseif ($val === $baseVal && !isset($extraKeys[$path])) {
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
}

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
    'motel'   => 'A szálláshely oldal szövegei: szobák, szolgáltatások, árakhoz tartozó megjegyzések. Maguk az árak a „Nyitvatartás, elérhetőség, árak” fülön vannak.',
    'gallery' => 'A képek felirata. Képet cserélni vagy újat feltölteni a „Képek” fülön lehet.',
    'contact' => 'A kapcsolat oldal szövegei. A telefonszám, cím és nyitvatartás a „Nyitvatartás, elérhetőség, árak” fülön módosítható.',
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
    'home.badges'        => 'Címkék a főcím alatt',
    'home.buffet_list'   => 'Büfé – felsorolás',
    'home.motel_list'    => 'Szálláshely – felsorolás',
    'home.quotes'        => 'Vendégvélemények',
    'motel.features'     => 'Szolgáltatások listája',
    'contact.route_list' => 'Útvonal lépései',
];

/** Galéria: kulcs → fájlnév, hogy látszódjon, melyik képhez tartozik a felirat. */
$galleryFiles = [];
foreach ($CFG['gallery'] as $g) {
    $galleryFiles[$g['key']] = $g['file'];
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

$adminTitle = 'Szövegek';
$adminTab   = 'texts';
require __DIR__ . '/_head.php';
?>

    <div class="edit-head">
      <p class="eyebrow">Szerkesztés</p>
      <h1>Az oldal szövegei</h1>
      <p class="lead">
        Írd át a mezőt, aztán kattints a mentésre. Az oldalon azonnal az új szöveg jelenik meg.
        Elrontani nem lehet: minden mező mellett ott a <em>Vissza az eredetire</em> gomb.
      </p>
    </div>

    <?php if ($msg): ?>
    <div class="edit-msg <?= $msgOk ? 'is-ok' : 'is-bad' ?>"><?= h($msg) ?></div>
    <?php endif; ?>

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
                  $edited  = array_key_exists($path, $overrides) && !isset($extraKeys[$path]);
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
                  <?php if (!isset($extraKeys[$path])): ?>
                  <button class="btn-reset" type="button" data-reset="<?= h($id) ?>" data-base="<?= h((string) $baseVal) ?>">Vissza az eredetire</button>
                  <?php endif; ?>
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
      a módosítás magától törlődik.
    </p>

<?php
$adminScript = <<<'JS'
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
JS;
require __DIR__ . '/_foot.php';
