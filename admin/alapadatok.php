<?php
/**
 * Alapadatok: nyitvatartás, elérhetőség, szobaárak.
 * Minden a data/site.json fájlba kerül; az inc/config.php ebből olvassa be
 * a felülírásokat. Az eredeti (inc/config.php-beli) értékek megmaradnak.
 */
require __DIR__ . '/_auth.php';
admin_require();

$ROOT     = dirname(__DIR__);
$siteFile = ADMIN_DATA . '/site.json';
$site     = is_file($siteFile)
    ? (json_decode(preg_replace('/^\xEF\xBB\xBF/', '', (string) file_get_contents($siteFile)), true) ?: [])
    : [];

$dayNames = ['Vasárnap', 'Hétfő', 'Kedd', 'Szerda', 'Csütörtök', 'Péntek', 'Szombat'];
$dayOrder = [1, 2, 3, 4, 5, 6, 0];

$msg   = '';
$msgOk = true;

function site_save(string $file, array $site): bool
{
    if (!is_dir(ADMIN_DATA)) {
        mkdir(ADMIN_DATA, 0775, true);
    }
    return file_put_contents(
        $file,
        json_encode($site, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    ) !== false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_check();
    $which = (string) ($_POST['which'] ?? '');

    // ---- Elérhetőség ----
    if ($which === 'contact') {
        $errors = [];
        $fields = ['phone', 'mobile', 'mail_to', 'facebook', 'zip', 'city', 'street'];
        foreach ($fields as $k) {
            $v = trim((string) ($_POST[$k] ?? ''));
            if ($v === '') {
                unset($site[$k]);                     // üres → vissza az eredetire
                continue;
            }
            if ($k === 'mail_to' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Az e-mail cím nem tűnik érvényesnek: ' . $v;
                continue;
            }
            if ($k === 'facebook' && !preg_match('#^https://(www\.)?facebook\.com/#i', $v)) {
                $errors[] = 'A Facebook-link https://www.facebook.com/… alakú legyen.';
                continue;
            }
            if (in_array($k, ['phone', 'mobile'], true) && strlen(preg_replace('/\D/', '', $v)) < 8) {
                $errors[] = 'A telefonszám túl rövid: ' . $v;
                continue;
            }
            $site[$k] = $v;
        }
        if ($errors) {
            $msgOk = false;
            $msg   = implode(' ', $errors);
        } else {
            $msgOk = site_save($siteFile, $site);
            $msg   = $msgOk ? 'Elérhetőségek elmentve. A fejléc, a lábléc és a kapcsolat oldal már az újat mutatja.'
                            : 'Nem sikerült menteni. A szerveren a data mappa nem írható.';
        }
    }

    // ---- Nyitvatartás ----
    if ($which === 'hours') {
        $hours  = [];
        $errors = [];
        foreach ($dayOrder as $d) {
            if (!empty($_POST['closed'][$d])) {
                continue;                             // aznap zárva
            }
            $o = trim((string) ($_POST['open'][$d] ?? ''));
            $c = trim((string) ($_POST['close'][$d] ?? ''));
            if (!preg_match('/^\d{1,2}:\d{2}$/', $o) || !preg_match('/^\d{1,2}:\d{2}$/', $c)) {
                $errors[] = $dayNames[$d] . ': add meg a nyitást és a zárást is (vagy jelöld zárva napnak).';
                continue;
            }
            if (strcmp(sprintf('%05s', $o), sprintf('%05s', $c)) >= 0) {
                $errors[] = $dayNames[$d] . ': a zárás legyen a nyitás után.';
                continue;
            }
            $hours[$d] = [$o, $c];
        }
        if (!$errors && !$hours) {
            $errors[] = 'Legalább egy napon nyitva kell lenni.';
        }
        if ($errors) {
            $msgOk = false;
            $msg   = implode(' ', $errors);
        } else {
            $site['hours'] = $hours;
            $msgOk = site_save($siteFile, $site);
            $msg   = $msgOk ? 'Nyitvatartás elmentve. A „nyitva / zárva” jelző is ebből számol.'
                            : 'Nem sikerült menteni. A szerveren a data mappa nem írható.';
        }
    }

    // ---- Szobaárak ----
    if ($which === 'prices') {
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
        $msgOk = site_save($siteFile, $site);
        $msg   = $msgOk ? 'Szobaárak elmentve.' : 'Nem sikerült menteni. A szerveren a data mappa nem írható.';
    }

    // ---- Vissza az eredetire (egy kártya) ----
    if ($which === 'reset') {
        $what = (string) ($_POST['what'] ?? '');
        $keys = match ($what) {
            'contact' => ['phone', 'mobile', 'mail_to', 'facebook', 'zip', 'city', 'street'],
            'hours'   => ['hours'],
            'prices'  => ['room_prices', 'breakfast_per_person'],
            default   => [],
        };
        foreach ($keys as $k) {
            unset($site[$k]);
        }
        $msgOk = site_save($siteFile, $site);
        $msg   = $msgOk ? 'Visszaállítva az eredeti adatokra.' : 'Nem sikerült menteni.';
    }
}

$CFG = require $ROOT . '/inc/config.php';             // már a mentett adatokkal

$changed = fn(string ...$keys) => (bool) array_filter($keys, fn($k) => array_key_exists($k, $site));

$adminTitle = 'Nyitvatartás, elérhetőség, árak';
$adminTab   = 'basics';
require __DIR__ . '/_head.php';
?>

    <div class="edit-head">
      <p class="eyebrow">Szerkesztés</p>
      <h1>Nyitvatartás, elérhetőség, árak</h1>
      <p class="lead">
        Ezek az adatok az egész oldalon egyszerre változnak: fejléc, lábléc, kapcsolat oldal,
        Google-cégadat. Kártyánként külön lehet menteni.
      </p>
    </div>

    <?php if ($msg): ?>
    <div class="edit-msg <?= $msgOk ? 'is-ok' : 'is-bad' ?>"><?= h($msg) ?></div>
    <?php endif; ?>

    <!-- Nyitvatartás -->
    <section class="edit-panel">
      <h2>Nyitvatartás <?php if ($changed('hours')): ?><span class="tag-edited">módosítva</span><?php endif; ?></h2>
      <p class="hint">A „nyitva / zárva” jelző, a lábléc és a Google is ebből dolgozik. Ha egy nap zárva vagytok, pipáld be.</p>
      <form method="post" id="hoursForm">
        <input type="hidden" name="csrf" value="<?= h(admin_csrf()) ?>">
        <input type="hidden" name="which" value="hours">
        <table class="hours-table">
          <thead>
            <tr><th>Nap</th><th>Nyitás</th><th>Zárás</th><th></th></tr>
          </thead>
          <tbody>
          <?php foreach ($dayOrder as $d):
              $h = $CFG['hours'][$d] ?? null; ?>
            <tr class="<?= $h ? '' : 'is-closed' ?>">
              <th scope="row"><?= h($dayNames[$d]) ?></th>
              <td><input type="time" name="open[<?= $d ?>]" value="<?= h($h[0] ?? '10:00') ?>" step="300" aria-label="<?= h($dayNames[$d]) ?> nyitás"></td>
              <td><input type="time" name="close[<?= $d ?>]" value="<?= h($h[1] ?? '22:00') ?>" step="300" aria-label="<?= h($dayNames[$d]) ?> zárás"></td>
              <td><label class="closed"><input type="checkbox" name="closed[<?= $d ?>]" value="1"<?= $h ? '' : ' checked' ?>> zárva</label></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <div class="savebar savebar--inline">
          <?php if ($changed('hours')): ?>
          <button class="btn btn--quiet btn--sm" type="submit" form="resetHours">Vissza az eredetire</button>
          <?php endif; ?>
          <button class="btn btn--primary" type="submit">Nyitvatartás mentése</button>
        </div>
      </form>
      <form method="post" id="resetHours" hidden>
        <input type="hidden" name="csrf" value="<?= h(admin_csrf()) ?>">
        <input type="hidden" name="which" value="reset"><input type="hidden" name="what" value="hours">
      </form>
    </section>

    <!-- Elérhetőség -->
    <section class="edit-panel">
      <h2>Elérhetőség <?php if ($changed('phone', 'mobile', 'mail_to', 'facebook', 'zip', 'city', 'street')): ?><span class="tag-edited">módosítva</span><?php endif; ?></h2>
      <p class="hint">Ha egy mezőt üresen hagysz, az eredeti érték marad.</p>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= h(admin_csrf()) ?>">
        <input type="hidden" name="which" value="contact">
        <div class="basics-grid">
          <div class="edit-row">
            <label for="phone">Vezetékes telefon</label>
            <input type="tel" id="phone" name="phone" value="<?= h($CFG['phone']) ?>" placeholder="+36 94 950 348">
            <span class="sub">Így jelenik meg; a hívógomb magától tudja tárcsázni.</span>
          </div>
          <div class="edit-row">
            <label for="mobile">Mobil</label>
            <input type="tel" id="mobile" name="mobile" value="<?= h($CFG['mobile']) ?>" placeholder="+36 30 693 4875">
          </div>
          <div class="edit-row">
            <label for="mail_to">Foglalások e-mail címe</label>
            <input type="email" id="mail_to" name="mail_to" value="<?= h($CFG['mail_to']) ?>">
            <span class="sub">Ide érkeznek az űrlapról a foglalási kérések.</span>
          </div>
          <div class="edit-row">
            <label for="facebook">Facebook-oldal címe</label>
            <input type="url" id="facebook" name="facebook" value="<?= h($CFG['facebook']) ?>">
          </div>
          <div class="edit-row">
            <label for="zip">Irányítószám</label>
            <input type="text" id="zip" name="zip" value="<?= h($CFG['zip']) ?>" inputmode="numeric">
          </div>
          <div class="edit-row">
            <label for="city">Település</label>
            <input type="text" id="city" name="city" value="<?= h($CFG['city']) ?>">
          </div>
          <div class="edit-row">
            <label for="street">Utca, házszám</label>
            <input type="text" id="street" name="street" value="<?= h($CFG['street']) ?>">
          </div>
        </div>
        <div class="savebar savebar--inline">
          <?php if ($changed('phone', 'mobile', 'mail_to', 'facebook', 'zip', 'city', 'street')): ?>
          <button class="btn btn--quiet btn--sm" type="submit" form="resetContact">Vissza az eredetire</button>
          <?php endif; ?>
          <button class="btn btn--primary" type="submit">Elérhetőség mentése</button>
        </div>
      </form>
      <form method="post" id="resetContact" hidden>
        <input type="hidden" name="csrf" value="<?= h(admin_csrf()) ?>">
        <input type="hidden" name="which" value="reset"><input type="hidden" name="what" value="contact">
      </form>
    </section>

    <!-- Szobaárak -->
    <section class="edit-panel">
      <h2>Szobaárak <?php if ($changed('room_prices', 'breakfast_per_person')): ?><span class="tag-edited">módosítva</span><?php endif; ?></h2>
      <p class="hint">Egy éjszakára, forintban. Mindhárom nyelven ugyanez jelenik meg.</p>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= h(admin_csrf()) ?>">
        <input type="hidden" name="which" value="prices">
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
        <div class="savebar savebar--inline">
          <?php if ($changed('room_prices', 'breakfast_per_person')): ?>
          <button class="btn btn--quiet btn--sm" type="submit" form="resetPrices">Vissza az eredetire</button>
          <?php endif; ?>
          <button class="btn btn--primary" type="submit">Szobaárak mentése</button>
        </div>
      </form>
      <form method="post" id="resetPrices" hidden>
        <input type="hidden" name="csrf" value="<?= h(admin_csrf()) ?>">
        <input type="hidden" name="which" value="reset"><input type="hidden" name="what" value="prices">
      </form>
    </section>

<?php
$adminScript = <<<'JS'
(function () {
  // zárva pipa → az időmezők halványulnak
  document.querySelectorAll('.hours-table input[type=checkbox]').forEach(function (cb) {
    cb.addEventListener('change', function () {
      cb.closest('tr').classList.toggle('is-closed', cb.checked);
    });
  });
})();
JS;
require __DIR__ . '/_foot.php';
