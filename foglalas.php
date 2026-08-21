<?php
/**
 * A foglalási űrlap feldolgozása.
 * JSON-t ad vissza (az űrlap fetch-csel küld), de JS nélkül is működik:
 * ilyenkor visszairányít a motel oldalra egy állapotjelzővel.
 */
declare(strict_types=1);

require __DIR__ . '/inc/i18n.php';   // session is itt indul

$isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';

function respond(bool $ok, string $message, bool $isAjax): never
{
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok'      => $ok,
            'message' => $message,
            'csrf'    => $_SESSION['csrf'] ?? null,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Location: motel.php?sent=' . ($ok ? '1' : '0') . '#foglalas');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, t('form.error'), $isAjax);
}

// --- spamszűrés ---------------------------------------------------------
// 1. rejtett mező: embernek láthatatlan, a legtöbb robot kitölti
if (!empty($_POST['website'])) {
    respond(true, t('form.success'), $isAjax);   // csendben elnyeljük
}
// 2. gyanúsan gyors kitöltés
$elapsed = time() - (int) ($_POST['ts'] ?? 0);
if ($elapsed < 3) {
    respond(true, t('form.success'), $isAjax);
}
// 3. munkamenet-token
if (empty($_POST['csrf']) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string) $_POST['csrf'])) {
    respond(false, t('form.error'), $isAjax);
}

// --- beolvasás és ellenőrzés -------------------------------------------
$clean = static fn(string $key, int $max = 200): string
    => trim(substr(str_replace(["\r", "\n"], ' ', (string) ($_POST[$key] ?? '')), 0, $max));

$name    = $clean('name', 120);
$email   = $clean('email', 160);
$phone   = $clean('phone', 60);
$arrival = $clean('arrival', 10);
$nights  = max(1, min(30, (int) ($_POST['nights'] ?? 1)));
$guests  = max(1, min(8, (int) ($_POST['guests'] ?? 1)));
$breakfast = !empty($_POST['breakfast']);
$message = trim(substr((string) ($_POST['message'] ?? ''), 0, 2000));

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $arrival)) {
    respond(false, t('form.error'), $isAjax);
}

$price = $CFG['room_prices'][$guests][$breakfast ? 1 : 0] ?? null;
$total = $price ? $price * $nights : null;

// --- levél összeállítása ------------------------------------------------
$lines = [
    'Foglalási kérés — ' . $CFG['name'],
    str_repeat('-', 44),
    'Név:        ' . $name,
    'E-mail:     ' . $email,
    'Telefon:    ' . ($phone !== '' ? $phone : '—'),
    'Érkezés:    ' . $arrival,
    'Éjszakák:   ' . $nights,
    'Vendégek:   ' . $guests,
    'Reggeli:    ' . ($breakfast ? 'igen' : 'nem'),
    'Becsült ár: ' . ($total !== null ? ft($total) : '—'),
    'Nyelv:      ' . strtoupper($LANG),
    '',
    'Megjegyzés:',
    $message !== '' ? $message : '—',
    '',
    'Küldve: ' . date('Y-m-d H:i') . ' — ' . ($_SERVER['REMOTE_ADDR'] ?? '?'),
];

$subject = '=?UTF-8?B?' . base64_encode('Foglalási kérés — ' . $name . ' (' . $arrival . ')') . '?=';
$headers = [
    'From: ' . $CFG['name'] . ' <' . $CFG['mail_from'] . '>',
    'Reply-To: ' . $name . ' <' . $email . '>',
    'Content-Type: text/plain; charset=UTF-8',
    'X-Mailer: PHP/' . PHP_VERSION,
];

$sent = @mail($CFG['mail_to'], $subject, implode("\n", $lines), implode("\r\n", $headers));

// A kérést mindig naplózzuk is (data/foglalasok.log), így akkor sem vész el,
// ha a levélküldés a szerveren nincs beállítva. A mappát .htaccess védi.
$logDir = __DIR__ . '/data';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
    @file_put_contents($logDir . '/.htaccess', "Require all denied\n");
}
$logged = (bool) @file_put_contents(
    $logDir . '/foglalasok.log',
    implode("\n", $lines) . "\n" . str_repeat('=', 44) . "\n",
    FILE_APPEND | LOCK_EX
);

// új tokent kérünk, hogy ugyanaz az űrlap ne legyen kétszer beküldhető
$_SESSION['csrf'] = bin2hex(random_bytes(16));

$ok = $sent || $logged;
respond($ok, $ok ? t('form.success') : t('form.error'), $isAjax);
