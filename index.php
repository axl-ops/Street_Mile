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
| REGENERATE SESSION
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['initiated'])) {
  session_regenerate_id(true);
  $_SESSION['initiated'] = true;
}

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
header('Content-Security-Policy: default-src \'self\'; img-src \'self\' data:; style-src \'self\' \'unsafe-inline\'; script-src \'self\' \'unsafe-inline\';');

/*
|--------------------------------------------------------------------------
| ERROR REPORTING
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
$timeout = 1800; // 30 menit

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
| SAFE QUERY FUNCTION
|--------------------------------------------------------------------------
*/
function safeQuery(mysqli $conn, string $query)
{
  $result = mysqli_query($conn, $query);

  if (!$result) {
    error_log('Database Query Error: ' . mysqli_error($conn));
    return false;
  }

  return $result;
}

/*
|--------------------------------------------------------------------------
| DASHBOARD DATA
|--------------------------------------------------------------------------
*/

// Total Produk
$q_produk = safeQuery(
  $conn,
  "SELECT COUNT(*) AS total_produk FROM products"
);

$data_produk = mysqli_fetch_assoc($q_produk);

// Total Stok
$q_stok = safeQuery(
  $conn,
  "SELECT SUM(stock) AS total_stok FROM products"
);

$data_stok = mysqli_fetch_assoc($q_stok);

// Total Kategori
$q_kategori = safeQuery(
  $conn,
  "SELECT COUNT(*) AS total_kategori FROM categories"
);

$data_kategori = mysqli_fetch_assoc($q_kategori);

// Barang Masuk
$q_masuk = safeQuery(
  $conn,
  "SELECT 
        DAY(created_at) AS hari,
        SUM(qty) AS total
    FROM stock_logs
    WHERE change_type = 'ADD'
    AND MONTH(created_at) = MONTH(CURRENT_DATE())
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
    GROUP BY DAY(created_at)"
);

// Barang Keluar
$q_keluar = safeQuery(
  $conn,
  "SELECT 
        DAY(created_at) AS hari,
        SUM(qty) AS total
    FROM stock_logs
    WHERE change_type = 'REDUCE'
    AND MONTH(created_at) = MONTH(CURRENT_DATE())
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
    GROUP BY DAY(created_at)"
);

/*
|--------------------------------------------------------------------------
| DEFAULT CHART DATA
|--------------------------------------------------------------------------
*/
$masuk = array_fill(1, 31, 0);
$keluar = array_fill(1, 31, 0);

/*
|--------------------------------------------------------------------------
| PROCESS DATA MASUK
|--------------------------------------------------------------------------
*/
if ($q_masuk instanceof mysqli_result) {
  while ($row = mysqli_fetch_assoc($q_masuk)) {

    $hari = (int) $row['hari'];

    if ($hari >= 1 && $hari <= 31) {
      $masuk[$hari] = (int) $row['total'];
    }
  }
}

/*
|--------------------------------------------------------------------------
| PROCESS DATA KELUAR
|--------------------------------------------------------------------------
*/
if ($q_keluar instanceof mysqli_result) {
  while ($row = mysqli_fetch_assoc($q_keluar)) {

    $hari = (int) $row['hari'];

    if ($hari >= 1 && $hari <= 31) {
      $keluar[$hari] = (int) $row['total'];
    }
  }
}

/*
|--------------------------------------------------------------------------
| PRODUK TERBARU
|--------------------------------------------------------------------------
*/
$query = safeQuery(
  $conn,
  "SELECT 
      p.product_name,
      p.stock,
      c.category_name
   FROM products p
   INNER JOIN categories c
      ON p.category_id = c.id
   ORDER BY p.created_at DESC
   LIMIT 5"
);

/*
|--------------------------------------------------------------------------
| STOK MENIPIS
|--------------------------------------------------------------------------
*/
$q_menipis = safeQuery(
  $conn,
  "SELECT 
      product_name,
      stock,
      min_stock
   FROM products
   WHERE stock <= min_stock
   ORDER BY stock ASC
   LIMIT 5"
);

/*
|--------------------------------------------------------------------------
| AKTIVITAS STOK
|--------------------------------------------------------------------------
*/
$q_aktivitas = safeQuery(
  $conn,
  "SELECT 
      sl.*,
      p.product_name,
      u.name AS user_name
   FROM stock_logs sl
   INNER JOIN products p
      ON sl.product_id = p.id
   INNER JOIN users u
      ON sl.created_by = u.id
   ORDER BY sl.created_at DESC
   LIMIT 5"
);

