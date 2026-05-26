<?php
session_start();

// Security Headers
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

require_once "koneksi.php";

// Batasi percobaan login sederhana
if (!isset($_SESSION['login_attempt'])) {
  $_SESSION['login_attempt'] = 0;
}

if ($_SESSION['login_attempt'] >= 5) {
  die("Terlalu banyak percobaan login. Coba lagi nanti.");
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

  $email = trim($_POST['email']);
  $password = trim($_POST['password']);

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Format email tidak valid.";
  } else {

    $stmt = mysqli_prepare($conn, "SELECT id, name, email, password, role, is_active FROM users WHERE email = ? LIMIT 1");

    if ($stmt) {

      mysqli_stmt_bind_param($stmt, "s", $email);
      mysqli_stmt_execute($stmt);

      $result = mysqli_stmt_get_result($stmt);

      if ($result && mysqli_num_rows($result) > 0) {

        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {

          if ((int)$user['is_active'] === 1) {

            session_regenerate_id(true);
            $_SESSION['login_attempt'] = 0;

            $_SESSION['login'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = htmlspecialchars($user['name']);
            $_SESSION['role'] = htmlspecialchars($user['role']);

            header("Location: index.php");
            exit;
          } else {
            $error = "Akun tidak aktif.";
          }
        } else {
          $_SESSION['login_attempt']++;
          $error = "Email atau password salah.";
        }
      } else {
        $_SESSION['login_attempt']++;
        $error = "Email atau password salah.";
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
    @font-face {
      font-family: 'FuturaHeavyOblique';
      src: url('assets/font/FuturaHeavyOblique.ttf') format('truetype');
    }

    body {
      background: url("assets/img/Login-Background.jpg") no-repeat center center fixed;
      background-size: cover;
      background-attachment: fixed;
    }

    body::before {
      content: "";
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.55);
      z-index: -1;
    }

    .logo-text {
      font-family: 'FuturaHeavyOblique';
      font-size: 32px;
      font-style: italic;
      font-weight: 900;
      color: #ffffff !important;
      letter-spacing: 1px;
      margin-left: 8px;
    }

    .credits a {
      font-family: 'Goudy Old Style', serif;
      font-weight: 900;
      color: #ffffff !important;
      text-decoration: none;
    }

    .card {
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
      backdrop-filter: blur(2px);
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
                <a href="login.php" class="logo d-flex align-items-center w-auto">
                  <img src="assets/img/Street Mile Logo login1.png" alt="Logo">
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
                      <?php echo htmlspecialchars($error); ?>
                    </div>
                  <?php endif; ?>

                  <form method="POST" class="row g-3">

                    <div class="col-12">
                      <label class="form-label">Email</label>
                      <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="col-12">
                      <label class="form-label">Password</label>
                      <input type="password" name="password" class="form-control" required>
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
                <a href="https://www.instagram.com/axelwavehassle/" target="_blank">
                  Axel Indra Yudha
                </a>
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