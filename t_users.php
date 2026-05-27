<?php

declare(strict_types=1);

/* =========================
   SESSION SECURITY
========================= */
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();
require_once 'koneksi.php';

/* =========================
   CSRF TOKEN
========================= */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* =========================
   SECURITY HEADERS
========================= */
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

/* =========================
   AUTH CHECK
========================= */
if (empty($_SESSION['login']) || empty($_SESSION['user_id'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

/* =========================
   SESSION TIMEOUT
========================= */
$timeout = 1800;

if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit;
}

$_SESSION['last_activity'] = time();

/* =========================
   XSS HELPER
========================= */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {

    /* =========================
       CSRF CHECK (WAJIB)
    ========================= */
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['flash_error'] = "Token tidak valid!";
        header("Location: users.php");
        exit;
    }

    /* =========================
       INPUT SANITIZATION
    ========================= */
    $name      = trim($_POST['name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $role      = $_POST['role'] ?? '';
    $is_active = (int)($_POST['is_active'] ?? 1);

    /* =========================
       VALIDASI
    ========================= */
    if ($name === '' || $email === '' || $password === '' || $role === '') {
        $_SESSION['flash_error'] = "Semua field wajib diisi!";
        header("Location: users.php");
        exit;
    }

    /* =========================
       CEK EMAIL DUPLIKAT
    ========================= */
    $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($check, "s", $email);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if (mysqli_stmt_num_rows($check) > 0) {
        mysqli_stmt_close($check);

        $_SESSION['flash_error'] = "Email sudah terdaftar!";
        header("Location: users.php");
        exit;
    }

    mysqli_stmt_close($check);

    /* =========================
       HASH PASSWORD
    ========================= */
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    /* =========================
       INSERT USER
    ========================= */
    $insert = mysqli_prepare(
        $conn,
        "INSERT INTO users (name, email, password, role, is_active)
         VALUES (?, ?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $insert,
        "ssssi",
        $name,
        $email,
        $password_hash,
        $role,
        $is_active
    );

    if (mysqli_stmt_execute($insert)) {
        $_SESSION['flash_success'] = "User berhasil ditambahkan!";
    } else {
        error_log("Insert user gagal: " . mysqli_error($conn));
        $_SESSION['flash_error'] = "Terjadi kesalahan sistem!";
    }

    mysqli_stmt_close($insert);

    header("Location: users.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>Manajemen Users - Street Mile</title>
    <meta content="" name="description">
    <meta content="" name="keywords">

    <!-- Favicons -->
    <link href="assets/img/Street Mile Logo.png" rel="icon">
    <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

    <!-- Google Fonts -->
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
    <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
    <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
    <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
    <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">

    <!-- Template Main CSS File -->
    <link href="assets/css/style.css" rel="stylesheet">
    <!-- =========================
     CUSTOM FONT
========================= -->
    <style>
        /* FORCE ALL ELEMENT FONT */
        body {
            font-family: Helvetica, Arial, sans-serif !important;
        }

        body * {
            font-family: inherit !important;
        }

        .logo span,
        .sidebar,
        .nav-link,
        .dropdown-item,
        .card,
        .card-title,
        table,
        th,
        td,
        input,
        select,
        textarea,
        button {
            font-family: inherit !important;
        }

        body {
            font-weight: 400;
            letter-spacing: 0.2px;
        }
    </style>

</head>

<body>

    <!-- ======= Header ======= -->
    <header id="header" class="header fixed-top d-flex align-items-center">

        <div class="d-flex align-items-center justify-content-between">
            <a href="index.php" class="logo d-flex align-items-center">
                <img src="assets/img/Street Mile Logo.png" alt="">
                <span class="d-none d-lg-block">Street Mile</span>
            </a>
            <i class="bi bi-list toggle-sidebar-btn"></i>
        </div><!-- End Logo -->

        <nav class="header-nav ms-auto">
            <ul class="d-flex align-items-center">
                <li class="nav-item dropdown pe-3">

                    <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
                        <img src="assets/img/Profile1.png" alt="Profile" class="rounded-circle">
                    </a><!-- End Profile Iamge Icon -->

                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
                        <li class="dropdown-header">
                            <?= htmlspecialchars($_SESSION['name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?>
                            <?= htmlspecialchars($_SESSION['role'] ?? 'Role', ENT_QUOTES, 'UTF-8') ?>
                        </li>
                        <li>
                            <hr class="dropdown-divider" />
                        </li>

                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Sign Out</span>
                            </a>
                        </li>
                    </ul><!-- End Profile Dropdown Items -->
                </li><!-- End Profile Nav -->
            </ul>
        </nav><!-- End Icons Navigation -->


    </header><!-- End Header -->

    <!-- ======= Sidebar ======= -->
    <aside id="sidebar" class="sidebar">

        <ul class="sidebar-nav" id="sidebar-nav">

            <li class="nav-item">
                <a class="nav-link collapsed" href="index.php">
                    <i class="bi bi-grid"></i>
                    <span>Dashboard</span>
                </a>
            </li><!-- End Dashboard Nav -->

            <li class="nav-item">
                <a class="nav-link collapsed" href="kategori_produk.php">
                    <i class="bi bi-person"></i>
                    <span>Kategori Produk</span>
                </a>
            </li><!-- End Profile Page Nav -->

            <li class="nav-item">
                <a class="nav-link collapsed" href="produk.php">
                    <i class="bi bi-question-circle"></i>
                    <span>Data Produk</span>
                </a>
            </li><!-- End F.A.Q Page Nav -->

            <li class="nav-item">
                <a class="nav-link collapsed" href="laporan.php">
                    <i class="bi bi-envelope"></i>
                    <span>Laporan</span>
                </a>
            </li><!-- End Contact Page Nav -->

            <li class="nav-item">
                <a class="nav-link collapsed" href="users.php">
                    <i class="bi bi-card-list"></i>
                    <span>Manajemen User</span>
                </a>
            </li><!-- End Register Page Nav -->
        </ul>

    </aside><!-- End Sidebar-->

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Manjemen User</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item">Manajemen User</li>
                    <li class="breadcrumb-item active">Tambah</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->
        <section class="section">
            <div class="row">
                <div class="col-lg-6">

                    <div class="card-body">
                        <h5 class="card-title">Tambah User</h5>

                        <form class="row g-3" method="post">

                            <!-- CSRF PROTECTION -->
                            <input type="hidden" name="csrf_token"
                                value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                            <!-- NAMA -->
                            <div class="col-12">
                                <label for="name" class="form-label">Nama</label>
                                <input type="text"
                                    class="form-control"
                                    id="name"
                                    name="name"
                                    required
                                    autocomplete="off">
                            </div>

                            <!-- EMAIL -->
                            <div class="col-12">
                                <label for="email" class="form-label">Email</label>
                                <input type="email"
                                    class="form-control"
                                    id="email"
                                    name="email"
                                    required
                                    autocomplete="off">
                            </div>

                            <!-- PASSWORD -->
                            <div class="col-12">
                                <label for="password" class="form-label">Password</label>
                                <input type="password"
                                    class="form-control"
                                    id="password"
                                    name="password"
                                    required
                                    autocomplete="new-password">
                            </div>

                            <!-- ROLE -->
                            <div class="col-12">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-control" name="role" required>
                                    <option value="">-- Pilih Role --</option>
                                    <option value="admin">Admin</option>
                                    <option value="staff">Staff</option>
                                </select>
                            </div>

                            <!-- STATUS -->
                            <div class="col-12">
                                <label for="is_active" class="form-label">Status</label>
                                <select class="form-control" name="is_active">
                                    <option value="1">Aktif</option>
                                    <option value="0">Nonaktif</option>
                                </select>
                            </div>

                            <!-- BUTTONS (CLEAN VERSION) -->
                            <div class="text-center">

                                <a href="users.php" class="btn btn-warning">
                                    Kembali
                                </a>

                                <button type="reset" class="btn btn-secondary">
                                    Reset
                                </button>

                                <button type="submit" class="btn btn-success" name="simpan">
                                    Simpan
                                </button>

                            </div>

                        </form>
                    </div>
                </div>
            </div>
            </div>
        </section>

    </main><!-- End #main -->

    <!-- ======= Footer ======= -->
    <footer id="footer" class="footer">
        <div class="copyright">
            &copy; Copyright <strong><span>Street Mile</span></strong>. All Rights Reserved
        </div>
        <div class="credits">
            Designed by <a href="">Axel Indra Yudha</a>
        </div>
    </footer><!-- End Footer -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/chart.js/chart.umd.js"></script>
    <script src="assets/vendor/echarts/echarts.min.js"></script>
    <script src="assets/vendor/quill/quill.min.js"></script>
    <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
    <script src="assets/vendor/tinymce/tinymce.min.js"></script>
    <script src="assets/vendor/php-email-form/validate.js"></script>

    <!-- Template Main JS File -->
    <script src="assets/js/main.js"></script>

</body>

</html>