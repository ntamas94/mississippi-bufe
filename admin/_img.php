<?php
/**
 * Képkezelés az adminhoz: ellenőrzés, átméretezés (GD), biztonsági másolat.
 * A feltöltött kép mindig JPEG-ként, legfeljebb 1600 px-es oldalhosszal kerül
 * az images/ mappába — az előző változat a data/img-backup/ alá kerül.
 */
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';

const IMG_DIR      = __DIR__ . '/../images';
const IMG_BACKUP   = ADMIN_DATA . '/img-backup';
const IMG_MAX_SIDE = 1600;
const IMG_QUALITY  = 82;

/** Az oldalon fixen (nem csak a galériában) használt képek: fájl → hol látszik. */
function img_fixed_uses(): array
{
    return [
        'bufe-tabla.jpg'       => 'Facebook / Google előnézeti kép',
        'pizza.jpg'            => 'Kezdőlap – büfé kártya',
        'motel-epulet.jpg'     => 'Kezdőlap – szálláshely kártya · Szálláshely oldal',
        'terasz-to.jpg'        => 'Kezdőlap – terasz',
        'kert-to.jpg'          => 'Kezdőlap – terasz',
        'terasz-jatszoter.jpg' => 'Kezdőlap – terasz',
        'szoba-1.jpg'          => 'Szálláshely oldal – szoba',
        'furdo.jpg'            => 'Szálláshely oldal – fürdő',
    ];
}

/** Feltöltött fájl ellenőrzése. Hibaüzenet, vagy null ha rendben. */
function img_check_upload(?array $f): ?string
{
    $err = $f['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err === UPLOAD_ERR_NO_FILE) {
        return 'Nem választottál ki képet.';
    }
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
        return 'Túl nagy a fájl a szerver beállításaihoz. Próbáld újra — a böngésző általában magától kicsinyíti; ha nem, küldd kisebb méretben.';
    }
    if ($err !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        return 'A feltöltés nem sikerült (hibakód ' . (int) $err . '). Próbáld újra.';
    }
    $info = @getimagesize($f['tmp_name']);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
        return 'Csak JPG, PNG vagy WebP képet lehet feltölteni. iPhone-on: Beállítások → Kamera → Formátumok → „Leginkább kompatibilis”, így JPG-t készít.';
    }
    if ($info[0] < 400 || $info[1] < 300) {
        return 'Túl kicsi a kép (' . $info[0] . '×' . $info[1] . ' px). Legalább 400×300 kell.';
    }
    return null;
}

/** Kép betöltése GD-be, EXIF-forgatás alkalmazásával (ha van exif kiterjesztés). */
function img_load(string $path): ?GdImage
{
    $info = @getimagesize($path);
    if (!$info) {
        return null;
    }
    $im = match ($info[2]) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
        IMAGETYPE_PNG  => @imagecreatefrompng($path),
        IMAGETYPE_WEBP => @imagecreatefromwebp($path),
        default        => false,
    };
    if (!$im) {
        return null;
    }
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif  = @exif_read_data($path);
        $o     = (int) ($exif['Orientation'] ?? 1);
        $angle = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
        if ($angle !== 0) {
            $r = imagerotate($im, $angle, 0);
            if ($r) {
                imagedestroy($im);
                $im = $r;
            }
        }
    }
    return $im;
}

/** Átméretezés legfeljebb $maxSide px-re, mentés progresszív JPEG-ként. */
function img_save_resized(string $src, string $dest, int $maxSide = IMG_MAX_SIDE): bool
{
    $im = img_load($src);
    if (!$im) {
        return false;
    }
    $w = imagesx($im);
    $h = imagesy($im);
    $scale = min(1.0, $maxSide / max($w, $h));
    $nw = (int) round($w * $scale);
    $nh = (int) round($h * $scale);

    $out   = imagecreatetruecolor($nw, $nh);
    $white = imagecolorallocate($out, 255, 255, 255);   // PNG-átlátszóság fehér háttérre
    imagefill($out, 0, 0, $white);
    imagecopyresampled($out, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagedestroy($im);

    imageinterlace($out, true);
    $ok = imagejpeg($out, $dest, IMG_QUALITY);
    imagedestroy($out);
    return $ok;
}

/** Az előző változat félretétele (egy szint mélyen). */
function img_backup(string $file): void
{
    $src = IMG_DIR . '/' . $file;
    if (!is_file($src)) {
        return;
    }
    if (!is_dir(IMG_BACKUP)) {
        mkdir(IMG_BACKUP, 0775, true);
    }
    copy($src, IMG_BACKUP . '/' . $file);
}

function img_has_backup(string $file): bool
{
    return is_file(IMG_BACKUP . '/' . $file);
}

/** Visszacserélés: a mostani kép lesz a mentés, a mentett a mostani. */
function img_restore(string $file): bool
{
    $b   = IMG_BACKUP . '/' . $file;
    $cur = IMG_DIR . '/' . $file;
    if (!is_file($b)) {
        return false;
    }
    $tmp = $b . '.swap';
    if (is_file($cur)) {
        rename($cur, $tmp);
    }
    $ok = rename($b, $cur);
    if (is_file($tmp)) {
        rename($tmp, $b);
    }
    if ($ok) {
        touch($cur);                                  // új mtime → új ?v= a cache-busting miatt
    }
    return $ok;
}

/** Biztonságos fájlnév: ékezet nélkül, kisbetű, kötőjel. */
function img_slug(string $s): string
{
    $s = mb_strtolower(trim($s));
    $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ö' => 'o', 'ő' => 'o',
                    'ú' => 'u', 'ü' => 'u', 'ű' => 'u', 'ä' => 'a', 'ß' => 'ss']);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim((string) $s, '-');
    return $s === '' ? 'kep' : substr($s, 0, 40);
}

/** Csak az images/ mappában lévő, egyszerű nevű jpg fogadható el paraméterként. */
function img_valid_name(string $f): bool
{
    return (bool) preg_match('/^[a-z0-9_-]+\.jpg$/', $f) && is_file(IMG_DIR . '/' . $f);
}

/** Méretcímke a listához: „1600×1067 · 210 KB”. */
function img_meta(string $file): string
{
    $p = IMG_DIR . '/' . $file;
    if (!is_file($p)) {
        return 'hiányzik!';
    }
    $i  = @getimagesize($p);
    $kb = (int) round(filesize($p) / 1024);
    return ($i ? $i[0] . '×' . $i[1] . ' · ' : '') . $kb . ' KB';
}

/** Kép URL az adminból nézve, verziószámmal. */
function img_url(string $file): string
{
    $p = IMG_DIR . '/' . $file;
    return '../images/' . rawurlencode($file) . (is_file($p) ? '?v=' . filemtime($p) : '');
}
