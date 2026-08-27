<?php
/**
 * Admin munkamenet és segédfüggvények.
 * Jelszó a kódban NINCS: az első megnyitáskor a böngészőben adod meg,
 * és csak a hash-e kerül a data/admin.php fájlba.
 */
declare(strict_types=1);

const ADMIN_DATA = __DIR__ . '/../data';

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

/** A mentett hitelesítő adat, vagy null, ha még nincs jelszó beállítva. */
function admin_cred(): ?array
{
    $f = ADMIN_DATA . '/admin.php';
    if (!is_file($f)) {
        return null;
    }
    $c = require $f;
    return (is_array($c) && !empty($c['hash'])) ? $c : null;
}

/** A jelszó hash mentése PHP-fájlként — webről lekérve nem ad ki semmit. */
function admin_save_hash(string $hash): bool
{
    if (!is_dir(ADMIN_DATA) && !mkdir(ADMIN_DATA, 0775, true)) {
        return false;
    }
    $php = "<?php\n// Automatikusan generált fájl — az admin jelszó hash-e. Ne tedd közzé!\n"
         . "return ['hash' => " . var_export($hash, true) . "];\n";
    return file_put_contents(ADMIN_DATA . '/admin.php', $php, LOCK_EX) !== false;
}

function admin_logged_in(): bool
{
    return !empty($_SESSION['admin_ok']);
}

function admin_require(): void
{
    if (!admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function admin_csrf(): string
{
    if (empty($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['admin_csrf'];
}

function admin_csrf_check(): void
{
    if (!hash_equals($_SESSION['admin_csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Érvénytelen kérés (CSRF) — töltsd újra az oldalt.');
    }
}

/** Kiírásra biztonságos szöveg (az admin nem tölti be a site i18n-jét). */
function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
