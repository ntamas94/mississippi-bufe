<?php
require __DIR__ . '/_auth.php';

unset($_SESSION['admin_ok']);
session_regenerate_id(true);
header('Location: login.php');
exit;
