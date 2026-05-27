<?php

declare(strict_types=1);

session_start();

/* =========================
   SECURITY: CLEAR SESSION DATA
========================= */
$_SESSION = [];

/* =========================
   SECURITY: DELETE SESSION COOKIE
========================= */
if (ini_get('session.use_cookies')) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

/* =========================
   SECURITY: DESTROY SESSION
========================= */
session_unset();
session_destroy();

/* =========================
   SECURITY: PREVENT BACK BUTTON SESSION REUSE
========================= */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

/* =========================
   REDIRECT
========================= */
header("Location: login.php");
exit;
