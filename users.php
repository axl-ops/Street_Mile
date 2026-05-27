<?php
session_start();

require_once 'koneksi.php';

// SECURITY HEADER
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

// CEK LOGIN
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {

  session_unset();
  session_destroy();

  header("Location: login.php");
  exit;
}

// SESSION TIMEOUT
$timeout = 1800;

if (isset($_SESSION['last_activity'])) {

  if ((time() - $_SESSION['last_activity']) > $timeout) {

    session_unset();
    session_destroy();

    header("Location: login.php?timeout=1");
    exit;
  }
}

$_SESSION['last_activity'] = time();

// VALIDASI USER ID
if (!isset($_SESSION['user_id'])) {

  session_unset();
  session_destroy();

  header("Location: login.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Manajemen User - Street Mile</title>
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
    /* =========================
       GLOBAL FONT OVERRIDE
    ========================= */

    :root {
      --app-font: Helvetica, Arial, sans-serif;
    }

    html,
    body {
      font-family: var(--app-font) !important;
      font-weight: 400;
      letter-spacing: 0.2px;
    }

    body * {
      font-family: var(--app-font) !important;
    }
    .logo span,
    .sidebar,
    .header,
    .nav-link,
    .dropdown-item,
    .card,
    .card-title,
    .table,
    .table th,
    .table td,
    .btn,
    input,
    select,
    textarea,
    label,
    .breadcrumb,
    .datatable-wrapper,
    .datatable-table,
    .datatable-input,
    .datatable-selector,
    .datatable-top,
    .datatable-bottom {
      font-family: var(--app-font) !important;
    }

    .logo span {
      font-family: var(--app-font) !important;
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
        <a class="nav-link collapsed" href="produk.php">
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
        <a class="nav-link " href="users.php">
          <i class="bi bi-people"></i>
          <span>Manajemen User</span>
        </a>
      </li><!-- End Register Page Nav -->
    </ul>

  </aside><!-- End Sidebar-->

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Manajemen User</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
          <li class="breadcrumb-item active">Manajemen User</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row">
        <div class="col-lg-12">

          <div class="card">
            <div class="card-body mt-3">
              <a href="t_users.php" class="btn btn-primary"> Tambah Data</a>
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
                <table class="table table-borderless datatable" data-page-length="5">
                  <thead>
                    <tr>
                      <th scope="col">No</th>
                      <th scope="col">Nama</th>
                      <th scope="col">Email</th>
                      <th scope="col">Role</th>
                      <th scope="col">Status</th>
                      <th scope="col">Dibuat</th>
                      <th scope="col">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>

                    <?php

                    $no = 1;

                    $sql = mysqli_query(
                      $conn,
                      "SELECT 
      id,
      name,
      email,
      role,
      is_active,
      created_at
   FROM users
   ORDER BY created_at DESC"
                    );

                    ?>

                    <?php if ($sql instanceof mysqli_result && mysqli_num_rows($sql) > 0): ?>

                      <?php while ($data = mysqli_fetch_assoc($sql)): ?>

                        <tr>

                          <td>
                            <?= $no++; ?>
                          </td>

                          <td>
                            <?= htmlspecialchars($data['name']); ?>
                          </td>

                          <td>
                            <?= htmlspecialchars($data['email']); ?>
                          </td>

                          <td>
                            <?= htmlspecialchars(ucfirst($data['role'])); ?>
                          </td>

                          <td>

                            <?php if ((int) $data['is_active'] === 1): ?>

                              <span class="badge bg-success">
                                Aktif
                              </span>

                            <?php else: ?>

                              <span class="badge bg-danger">
                                Nonaktif
                              </span>

                            <?php endif; ?>

                          </td>

                          <td>
                            <?= date(
                              'd-m-Y H:i',
                              strtotime($data['created_at'])
                            ); ?>
                          </td>

                          <td>

                            <a
                              href="e_users.php?id=<?= (int) $data['id']; ?>"
                              class="btn btn-warning btn-sm">
                              Edit
                            </a>

                            <a
                              href="h_users.php?id=<?= (int) $data['id']; ?>"
                              class="btn btn-danger btn-sm"
                              onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')">
                              Hapus
                            </a>

                          </td>

                        </tr>

                      <?php endwhile; ?>

                    <?php else: ?>

                      <tr>

                        <td colspan="7" class="text-center text-muted">

                          Data user tidak tersedia

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