<?php
/**
 * Nyelvkezelés. A nyelv sorrendben innen jön:
 *   1. ?lang=xx paraméter (ilyenkor sütibe is elmentjük)
 *   2. korábban elmentett süti
 *   3. a böngésző Accept-Language fejléce
 *   4. magyar
 */

const LANGS = ['hu' => 'Magyar', 'en' => 'English', 'de' => 'Deutsch'];

// A munkamenetet még bármilyen kimenet előtt el kell indítani (CSRF-token az űrlaphoz).
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

function detect_lang(): string
{
    if (isset($_GET['lang']) && isset(LANGS[$_GET['lang']])) {
        $lang = $_GET['lang'];
        setcookie('lang', $lang, [
            'expires'  => time() + 31536000,
            'path'     => '/',
            'samesite' => 'Lax',
        ]);
        return $lang;
    }

    if (isset($_COOKIE['lang']) && isset(LANGS[$_COOKIE['lang']])) {
        return $_COOKIE['lang'];
    }

    $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    foreach (explode(',', $accept) as $part) {
        $code = strtolower(substr(trim($part), 0, 2));
        if (isset(LANGS[$code])) {
            return $code;
        }
    }

    return 'hu';
}

$LANG = detect_lang();
$T = require __DIR__ . '/../lang/' . $LANG . '.php';

// Az adminban mentett szövegek rárakása az alap fordításokra (data/overrides.<nyelv>.json).
// Csak létező kulcsot írunk felül, típushelyesen — így a felülírás nem tudja eltörni az oldalt.
$__ovFile = __DIR__ . '/../data/overrides.' . $LANG . '.json';
if (is_file($__ovFile)) {
    // A kezdő UTF-8 BOM-ot levágjuk (pl. Jegyzettömbből mentett fájl), különben a json_decode elhasal.
    $__ov = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', (string) file_get_contents($__ovFile)), true);
    if (is_array($__ov)) {
        foreach ($__ov as $__path => $__val) {
            if (!is_string($__val) && !is_int($__val) && !is_float($__val)) {
                continue;
            }
            $__ref   =& $T;
            $__parts = explode('.', (string) $__path);
            $__last  = array_pop($__parts);
            $__ok    = true;
            foreach ($__parts as $__p) {
                if (!is_array($__ref) || !array_key_exists($__p, $__ref)) {
                    $__ok = false;
                    break;
                }
                $__ref =& $__ref[$__p];
            }
            if ($__ok && is_array($__ref) && array_key_exists($__last, $__ref)) {
                if (is_int($__ref[$__last])) {
                    $__ref[$__last] = (int) $__val;
                } elseif (is_string($__ref[$__last])) {
                    $__ref[$__last] = (string) $__val;
                }
            }
            unset($__ref);
        }
    }
    unset($__ov, $__path, $__val, $__parts, $__last, $__ok, $__p);
}
unset($__ovFile);
$CFG = require __DIR__ . '/config.php';

/** Fordított szöveg pontokkal elválasztott kulcs alapján: t('nav.menu') */
function t(string $key, string $fallback = ''): string
{
    global $T;
    $node = $T;
    foreach (explode('.', $key) as $part) {
        if (!is_array($node) || !array_key_exists($part, $node)) {
            return $fallback !== '' ? $fallback : $key;
        }
        $node = $node[$part];
    }
    return is_string($node) ? $node : $key;
}

/** Fordított tömb (listák, étlapcsoportok) */
function ta(string $key): array
{
    global $T;
    $node = $T;
    foreach (explode('.', $key) as $part) {
        if (!is_array($node) || !array_key_exists($part, $node)) {
            return [];
        }
        $node = $node[$part];
    }
    return is_array($node) ? $node : [];
}

/** Kiírásra biztonságos szöveg */
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Belső link a jelenlegi nyelvvel */
function u(string $page): string
{
    global $LANG;
    return $LANG === 'hu' ? $page : $page . '?lang=' . $LANG;
}

/** Ugyanez az oldal másik nyelven */
function switch_url(string $lang): string
{
    $page = basename($_SERVER['SCRIPT_NAME']);
    return $page . '?lang=' . $lang;
}

/**
 * Nyitva vagyunk-e most. A szerver óráját használja; a fejlécben lévő
 * jelzést a JS percenként frissíti a látogató saját idejéhez igazítva.
 */
function open_state(array $cfg, ?DateTimeInterface $now = null): array
{
    $tz  = new DateTimeZone('Europe/Budapest');
    $now = $now ? DateTime::createFromInterface($now)->setTimezone($tz) : new DateTime('now', $tz);

    $day     = (int) $now->format('w');
    $minutes = (int) $now->format('G') * 60 + (int) $now->format('i');

    $today = $cfg['hours'][$day] ?? null;
    if ($today) {
        [$openStr, $closeStr] = $today;
        $open  = hm_to_min($openStr);
        $close = hm_to_min($closeStr);
        if ($minutes >= $open && $minutes < $close) {
            return ['open' => true, 'until' => $closeStr];
        }
        if ($minutes < $open) {
            return ['open' => false, 'next' => $openStr, 'nextDay' => $day];
        }
    }

    // ma már zárva: keressük a következő nyitást
    for ($i = 1; $i <= 7; $i++) {
        $d = ($day + $i) % 7;
        if (isset($cfg['hours'][$d])) {
            return ['open' => false, 'next' => $cfg['hours'][$d][0], 'nextDay' => $d];
        }
    }

    return ['open' => false];
}

function hm_to_min(string $hm): int
{
    [$h, $m] = array_map('intval', explode(':', $hm));
    return $h * 60 + $m;
}

/** 7 800 Ft alakú ár */
function ft(int $amount): string
{
    return number_format($amount, 0, ',', ' ') . ' Ft';
}
