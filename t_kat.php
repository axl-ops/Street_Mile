<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SESSION SECURITY
|--------------------------------------------------------------------------
*/
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/
require_once 'koneksi.php';

/*
|--------------------------------------------------------------------------
| TIMEZONE
|--------------------------------------------------------------------------
*/
date_default_timezone_set('Asia/Jakarta');

/*
|--------------------------------------------------------------------------
| SECURITY HEADERS
|--------------------------------------------------------------------------
*/
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-XSS-Protection: 1; mode=block');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline';");

/*
|--------------------------------------------------------------------------
| MYSQL ERROR MODE
|--------------------------------------------------------------------------
*/
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/*
|--------------------------------------------------------------------------
| SESSION VALIDATION
|--------------------------------------------------------------------------
*/
if (
    empty($_SESSION['login']) ||
    $_SESSION['login'] !== true ||
    empty($_SESSION['user_id'])
) {

    session_unset();
    session_destroy();

    header('Location: login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| SESSION TIMEOUT
|--------------------------------------------------------------------------
*/
$timeout = 1800;

if (
    isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity']) > $timeout
) {

    session_unset();
    session_destroy();

    header('Location: login.php?timeout=1');
    exit;
}

$_SESSION['last_activity'] = time();

/*
|--------------------------------------------------------------------------
| GENERATE KODE KATEGORI
|--------------------------------------------------------------------------
*/
$kd_kat = 'K001';

$queryKode = mysqli_query(
    $conn,
    "SELECT MAX(kd_kat) AS max_code FROM categories"
);

if ($queryKode instanceof mysqli_result) {

    $hasil = mysqli_fetch_assoc($queryKode);

    if (!empty($hasil['max_code'])) {

        $urutan = (int) substr($hasil['max_code'], 1);

        $urutan++;

        $kd_kat = 'K' . sprintf('%03d', $urutan);
    }
}

/*
|--------------------------------------------------------------------------
| SIMPAN DATA
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nm_kat = trim($_POST['nm_kat'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | VALIDASI INPUT
    |--------------------------------------------------------------------------
    */
    if ($nm_kat === '') {

        echo "<script>alert('Nama kategori wajib diisi!');</script>";
    } elseif (mb_strlen($nm_kat) > 100) {

        echo "<script>alert('Nama kategori terlalu panjang!');</script>";
    } else {

        /*
        |--------------------------------------------------------------------------
        | PREPARED STATEMENT
        |--------------------------------------------------------------------------
        */
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO categories (kd_kat, category_name)
             VALUES (?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            'ss',
            $kd_kat,
            $nm_kat
        );

        $simpan = mysqli_stmt_execute($stmt);

        mysqli_stmt_close($stmt);

        if ($simpan) {

            header('Location: kategori_produk.php?success=1');
            exit;
        } else {

            echo "<script>alert('Data gagal ditambahkan!');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>Kategori Produk - Street Mile</title>
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
    <style>
        :root {
            --main-font: Helvetica, Arial, sans-serif;
        }

        body {
            font-family: var(--main-font);
            letter-spacing: 0.2px;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        p,
        a,
        li,
        table,
        th,
        td,
        button,
        input,
        select,
        textarea,
        label,
        span,
        .card-title,
        .nav-link,
        .dropdown-item,
        .breadcrumb,
        .datatable-wrapper,
        .datatable-table,
        .datatable-input,
        .datatable-selector {
            font-family: inherit;
        }

        .logo span {
            font-family: Helvetica, Arial, sans-serif !important;
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
                            <h6>
                                <?= htmlspecialchars($_SESSION['name'] ?? 'User'); ?>
                            </h6>

                            <span>
                                <?= htmlspecialchars($_SESSION['role'] ?? 'Role'); ?>
                            </span>
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

    <?php
    $currentPage = basename($_SERVER['PHP_SELF']);
    ?>

    <!-- ======= Sidebar ======= -->
    <aside id="sidebar" class="sidebar">

        <ul class="sidebar-nav" id="sidebar-nav">

            <li class="nav-item">
                <a class="nav-link <?= ($currentPage === 'index.php') ? '' : 'collapsed'; ?>" href="index.php">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= ($currentPage === 'kategori_produk.php') ? '' : 'collapsed'; ?>" href="kategori_produk.php">
                    <i class="bi bi-tags"></i>
                    <span>Kategori Produk</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= ($currentPage === 'produk.php') ? '' : 'collapsed'; ?>" href="produk.php">
                    <i class="bi bi-box-seam"></i>
                    <span>Data Produk</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= ($currentPage === 'laporan.php') ? '' : 'collapsed'; ?>" href="laporan.php">
                    <i class="bi bi-bar-chart-line"></i>
                    <span>Laporan</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= ($currentPage === 'users.php') ? '' : 'collapsed'; ?>" href="users.php">
                    <i class="bi bi-people"></i>
                    <span>Manajemen User</span>
                </a>
            </li>

        </ul>

    </aside><!-- End Sidebar -->

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Kategori Produk</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item">Kategori Produk</li>
                    <li class="breadcrumb-item active">Tambah</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->
        <section class="section">
            <div class="row">

                <div class="col-lg-6">

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Tambah Kategori Produk</h5>

                            <!-- Vertical Form -->
                            <form class="row g-3" method="post">
                                <div class="col-12">
                                    <label for="kd_kat" class="form-label">Kode Kategori</label>
                                    <input type="text" class="form-control" id="kd_kat" name="kd_kat" value="<?php echo $kd_kat; ?>" readonly>
                                </div>
                                <div class="col-12">
                                    <label for="nm_kat" class="form-label">Nama Kategori</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="nm_kat"
                                        name="nm_kat"
                                        maxlength="100"
                                        autocomplete="off"
                                        required>
                                </div>
                                <div class="text-center">
                                    <a href="kategori_produk.php" class="btn btn-warning text-dark text-decoration-none">
                                        Kembali
                                    </a>
                                    <button type="reset" class="btn btn-secondary">Reset</button>
                                    <button type="submit" class="btn btn-success" name="simpan">Simpan</button>
                                </div>
                            </form><!-- Vertical Form -->

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
            <!-- All the links in the footer should remain intact. -->
            <!-- You can delete the links only if you purchased the pro version. -->
            <!-- Licensing information: https://bootstrapmade.com/license/ -->
            <!-- Purchase the pro version with working PHP/AJAX contact form: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/ -->
            Designed by <a href="https://www.instagram.com/axelwavehassle/">Axel Indra Yudha</a>
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