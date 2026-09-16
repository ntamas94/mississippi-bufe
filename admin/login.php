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
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex,nofollow">
<title>Belépés — Mississippi Büfé &amp; Missouri Szálláshely</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap">
<link rel="stylesheet" href="../assets/app.css?v=9">
<script>
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
  .login-wrap { min-height: 100vh; display: grid; place-items: center; padding: 30px 20px;
                background: var(--bg-sunk); }
  .login-card { width: min(94vw, 420px); background: var(--surface); border: 1px solid var(--line);
                border-radius: var(--r-lg); box-shadow: var(--shadow-md); padding: 34px 32px; }
  .login-brand { display: inline-flex; align-items: center; gap: 12px; margin-bottom: 22px;
                 text-decoration: none; color: inherit; }
  .login-brand .brand-mark { width: 42px; height: 42px; display: grid; place-items: center;
                 border-radius: 12px; background: var(--green-700); color: var(--amber);
                 font-family: var(--serif); font-size: 1.3rem; }
  .login-brand span.txt { font-family: var(--serif); font-size: 1.05rem; }
  .login-brand span.txt em { font-style: normal; color: var(--amber); }
  .login-card h1 { font-size: 1.35rem; margin: 0 0 8px; }
  .login-card p.sub { color: var(--ink-soft); font-size: .95rem; margin: 0 0 22px; }
  .login-card label { display: block; font-size: .88rem; font-weight: 600;
                      color: var(--ink-soft); margin: 16px 0 7px; }
  .login-card input { width: 100%; padding: 13px 15px; border: 1px solid var(--line);
                      border-radius: var(--r-sm); background: var(--surface-2);
                      color: var(--ink); font: inherit; }
  .login-card input:focus { outline: 2px solid var(--amber); border-color: transparent; }
  .login-card .btn { width: 100%; margin-top: 24px; }
  .login-err { background: rgba(207, 95, 65, .13); border: 1px solid rgba(207, 95, 65, .4);
               color: #b14b30; padding: 12px 15px; border-radius: var(--r-sm);
               font-size: .9rem; margin-bottom: 4px; }
  .login-tip { background: var(--bg-sunk); border-radius: var(--r-sm); padding: 14px 16px;
               color: var(--ink-soft); font-size: .88rem; margin: 20px 0 0; }
  .login-back { display: inline-block; margin-top: 20px; font-size: .9rem; color: var(--ink-faint); }
</style>
</head>
<body class="page-login">
<div class="login-wrap">
  <form class="login-card" method="post" action="login.php">
    <input type="hidden" name="csrf" value="<?= h(admin_csrf()) ?>">

    <a class="login-brand" href="../index.php">
      <span class="brand-mark">M</span>
      <span class="txt">Mississippi <em>Büfé &amp; Szálláshely</em></span>
    </a>

    <?php if ($setupMode): ?>
      <h1>Válassz egy jelszót</h1>
      <p class="sub">
        Most vagy itt először. Ezzel a jelszóval tudod majd átírni az oldal szövegeit és árait.
        Legalább 8 karakter legyen.
      </p>
    <?php else: ?>
      <h1>Belépés a szerkesztőbe</h1>
      <p class="sub">
        Add meg a jelszavad, és szerkesztheted az oldal szövegeit, árait.
      </p>
    <?php endif; ?>

    <?php if ($err): ?><div class="login-err"><?= h($err) ?></div><?php endif; ?>

    <label for="pass">Jelszó</label>
    <input type="password" id="pass" name="pass" required minlength="<?= $setupMode ? 8 : 1 ?>" autofocus autocomplete="<?= $setupMode ? 'new-password' : 'current-password' ?>">

    <?php if ($setupMode): ?>
      <label for="pass2">Jelszó még egyszer</label>
      <input type="password" id="pass2" name="pass2" required minlength="8" autocomplete="new-password">
    <?php endif; ?>

    <button class="btn btn--primary" type="submit"><?= $setupMode ? 'Jelszó mentése és belépés' : 'Belépés' ?></button>

    <?php if ($setupMode): ?>
    <p class="login-tip">
      Írd fel a jelszót valahová. Ha elfelejtenéd, a szerveren a <code>data/admin.php</code>
      fájlt kell törölni, és újra beállíthatod.
    </p>
    <?php endif; ?>

    <a class="login-back" href="../index.php">← Vissza az oldalra</a>
  </form>
</div>
</body>
</html>
