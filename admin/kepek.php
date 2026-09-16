<?php
/**
 * Képek: csere, új galériakép, elrejtés, sorrend, előző változat visszaállítása.
 *
 * A feltöltött kép a böngészőben már kicsinyítve érkezik (JS), a szerver
 * GD-vel véglegesíti (max. 1600 px, JPEG). Az előző változat a
 * data/img-backup/ alá kerül, egy lépés visszavonható.
 * A galéria-módosítások a data/site.json-ba (gallery_extra / gallery_hidden /
 * gallery_order), az új képek felirata a data/overrides.<nyelv>.json-ba kerül.
 */
require __DIR__ . '/_auth.php';
require __DIR__ . '/_img.php';
admin_require();

const CFG_KEEP_HIDDEN = true;                          // a rejtett galériaképeket is listázzuk

$ROOT     = dirname(__DIR__);
$siteFile = ADMIN_DATA . '/site.json';
$site     = is_file($siteFile)
    ? (json_decode(preg_replace('/^\xEF\xBB\xBF/', '', (string) file_get_contents($siteFile)), true) ?: [])
    : [];

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

/** Üzenet a következő oldalbetöltésre, majd átirányítás (frissítés ne töltsön fel újra). */
function flash_redirect(bool $ok, string $msg): never
{
    $_SESSION['admin_flash'] = [$ok, $msg];
    header('Location: kepek.php');
    exit;
}

