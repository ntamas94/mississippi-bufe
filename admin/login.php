<?php
require __DIR__ . '/_auth.php';

if (admin_logged_in()) {
    header('Location: index.php');
    exit;
}

$cred      = admin_cred();
$setupMode = $cred === null;   // első indítás: még nincs jelszó
$err       = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_check();

    if ($setupMode) {
        $p1 = (string) ($_POST['pass'] ?? '');
        $p2 = (string) ($_POST['pass2'] ?? '');
        if (strlen($p1) < 8) {
            $err = 'A jelszó legalább 8 karakter legyen.';
        } elseif ($p1 !== $p2) {
            $err = 'A két jelszó nem egyezik.';
        } elseif (!admin_save_hash(password_hash($p1, PASSWORD_DEFAULT))) {
            $err = 'Nem sikerült menteni — írható a data/ mappa a szerveren?';
        } else {
            session_regenerate_id(true);
            $_SESSION['admin_ok'] = true;
            header('Location: index.php');
            exit;
        }
    } else {
        // lassítás próbálgatás ellen
        $_SESSION['admin_tries'] = (int) ($_SESSION['admin_tries'] ?? 0) + 1;
        if ($_SESSION['admin_tries'] > 3) {
            sleep(2);
        }
        if (password_verify((string) ($_POST['pass'] ?? ''), $cred['hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_ok']    = true;
            $_SESSION['admin_tries'] = 0;
            header('Location: index.php');
            exit;
        }
        $err = 'Hibás jelszó.';
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Admin belépés — Mississippi Büfé</title>
<style>
  :root { color-scheme: dark; }
  * { box-sizing: border-box; }
  body { margin: 0; min-height: 100vh; display: grid; place-items: center;
         font-family: system-ui, sans-serif; background: #191512; color: #fbf8f2; }
  .card { width: min(92vw, 380px); background: #221d18; border: 1px solid #3a322a;
          border-radius: 14px; padding: 28px; }
  h1 { font-size: 1.15rem; margin: 0 0 6px; }
  p.note { color: #b5a globális; }
  p { color: #b5aa9c; font-size: .9rem; margin: 0 0 18px; }
  label { display: block; font-size: .8rem; margin: 14px 0 6px; color: #d8cfc3; }
  input { width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #4a4036;
          background: #191512; color: #fbf8f2; font-size: 1rem; }
  input:focus { outline: 2px solid #e8a13c; border-color: transparent; }
  button { margin-top: 20px; width: 100%; padding: 11px; border: 0; border-radius: 8px;
           background: linear-gradient(135deg, #e8a13c, #c97f1e); color: #211505;
           font-weight: 700; font-size: 1rem; cursor: pointer; }
  .err { background: #3a1f1c; border: 1px solid #6b2f28; color: #f0b9ae;
         padding: 10px 12px; border-radius: 8px; font-size: .85rem; margin-bottom: 6px; }
  a { color: #e8a13c; }
</style>
</head>
<body>
  <form class="card" method="post" action="login.php">
    <input type="hidden" name="csrf" value="<?= h(admin_csrf()) ?>">
    <?php if ($setupMode): ?>
      <h1>Admin jelszó beállítása</h1>
      <p>Első indítás: adj meg egy jelszót a szerkesztőhöz (legalább 8 karakter). Ezzel fogsz ezután belépni.</p>
    <?php else: ?>
      <h1>Admin belépés</h1>
      <p>Mississippi Büfé &amp; Missouri Szálláshely — szövegszerkesztő.</p>
    <?php endif; ?>
    <?php if ($err): ?><div class="err"><?= h($err) ?></div><?php endif; ?>
    <label for="pass">Jelszó</label>
    <input type="password" id="pass" name="pass" required minlength="<?= $setupMode ? 8 : 1 ?>" autofocus autocomplete="<?= $setupMode ? 'new-password' : 'current-password' ?>">
    <?php if ($setupMode): ?>
      <label for="pass2">Jelszó még egyszer</label>
      <input type="password" id="pass2" name="pass2" required minlength="8" autocomplete="new-password">
    <?php endif; ?>
    <button type="submit"><?= $setupMode ? 'Beállítás és belépés' : 'Belépés' ?></button>
  </form>
</body>
</html>
