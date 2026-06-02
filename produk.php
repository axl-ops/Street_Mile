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
| CSRF TOKEN
|--------------------------------------------------------------------------
*/
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| QUERY PRODUK
|--------------------------------------------------------------------------
*/
$sql = mysqli_query(
  $conn,
  "SELECT
        p.*,
        c.category_name
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.id
    ORDER BY p.id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Produk - Street Mile</title>
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
          <i class="bi bi-tags"></i>
          <span>Kategori Produk</span>
        </a>
      </li><!-- End Profile Page Nav -->

      <li class="nav-item">
        <a class="nav-link " href="produk.php">
          <i class="bi bi-box-seam"></i>
          <span>Data Produk</span>
        </a>
      </li><!-- End Data Produk Page Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" href="laporan.php">
          <i class="bi bi-bar-chart-line"></i>
          <span>Laporan</span>
        </a>
      </li><!-- End Laporan Page Nav -->

      <li class="nav-item">
        <a class="nav-link collapsed" href="users.php">
          <i class="bi bi-people"></i>
          <span>Manajemen User</span>
        </a>
      </li><!-- End Register Page Nav -->
    </ul>

  </aside><!-- End Sidebar-->

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Produk</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
          <li class="breadcrumb-item active">Produk</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body mt-3">
            <a href="t_produk.php" class="btn btn-primary">Tambah Data</a>
            <a href="stok.php" class="btn btn-dark">Stok</a>
          </div>
        </div>
      </div>
    </div>
    <section class="section">
      <div class="row">
        <div class="col-lg-12">

          <div class="card">
            <div class="card-body mt-3">
              <!-- Table with stripped rows -->
              <table class="table datatable">
                <thead>
                  <tr>
                    <th scope="col">No</th>
                    <th scope="col">Kode Produk</th>
                    <th scope="col">Nama Produk</th>
                    <th scope="col">Kategori</th>
                    <th scope="col">stok</th>
                    <th scope="col">Harga</th>
                    <th scope="col">Gambar</th>
                    <th scope="col">Aksi</th>
                  </tr>
                </thead>
                <tbody>

                  <?php if ($sql instanceof mysqli_result && mysqli_num_rows($sql) > 0): ?>

                    <?php $no = 1; ?>

                    <?php while ($data = mysqli_fetch_assoc($sql)): ?>

                      <tr>

                        <td>
                          <?= (int) $no++; ?>
                        </td>

                        <td>
                          <?= htmlspecialchars($data['product_code'], ENT_QUOTES, 'UTF-8'); ?>
                        </td>

                        <td>
                          <?= htmlspecialchars($data['product_name'], ENT_QUOTES, 'UTF-8'); ?>
                        </td>

                        <td>
                          <?= htmlspecialchars($data['category_name'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                        </td>

                        <td>
                          <?= (int) $data['stock']; ?>
                        </td>

                        <td>
                          Rp <?= number_format((float) $data['price'], 0, ',', '.'); ?>
                        </td>

                        <td>

                          <?php
                          $gambar = (!empty($data['gambar']) && file_exists('produk_img/' . $data['gambar']))
                            ? 'produk_img/' . $data['gambar']
                            : 'assets/img/no-image.png';
                          ?>

                          <img
                            src="<?= htmlspecialchars($gambar, ENT_QUOTES, 'UTF-8'); ?>"
                            width="50"
                            class="rounded"
                            alt="Produk">

                        </td>

                        <td>

                          <!-- EDIT -->
                          <a
                            href="e_produk.php?id=<?= (int) $data['id']; ?>"
                            class="btn btn-warning btn-sm">
                            Edit
                          </a>

                          <!-- DELETE (FIXED: POST + CSRF + NO ERROR) -->
                          <form method="POST" action="h_produk.php" style="display:inline-block;">

                            <input type="hidden" name="id" value="<?= (int) $data['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

                            <button type="submit"
                              class="btn btn-danger btn-sm"
                              onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">

                              Hapus
                            </button>

                          </form>

                        </td>

                      </tr>

                    <?php endwhile; ?>
                  <?php else: ?>

                    <tr>
                      <td colspan="8" class="text-center text-muted">
                        Data produk tidak tersedia
                      </td>
                    </tr>

                  <?php endif; ?>

                </tbody>
              </table>
              <!-- End Table with stripped rows -->

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