function overrides_set(string $lang, string $key, ?string $val): void
{
    $f  = ADMIN_DATA . '/overrides.' . $lang . '.json';
    $ov = is_file($f) ? (json_decode((string) file_get_contents($f), true) ?: []) : [];
    if ($val === null) {
        unset($ov[$key]);
    } else {
        $ov[$key] = $val;
    }
    file_put_contents($f, json_encode($ov, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

/* ---------------- Műveletek ---------------- */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // túl nagy feltöltésnél a PHP üresen adja a $_POST-ot → érthető üzenet
    if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        flash_redirect(false, 'Túl nagy volt a feltöltött fájl a szerver beállításaihoz. Próbáld újra — ha nem megy, küldd kisebb képet.');
    }
    admin_csrf_check();

    $CFG    = require $ROOT . '/inc/config.php';
    $action = (string) ($_POST['action'] ?? '');
    $file   = (string) ($_POST['file'] ?? '');
    $key    = (string) ($_POST['key'] ?? '');
    $extras = $site['gallery_extra'] ?? [];
    $hidden = array_values(array_map('strval', $site['gallery_hidden'] ?? []));
    $keys   = array_column($CFG['gallery'], 'key');

    // ---- kép cseréje (azonos fájlnév, az oldal minden helyén frissül) ----
    if ($action === 'replace') {
        if (!img_valid_name($file)) {
            flash_redirect(false, 'Ismeretlen kép.');
        }
        if ($err = img_check_upload($_FILES['photo'] ?? null)) {
            flash_redirect(false, $err);
        }
        img_backup($file);
        if (!img_save_resized($_FILES['photo']['tmp_name'], IMG_DIR . '/' . $file)) {
            flash_redirect(false, 'A képet nem sikerült feldolgozni. Próbáld más formátumban (JPG).');
        }
        flash_redirect(true, 'Kép lecserélve: ' . $file . '. Az oldalon mindenhol az új látszik. Ha mégsem jó, az „Előző vissza” gombbal visszahozod.');
    }

    // ---- előző változat vissza ----
    if ($action === 'restore') {
        if (!img_valid_name($file) || !img_restore($file)) {
            flash_redirect(false, 'Ehhez a képhez nincs elmentett előző változat.');
        }
        flash_redirect(true, 'Visszaállítva az előző kép: ' . $file);
    }

    // ---- új galériakép ----
    if ($action === 'add') {
        $caption = trim((string) ($_POST['caption'] ?? ''));
        if (mb_strlen($caption) < 3) {
            flash_redirect(false, 'Adj a képnek egy rövid feliratot (legalább 3 karakter) — ez jelenik meg alatta.');
        }
        if ($err = img_check_upload($_FILES['photo'] ?? null)) {
            flash_redirect(false, $err);
        }
        $slug = img_slug($caption);
        $name = $slug . '.jpg';
        for ($i = 2; is_file(IMG_DIR . '/' . $name); $i++) {
            $name = $slug . '-' . $i . '.jpg';
        }
        $newKey = 'g_' . str_replace('-', '_', pathinfo($name, PATHINFO_FILENAME));
        if (in_array($newKey, $keys, true)) {
            $newKey .= '_' . substr(bin2hex(random_bytes(2)), 0, 4);
        }
        if (!img_save_resized($_FILES['photo']['tmp_name'], IMG_DIR . '/' . $name)) {
            flash_redirect(false, 'A képet nem sikerült feldolgozni. Próbáld más formátumban (JPG).');
        }
        $extras[] = ['file' => $name, 'key' => $newKey];
        $site['gallery_extra'] = $extras;
        if (!empty($site['gallery_order'])) {
            $site['gallery_order'][] = $newKey;
        }
        foreach (['hu', 'en', 'de'] as $l) {          // ugyanaz a felirat mindhárom nyelven;
            overrides_set($l, 'gallery.' . $newKey, $caption);   // a Szövegek fülön fordítható
        }
        if (!site_save($siteFile, $site)) {
            flash_redirect(false, 'A kép feltöltődött, de a beállítást nem sikerült menteni (data mappa nem írható?).');
        }
        flash_redirect(true, 'Új kép a galériában: „' . $caption . '”. A felirat angol és német változatát a Szövegek fülön, a Galéria részben írhatod át.');
    }

    // ---- elrejtés / mutatás a galériában ----
    if ($action === 'hide' || $action === 'show') {
        if (!in_array($key, $keys, true)) {
            flash_redirect(false, 'Ismeretlen kép.');
        }
        $hidden = array_values(array_diff($hidden, [$key]));
        if ($action === 'hide') {
            $hidden[] = $key;
        }
        $site['gallery_hidden'] = $hidden;
        site_save($siteFile, $site);
        flash_redirect(true, $action === 'hide' ? 'A kép nem jelenik meg a galériában. Bármikor visszakapcsolhatod.' : 'A kép újra látszik a galériában.');
    }

    // ---- sorrend ----
    if ($action === 'move') {
        $dir = (string) ($_POST['dir'] ?? '');
        $i   = array_search($key, $keys, true);
        if ($i === false) {
            flash_redirect(false, 'Ismeretlen kép.');
        }
        $j = $dir === 'up' ? $i - 1 : $i + 1;
        if ($j >= 0 && $j < count($keys)) {
            [$keys[$i], $keys[$j]] = [$keys[$j], $keys[$i]];
            $site['gallery_order'] = $keys;
            site_save($siteFile, $site);
        }
        flash_redirect(true, 'Sorrend módosítva.');
    }

    // ---- adminból feltöltött kép törlése ----
    if ($action === 'delete') {
        $idx = null;
        foreach ($extras as $n => $g) {
            if (($g['key'] ?? '') === $key) {
                $idx = $n;
            }
        }
        if ($idx === null) {
            flash_redirect(false, 'Csak az adminból feltöltött képeket lehet törölni; az eredetieket elrejteni tudod.');
        }
        $f = $extras[$idx]['file'];
        if (img_valid_name($f)) {
            if (!is_dir(IMG_BACKUP)) {
                mkdir(IMG_BACKUP, 0775, true);
            }
            rename(IMG_DIR . '/' . $f, IMG_BACKUP . '/torolt-' . date('Ymd-His') . '-' . $f);
        }
        unset($extras[$idx]);
        $site['gallery_extra']  = array_values($extras);
        $site['gallery_hidden'] = array_values(array_diff($hidden, [$key]));
        if (!empty($site['gallery_order'])) {
            $site['gallery_order'] = array_values(array_diff($site['gallery_order'], [$key]));
        }
        foreach (['hu', 'en', 'de'] as $l) {
            overrides_set($l, 'gallery.' . $key, null);
        }
        site_save($siteFile, $site);
        flash_redirect(true, 'Kép törölve a galériából.');
    }

    flash_redirect(false, 'Ismeretlen művelet.');
}

/* ---------------- Megjelenítés ---------------- */

$CFG      = require $ROOT . '/inc/config.php';
$flash    = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

$huT      = require $ROOT . '/lang/hu.php';
$ovHu     = is_file(ADMIN_DATA . '/overrides.hu.json')
    ? (json_decode((string) file_get_contents(ADMIN_DATA . '/overrides.hu.json'), true) ?: [])
    : [];
$caption  = fn(string $k) => (string) ($ovHu['gallery.' . $k] ?? $huT['gallery'][$k] ?? '');
$fixed    = img_fixed_uses();
$hidden   = array_map('strval', $site['gallery_hidden'] ?? []);
$extraKeys = array_column($site['gallery_extra'] ?? [], 'key');
$count    = count($CFG['gallery']);

$adminTitle = 'Képek';
$adminTab   = 'images';
require __DIR__ . '/_head.php';
?>

    <div class="edit-head">
      <p class="eyebrow">Szerkesztés</p>
      <h1>Képek</h1>
      <p class="lead">
        Telefonról is megy: válaszd ki a fotót, a többi magától történik (kicsinyítés, feltöltés).
        Egy cserélt kép az oldal minden helyén frissül. Az előző változat egy lépéssel visszahozható.
      </p>
    </div>

    <?php if ($flash): ?>
    <div class="edit-msg <?= $flash[0] ? 'is-ok' : 'is-bad' ?>"><?= h($flash[1]) ?></div>
    <?php endif; ?>

    <!-- Új kép -->
    <section class="edit-panel">
      <h2>Új kép a galériába</h2>
      <p class="hint">Fekvő kép a legszebb. A felirat a kép alatt jelenik meg.</p>
      <form method="post" enctype="multipart/form-data" id="addForm" class="upload-box">
        <input type="hidden" name="csrf" value="<?= h(admin_csrf()) ?>">
        <input type="hidden" name="action" value="add">
        <label class="btn btn--light" for="addPhoto">Fotó kiválasztása…</label>
        <input type="file" id="addPhoto" name="photo" accept="image/*" required style="position:absolute;width:1px;height:1px;opacity:0">
        <span id="addName" style="display:block;margin-top:10px;color:var(--ink-faint);font-size:.88rem">Még nincs kiválasztva kép. Ide is húzhatod.</span>
        <img id="addPreview" class="upload-preview" alt="">
        <div class="edit-row">
          <label for="caption">Felirat a kép alatt</label>
          <input type="text" id="caption" name="caption" maxlength="120" required placeholder="például: Nyári terasz naplementében">
        </div>
        <div class="savebar savebar--inline" style="justify-content:center">
          <button class="btn btn--primary" type="submit">Feltöltés a galériába</button>
        </div>
      </form>
    </section>

    <!-- Meglévő képek -->
    <section class="edit-panel">
      <h2>Az oldal képei <small style="font-weight:400;color:var(--ink-faint);font-size:.85rem"><?= $count ?> kép</small></h2>
      <p class="hint">
        A „Csere…” ugyanazt a képet cseréli le mindenhol, ahol szerepel. Az „Elrejt” csak a galériából veszi ki —
        ha máshol is használt kép, ott marad. A nyilakkal a galéria sorrendjét állítod.
      </p>
      <div class="img-grid">
        <?php foreach ($CFG['gallery'] as $i => $shot):
            $f    = $shot['file'];
            $k    = $shot['key'];
            $isH  = in_array($k, $hidden, true);
            $isX  = in_array($k, $extraKeys, true);
            $uses = [];
            if (isset($fixed[$f])) {
                $uses[] = $fixed[$f];
            }
            $uses[] = $isH ? 'Galéria (rejtve)' : 'Galéria'; ?>
        <article class="img-card<?= $isH ? ' is-hidden' : '' ?>">
          <img src="<?= h(img_url($f)) ?>" alt="" loading="lazy">
          <div class="body">
            <div class="cap"><?= h($caption($k) ?: '(nincs felirat)') ?></div>
            <div class="use"><?= h(implode(' · ', $uses)) ?></div>
            <div class="meta"><?= h($f) ?> · <?= h(img_meta($f)) ?></div>
            <div class="actions">
              <form method="post" enctype="multipart/form-data" class="replaceForm">
                <input type="hidden" name="csrf" value="<?= h(admin_csrf()) ?>">
                <input type="hidden" name="action" value="replace">
                <input type="hidden" name="file" value="<?= h($f) ?>">
                <label class="btn btn--primary btn--xs" for="rep-<?= $i ?>">Csere…</label>
                <input type="file" id="rep-<?= $i ?>" name="photo" accept="image/*">
              </form>
              <?php if (img_has_backup($f)): ?>
              <form method="post">
                <input type="hidden" name="csrf" value="<?= h(admin_csrf()) ?>">
                <input type="hidden" name="action" value="restore">
                <input type="hidden" name="file" value="<?= h($f) ?>">
                <button class="btn btn--quiet btn--xs" type="submit">Előző vissza</button>
              </form>
              <?php endif; ?>
              <form method="post">
                <input type="hidden" name="csrf" value="<?= h(admin_csrf()) ?>">
                <input type="hidden" name="action" value="<?= $isH ? 'show' : 'hide' ?>">
                <input type="hidden" name="key" value="<?= h($k) ?>">
                <button class="btn btn--quiet btn--xs" type="submit"><?= $isH ? 'Mutat' : 'Elrejt' ?></button>
              </form>
              <form method="post" style="display:inline-flex;gap:4px">
                <input type="hidden" name="csrf" value="<?= h(admin_csrf()) ?>">
                <input type="hidden" name="action" value="move">
                <input type="hidden" name="key" value="<?= h($k) ?>">
                <button class="btn btn--quiet btn--xs" type="submit" name="dir" value="up" aria-label="Előrébb"<?= $i === 0 ? ' disabled' : '' ?>>↑</button>
                <button class="btn btn--quiet btn--xs" type="submit" name="dir" value="down" aria-label="Hátrébb"<?= $i === $count - 1 ? ' disabled' : '' ?>>↓</button>
              </form>
              <?php if ($isX): ?>
              <form method="post" onsubmit="return confirm('Biztosan törlöd ezt a képet a galériából?')">
                <input type="hidden" name="csrf" value="<?= h(admin_csrf()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="key" value="<?= h($k) ?>">
                <button class="btn btn--danger btn--xs" type="submit">Törlés</button>
              </form>
              <?php endif; ?>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </section>

    <p class="edit-foot">
      A képek automatikusan legfeljebb 1600 pixel szélesek lesznek, hogy gyorsan töltsön az oldal.
      Az eredeti fájlokat nem kell megőrizned — de a legutóbbi előző változat mindig visszahozható.
    </p>

<?php
$adminScript = <<<'JS'
(function () {
  // Kicsinyítés a böngészőben feltöltés előtt: telefonfotó 5 MB → kb. 400 KB.
  async function shrink(file, max, q) {
    max = max || 1800; q = q || 0.86;
    if (!/^image\/(jpeg|png|webp)$/.test(file.type)) return file;
    var bmp = null;
    try { bmp = await createImageBitmap(file, { imageOrientation: 'from-image' }); }
    catch (e) { try { bmp = await createImageBitmap(file); } catch (e2) { return file; } }
    var s = Math.min(1, max / Math.max(bmp.width, bmp.height));
    if (s === 1 && file.size < 900 * 1024) { bmp.close(); return file; }
    var c = document.createElement('canvas');
    c.width = Math.round(bmp.width * s); c.height = Math.round(bmp.height * s);
    c.getContext('2d').drawImage(bmp, 0, 0, c.width, c.height);
    bmp.close();
    var blob = await new Promise(function (r) { c.toBlob(r, 'image/jpeg', q); });
    if (!blob) return file;
    return new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' });
  }
  function setFile(input, file) {
    try { var dt = new DataTransfer(); dt.items.add(file); input.files = dt.files; } catch (e) {}
  }

  // Csere: fájl kiválasztása után azonnal feltölt
  document.querySelectorAll('.replaceForm input[type=file]').forEach(function (inp) {
    inp.addEventListener('change', async function () {
      if (!inp.files[0]) return;
      var card = inp.closest('.img-card');
      card.classList.add('is-busy');
      var lbl = card.querySelector('label[for="' + inp.id + '"]');
      if (lbl) lbl.textContent = 'Feltöltés…';
      setFile(inp, await shrink(inp.files[0]));
      inp.form.submit();
    });
  });

  // Új kép: előnézet + kicsinyítés küldéskor
  var addForm = document.getElementById('addForm');
  var addInp  = document.getElementById('addPhoto');
  var addName = document.getElementById('addName');
  var addPrev = document.getElementById('addPreview');
  function showPick() {
    var f = addInp.files[0];
    if (!f) return;
    addName.textContent = f.name + ' · ' + Math.round(f.size / 1024) + ' KB';
    addPrev.src = URL.createObjectURL(f);
    addPrev.classList.add('is-on');
  }
  if (addForm) {
    addInp.addEventListener('change', showPick);
    ['dragenter', 'dragover'].forEach(function (ev) {
      addForm.addEventListener(ev, function (e) { e.preventDefault(); addForm.classList.add('is-over'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
      addForm.addEventListener(ev, function (e) { e.preventDefault(); addForm.classList.remove('is-over'); });
    });
    addForm.addEventListener('drop', function (e) {
      if (e.dataTransfer.files[0]) { setFile(addInp, e.dataTransfer.files[0]); showPick(); }
    });
    var shrunk = false;
    addForm.addEventListener('submit', async function (e) {
      if (shrunk || !addInp.files[0]) return;
      e.preventDefault();
      var btn = addForm.querySelector('button[type=submit]');
      btn.disabled = true; btn.textContent = 'Feltöltés…';
      setFile(addInp, await shrink(addInp.files[0]));
      shrunk = true;
      addForm.submit();
    });
  }
})();
JS;
require __DIR__ . '/_foot.php';
