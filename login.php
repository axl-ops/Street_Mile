<?php

declare(strict_types=1);

session_start();

/* =========================
   SECURITY HEADERS
========================= */
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

/* =========================
   DATABASE
========================= */
require_once "koneksi.php";

/* =========================
   LOGIN RATE LIMIT
========================= */
if (!isset($_SESSION['login_attempt'])) {
  $_SESSION['login_attempt'] = 0;
}

if ($_SESSION['login_attempt'] >= 5) {
  die("Terlalu banyak percobaan login. Coba lagi nanti.");
}

/* =========================
   INIT ERROR
========================= */
$error = "";

/* =========================
   LOGIN PROCESS
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

  $email = trim($_POST['email'] ?? '');
  $password = trim($_POST['password'] ?? '');

  /* VALIDASI KOSONG */
  if ($email === '' || $password === '') {
    $error = "Email dan password wajib diisi.";
  }

  /* VALIDASI FORMAT EMAIL */ elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Format email tidak valid.";
  } else {

    $stmt = mysqli_prepare(
      $conn,
      "SELECT id, name, email, password, role, is_active
       FROM users
       WHERE email = ?
       LIMIT 1"
    );

    if ($stmt) {

      mysqli_stmt_bind_param($stmt, "s", $email);
      mysqli_stmt_execute($stmt);

      $result = mysqli_stmt_get_result($stmt);

      if ($result && $user = mysqli_fetch_assoc($result)) {

        if (password_verify($password, $user['password'])) {

          if ((int)$user['is_active'] === 1) {

            session_regenerate_id(true);

            $_SESSION['login_attempt'] = 0;

            $_SESSION['login'] = true;
            $_SESSION['user_id'] = (int)$user['id'];

            /* SIMPAN RAW (AMAN, ESCAPE DI VIEW) */
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            header("Location: index.php");
            exit;
          } else {
            $error = "Akun tidak aktif.";
          }
        } else {
          $_SESSION['login_attempt']++;
          $error = "Invalid login details. Please check your credentials and try again.";
        }
      } else {
        $_SESSION['login_attempt']++;
        $error = "Invalid login details. Please check your credentials and try again.";
      }

      mysqli_stmt_close($stmt);
    } else {
      $error = "Terjadi kesalahan sistem.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Login - Street Mile</title>

  <link href="assets/img/SM1.png" rel="icon">

  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700|Nunito:300,400,600,700|Poppins:300,400,500,600,700" rel="stylesheet">

  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">

  <style>
    /* =========================
   GLOBAL RESET & SECURITY
========================= */

    *,
    *::before,
    *::after {
      box-sizing: border-box;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      margin: 0;
      min-height: 100vh;
      overflow-x: hidden;

      font-family: Arial, sans-serif;

      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      text-rendering: optimizeLegibility;

      background: url("assets/img/Login-Background1.jpg") no-repeat center center fixed;
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
    }

    /* DARK OVERLAY (SECURITY + FOCUS LOGIN) */
    body::before {
      content: "";
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.55);

      z-index: 0;
      pointer-events: none;
    }

    /* =========================
   LOGO LOGIN
========================= */

    .logo img.login-logo {
      max-height: none !important;
      height: 45px !important;
      width: auto !important;
    }

    .logo-container {
      display: flex;
      justify-content: center;
      align-items: center;
      width: 100%;
      text-decoration: none;
      position: relative;
      z-index: 2;
    }

    .login-logo {
      display: block;

      height: 110px;
      width: auto;

      object-fit: contain;

      user-select: none;
      -webkit-user-drag: none;

      filter:
        brightness(0.85) contrast(1.15) drop-shadow(0 4px 12px rgba(0, 0, 0, 0.20));

    }

    /* =========================
   TEXT LOGIN
========================= */

    a.logo .logo-text {
      font-family: "Futura Heavy", sans-serif !important;
      font-size: 34px !important;
      font-weight: 900 !important;
      font-style: normal !important;

      color: rgba(255, 255, 255, 0.98) !important;

      letter-spacing: 1.2px;
      margin-left: 10px;
      line-height: 1;

      text-shadow:
        0 2px 10px rgba(0, 0, 0, 0.35);

      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      text-rendering: optimizeLegibility;
    }

    @media (max-width: 768px) {
      a.logo .logo-text {
        font-size: 26px !important;
      }
    }

    @media (max-width: 480px) {
      a.logo .logo-text {
        font-size: 22px !important;
      }
    }

    /* =========================
   🔥 GLASSMORPHISM LOGIN CARD
========================= */

    .card {
      position: relative;
      z-index: 2;

      border-radius: 18px;

      /* GLASS EFFECT */
      background: rgba(255, 255, 255, 0.10);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);

      border: 1px solid rgba(255, 255, 255, 0.25);

      box-shadow:
        0 10px 40px rgba(0, 0, 0, 0.35),
        inset 0 1px 0 rgba(255, 255, 255, 0.15);

      overflow: hidden;

      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    /* Hover Card */
    @media (hover: hover) {
      .card:hover {
        transform: translateY(-3px);
        box-shadow:
          0 16px 60px rgba(0, 0, 0, 0.45),
          inset 0 1px 0 rgba(255, 255, 255, 0.2);
      }
    }

    /* =========================
   CARD BODY
========================= */

    .card-body {
      padding: 32px;
    }

    @media (max-width: 480px) {
      .card-body {
        padding: 24px;
      }
    }

    /* =========================
   INPUT PREMIUM
========================= */

    .form-control {
      border-radius: 10px;
      padding: 12px;
      border: 1px solid rgba(255, 255, 255, 0.25);

      background: rgba(255, 255, 255, 0.85);
      color: #1f1f1f;
      font-size: 15px;

      transition: 0.2s ease;
      box-shadow: none;
    }

    .form-control::placeholder {
      color: #9c9c9c;
    }

    .form-control:focus {
      border-color: #7b00a8;
      outline: none;

      box-shadow: 0 0 0 0.18rem rgba(123, 0, 168, 0.25);
    }

    /* Autofill Fix */
    input:-webkit-autofill,
    input:-webkit-autofill:hover,
    input:-webkit-autofill:focus {
      -webkit-text-fill-color: #1f1f1f;
      transition: background-color 9999s ease-in-out 0s;
    }

    /* =========================
   BUTTON PREMIUM
========================= */

    .btn-primary {
      background: #7b00a8;
      border: none;
      border-radius: 10px;
      padding: 12px;
      width: 100%;

      font-weight: 600;
      font-size: 15px;
      letter-spacing: 0.3px;

      color: #ffffff !important;
      cursor: pointer;

      transition: 0.25s ease;

      box-shadow: 0px 4px 15px rgba(123, 0, 168, 0.25);
    }

    @media (hover: hover) {
      .btn-primary:hover {
        background: #9400cc;
        transform: translateY(-1px);
        box-shadow: 0px 8px 24px rgba(123, 0, 168, 0.35);
      }
    }

    .btn-primary:active {
      transform: scale(0.99);
    }

    .btn-primary:focus {
      box-shadow: 0 0 0 0.18rem rgba(123, 0, 168, 0.25);
    }

    .btn-primary:disabled {
      opacity: 0.7;
      cursor: not-allowed;
    }

    /* =========================
   CREDITS
========================= */

    .credits {
      text-align: center;
      margin-top: 18px;
    }

    .credits a:hover {
      text-decoration: underline;
      opacity: 0.95;
    }

    /* =========================
   FIX CLICK LAYER
========================= */

    .logo-container,
    .credits {
      position: relative;
      z-index: 2;
    }

    .credits a {
      font-family: 'Goudy Old Style', serif;
      font-weight: 900;
      color: #ffffff !important;
      text-decoration: none;
      text-shadow: 0px 2px 8px rgba(0, 0, 0, 0.4);
      cursor: pointer;
    }

    .card-title {
      color: #ffffff !important;
      text-shadow: 0px 2px 8px rgba(0, 0, 0, 0.35);
      letter-spacing: 0.5px;
    }

    .card-body p {
      color: rgba(255, 255, 255, 0.85) !important;
    }

    .form-label {
      color: rgba(255, 255, 255, 0.90) !important;
      font-weight: 500;
    }

    /* =========================
   ACCESSIBILITY
========================= */

    @media (prefers-reduced-motion: reduce) {

      *,
      *::before,
      *::after {
        animation: none !important;
        transition: none !important;
        scroll-behavior: auto !important;
      }
    }
  </style>
</head>

<body>

  <main>
    <div class="container">

      <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">

        <div class="container">
          <div class="row justify-content-center">

            <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">

              <!-- Logo -->
              <div class="d-flex justify-content-center py-4">
                <a class="logo d-flex align-items-center w-auto">
                  <img
                    src="assets/img/Logo Login1.svg"
                    alt="Street Mile Logo"
                    class="login-logo"
                    loading="lazy"
                    decoding="async">
                  <span class="d-none d-lg-block logo-text">Street Mile</span>
                </a>
              </div>

              <div class="card mb-3">
                <div class="card-body">

                  <div class="pt-4 pb-2">
                    <h5 class="card-title text-center fs-4">Login to Your Account</h5>
                    <p class="text-center small">Enter your email & password</p>
                  </div>

                  <?php if (!empty($error)) : ?>
                    <div class="alert alert-danger text-center">
                      <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                  <?php endif; ?>

                  <form method="POST" class="row g-3" autocomplete="off">
                    <div class="col-12">
                      <label class="form-label">Email</label>
                      <input type="email" name="email" class="form-control" required autocomplete="off">
                    </div>

                    <div class="col-12">
                      <label class="form-label">Password</label>
                      <input type="password" name="password" class="form-control" required autocomplete="new-password">
                    </div>

                    <div class="col-12">
                      <button class="btn btn-primary w-100" type="submit" name="login">
                        Login
                      </button>
                    </div>

                  </form>

                </div>
              </div>

              <div class="credits text-white">
                Designed by
                <a href="https://www.instagram.com/axelwavehassle/" target="_blank" rel="noopener noreferrer">
                  Axel Indra Yudha
                </a>
              </div>

            </div>

          </div>

        </div>
    </div>

    </section>

    </div>
  </main>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

</body>

</html>