/*
|--------------------------------------------------------------------------
| FORMAT WAKTU
|--------------------------------------------------------------------------
*/
function waktu_lalu(string $datetime): string
{
  $selisih = time() - strtotime($datetime);

  if ($selisih < 0) {
    $selisih = 0;
  }

  $menit = floor($selisih / 60);
  $jam   = floor($selisih / 3600);
  $hari  = floor($selisih / 86400);

  if ($menit < 60) {
    return $menit . ' menit yang lalu';
  }

  if ($jam < 24) {
    return $jam . ' jam yang lalu';
  }

  return $hari . ' hari yang lalu';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Dashboard - Street Mile</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="assets/img/Street Mile Logo.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

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

  <!-- Custom Typography -->
  <style>
    :root {
      --main-font: Helvetica, Arial, sans-serif;
    }

    body {
      font-family: var(--main-font);
      font-weight: 400;
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
    .activity-content,
    .activity-label,
    .datatable-wrapper,
    .datatable-table,
    .datatable-input,
    .datatable-selector {
      font-family: inherit;
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
          </a><!-- End Profile Image Icon -->

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
            <li class="dropdown-header">
              <h6><?php echo isset($_SESSION['name']) ? $_SESSION['name'] : 'User'; ?></h6>
              <span><?php echo isset($_SESSION['role']) ? $_SESSION['role'] : 'Role'; ?></span>
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
      <h1>Dashboard</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item active">Dashboard</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section dashboard">
      <div class="row">

        <!-- Left side columns -->
        <div class="col-lg-8">
          <div class="row">

            <!-- TOTAL PRODUK -->
            <div class="col-xxl-4 col-md-6">
              <div class="card info-card">
                <div class="card-body">
                  <h5 class="card-title">Produk <span>| Total</span></h5>

                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-box-seam"></i>
                    </div>
                    <div class="ps-3">
                      <h6><?= $data_produk['total_produk']; ?></h6>
                      <span class="text-muted small pt-2 ps-1">Total Produk</span>
                    </div>
                  </div>

                </div>
              </div>
            </div>

            <!-- TOTAL STOK -->
            <div class="col-xxl-4 col-md-6">
              <div class="card info-card">
                <div class="card-body">
                  <h5 class="card-title">Stok <span>| Total</span></h5>

                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-stack"></i>
                    </div>
                    <div class="ps-3">
                      <h6><?= $data_stok['total_stok'] ?? 0; ?></h6>
                      <span class="text-muted small pt-2 ps-1">Total Stok</span>
                    </div>
                  </div>

                </div>
              </div>
            </div>

            <!-- TOTAL KATEGORI -->
            <div class="col-xxl-4 col-md-6">
              <div class="card info-card">
                <div class="card-body">
                  <h5 class="card-title">Kategori <span>| Total</span></h5>

                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-tags"></i>
                    </div>
                    <div class="ps-3">
                      <h6><?= $data_kategori['total_kategori'] ?? 0; ?></h6>
                      <span class="text-muted small pt-2 ps-1">Total Kategori</span>
                    </div>
                  </div>

                </div>
              </div>
            </div>

            <!-- REPORT / GRAFIK -->
            <div class="col-12">
              <div class="card">

                <div class="filter">
                  <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
                  <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <li class="dropdown-header text-start">
                      <h6>Filter</h6>
                    </li>

                    <li><a class="dropdown-item" href="#">Hari Ini</a></li>
                    <li><a class="dropdown-item" href="#">Bulan Ini</a></li>
                    <li><a class="dropdown-item" href="#">Tahun Ini</a></li>
                  </ul>
                </div>

                <div class="card-body">
                  <h5 class="card-title">Laporan Barang <span>| Bulan Ini</span></h5>

                  <div id="reportsChart"></div>

                  <script>
                    document.addEventListener("DOMContentLoaded", () => {

                      const dataMasuk = <?= json_encode(array_values($masuk)); ?>;
                      const dataKeluar = <?= json_encode(array_values($keluar)); ?>;

                      new ApexCharts(document.querySelector("#reportsChart"), {
                        series: [{
                            name: "Barang Masuk",
                            data: dataMasuk
                          },
                          {
                            name: "Barang Keluar",
                            data: dataKeluar
                          }
                        ],
                        chart: {
                          height: 350,
                          type: 'area',
                          toolbar: {
                            show: false
                          }
                        },
                        markers: {
                          size: 4
                        },
                        colors: ['#4154f1', '#ff771d'],
                        fill: {
                          type: "gradient",
                          gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.3,
                            opacityTo: 0.4,
                            stops: [0, 90, 100]
                          }
                        },
                        dataLabels: {
                          enabled: false
                        },
                        stroke: {
                          curve: 'smooth',
                          width: 2
                        },
                        xaxis: {
                          categories: [...Array(31).keys()].map(i => i + 1)
                        },
                        tooltip: {
                          x: {
                            format: 'dd/MM/yy'
                          },
                        }
                      }).render();

                    });
                  </script>
                </div>
              </div>
            </div>

            <!-- PRODUK TERBARU -->
            <div class="col-12">
              <div class="card recent-sales">

                <div class="card-body">
                  <h5 class="card-title">Produk Terbaru <span>| Latest</span></h5>

                  <table class="table table-borderless datatable" data-page-length="5">
                    <thead>
                      <tr>
                        <th scope="col">#</th>
                        <th scope="col">Produk</th>
                        <th scope="col">Kategori</th>
                        <th scope="col">Stok</th>
                      </tr>
                    </thead>

                    <tbody>

                      <?php if ($query instanceof mysqli_result && mysqli_num_rows($query) > 0): ?>

                        <?php $no = 1; ?>

                        <?php while ($row = mysqli_fetch_assoc($query)): ?>

                          <tr>
                            <th><?= $no++; ?></th>

                            <td>
                              <?= htmlspecialchars($row['product_name']); ?>
                            </td>

                            <td>
                              <?= htmlspecialchars($row['category_name']); ?>
                            </td>

                            <td>
                              <?= (int) $row['stock']; ?>
                            </td>
                          </tr>

                        <?php endwhile; ?>

                      <?php else: ?>

                        <tr>
                          <td colspan="4" class="text-center text-muted">
                            Data produk tidak tersedia
                          </td>
                        </tr>

                      <?php endif; ?>

                    </tbody>
                  </table>
                </div>

              </div>
            </div>

          </div>
        </div><!-- End Left side columns -->
        <!-- RIGHT SIDE -->
        <div class="col-lg-4">

          <!-- STOK MENIPIS -->
          <div class="card top-selling overflow-auto">
            <div class="card-body pb-0">
              <h5 class="card-title">Stok Menipis <span>| Warning</span></h5>

              <table class="table table-borderless datatable">
                <thead>
                  <tr>
                    <th scope="col">Produk</th>
                    <th scope="col">Stok</th>
                    <th scope="col">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $no = 1;
                  while ($row = mysqli_fetch_assoc($q_menipis)) :
                  ?>
                    <tr>
                      <td><?= $row['product_name']; ?></td>
                      <td><?= $row['stock']; ?></td>
                      <td><?php if ($row['stock'] == 0): ?>
                          <span class="badge bg-danger">Habis</span>
                        <?php elseif ($row['stock'] <= ($row['min_stock'] / 2)): ?>
                          <span class="badge bg-danger">Hampir Habis</span>
                        <?php else: ?>
                          <span class="badge bg-warning">Menipis</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>

            </div>

          </div>

          <!-- AKTIVITAS -->
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Aktifitas Barang</h5>

              <div class="activity">

                <?php while ($row = mysqli_fetch_assoc($q_aktivitas)) :

                  if ($row['change_type'] == 'ADD') {
                    $text = 'Penambahan stok';
                    $color = 'text-success';
                  } elseif ($row['change_type'] == 'REDUCE') {
                    $text = 'Pengeluaran stok';
                    $color = 'text-danger';
                  } else {
                    $text = 'Perubahan stok';
                    $color = 'text-secondary';
                  }

                ?>

                  <div class="activity-item d-flex">

                    <div class="activity-label">
                      <?= waktu_lalu($row['created_at']); ?>
                    </div>
                    <i class="bi bi-circle-fill activity-badge <?= $color ?> align-self-start"></i>
                    <div class="activity-content">
                      <?= $text; ?>
                      <span class="fw-bold text-dark">
                        "<?= $row['product_name']; ?>"
                      </span>
                    </div>

                  </div>
                <?php endwhile; ?>
              </div>
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