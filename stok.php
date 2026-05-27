<?php
session_start();

require_once 'koneksi.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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

if (isset($_POST['submit'])) {

  /*
    |--------------------------------------------------------------------------
    | VALIDASI INPUT
    |--------------------------------------------------------------------------
    */
  $product_id = (int) ($_POST['product_id'] ?? 0);

  $change_type = trim($_POST['change_type'] ?? '');

  $qty = (int) ($_POST['qty'] ?? 0);

  $note = trim($_POST['note'] ?? '');

  $user_id = (int) ($_SESSION['user_id'] ?? 0);

  /*
    |--------------------------------------------------------------------------
    | VALIDASI DASAR
    |--------------------------------------------------------------------------
    */
  if (
    $product_id <= 0 ||
    $qty <= 0 ||
    !in_array($change_type, ['ADD', 'REDUCE'], true)
  ) {

    echo "<script>alert('Data tidak valid!');</script>";
  } else {

    /*
        |--------------------------------------------------------------------------
        | AMBIL STOK PRODUK
        |--------------------------------------------------------------------------
        */
    $stmt = mysqli_prepare(
      $conn,
      "SELECT stock FROM products WHERE id = ? LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, "i", $product_id);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $data = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    /*
        |--------------------------------------------------------------------------
        | VALIDASI PRODUK
        |--------------------------------------------------------------------------
        */
    if (!$data) {

      echo "<script>alert('Produk tidak ditemukan!');</script>";
    } else {

      $stock_before = (int) $data['stock'];

      /*
            |--------------------------------------------------------------------------
            | HITUNG STOK BARU
            |--------------------------------------------------------------------------
            */
      if ($change_type === 'ADD') {

        $stock_after = $stock_before + $qty;
      } else {

        $stock_after = $stock_before - $qty;

        if ($stock_after < 0) {

          echo "<script>alert('Stok tidak cukup!');</script>";

          exit;
        }
      }

      /*
            |--------------------------------------------------------------------------
            | UPDATE STOK
            |--------------------------------------------------------------------------
            */
      $stmtUpdate = mysqli_prepare(
        $conn,
        "UPDATE products
                 SET stock = ?
                 WHERE id = ?"
      );

      mysqli_stmt_bind_param(
        $stmtUpdate,
        "ii",
        $stock_after,
        $product_id
      );

      mysqli_stmt_execute($stmtUpdate);

      mysqli_stmt_close($stmtUpdate);

      /*
            |--------------------------------------------------------------------------
            | INSERT LOG STOK
            |--------------------------------------------------------------------------
            */
      $stmtLog = mysqli_prepare(
        $conn,
        "INSERT INTO stock_logs
                (
                    product_id,
                    change_type,
                    qty,
                    stock_before,
                    stock_after,
                    note,
                    created_by
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)"
      );

      mysqli_stmt_bind_param(
        $stmtLog,
        "isiiisi",
        $product_id,
        $change_type,
        $qty,
        $stock_before,
        $stock_after,
        $note,
        $user_id
      );

      mysqli_stmt_execute($stmtLog);

      mysqli_stmt_close($stmtLog);

      header("Location: stok.php?success=1");
      exit;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Stock - Street Mile</title>
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
    body,
    button,
    input,
    select,
    textarea,
    .table,
    .card,
    .nav-link,
    .dropdown-menu,
    .badge,
    .btn,
    .pagetitle,
    .breadcrumb,
    .header,
    .sidebar,
    .footer {
      font-family: Helvetica, Arial, sans-serif !important;
    }

    /* Logo kiri atas */
    .logo span {
      font-family: Helvetica, Arial, sans-serif !important;
    }
  </style>
</head>

<body>
  <?php if (isset($_GET['success'])): ?>
    <script>
      alert('Stok berhasil diperbarui!');
    </script>
  <?php endif; ?>
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
      <h1>Manajemen Stok</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="produk.php">Data Produk</a></li>
          <li class="breadcrumb-item active">Manajemen Stok</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section">
      <div class="row">
        <!-- FORM MANAJEMEN STOK -->
        <div class="col-lg-6">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Manajemen Stok</h5>
              <form method="post">
                <div class="mb-3">
                  <label class="form-label">Pilih Produk</label>
                  <select name="product_id" class="form-select" required>
                    <option value="">-- Pilih Produk --</option>
                    <?php
                    $produk = mysqli_query(
                      $conn,
                      "SELECT id, product_name
     FROM products
     ORDER BY product_name ASC"
                    );

                    while ($p = mysqli_fetch_assoc($produk)):
                    ?>

                      <option value="<?= (int) $p['id']; ?>">
                        <?= htmlspecialchars($p['product_name']); ?>
                      </option>

                    <?php endwhile; ?>
                    ?>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Jenis Aksi</label>
                  <select name="change_type" class="form-select">
                    <option value="ADD">Tambah Stok</option>
                    <option value="REDUCE">Kurangi Stok</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Jumlah</label>
                  <input type="number" name="qty" class="form-control" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Catatan</label>
                  <textarea name="note" class="form-control" rows="2"></textarea>
                </div>
                <button type="submit" name="submit" class="btn btn-primary">Simpan Perubahan</button>
              </form>
            </div>
          </div>
        </div>

        <!-- RIWAYAT STOK -->
        <div class="col-lg-6">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Riwayat Stok</h5>

              <table class="table table-striped">
                <thead>
                  <tr>
                    <th scope="col">Tanggal</th>
                    <th scope="col">Produk</th>
                    <th scope="col">Aksi</th>
                    <th scope="col">Qty</th>
                    <th scope="col">User</th>
                  </tr>
                </thead>
                <tbody>
                  <?php

                  $query = mysqli_query(
                    $conn,
                    "SELECT
        sl.*,
        p.product_name,
        u.name
     FROM stock_logs sl
     INNER JOIN products p
        ON sl.product_id = p.id
     INNER JOIN users u
        ON sl.created_by = u.id
     ORDER BY sl.created_at DESC"
                  );

                  if ($query instanceof mysqli_result && mysqli_num_rows($query) > 0):

                    while ($row = mysqli_fetch_assoc($query)):

                      $badge = $row['change_type'] === 'ADD'
                        ? "<span class='badge bg-success'>+ (ADD)</span>"
                        : "<span class='badge bg-danger'>- (REDUCE)</span>";
                  ?>

                      <tr>

                        <td>
                          <?= date('d M Y H:i', strtotime($row['created_at'])); ?>
                        </td>

                        <td>
                          <?= htmlspecialchars($row['product_name']); ?>
                        </td>

                        <td>
                          <?= $badge; ?>
                        </td>

                        <td>
                          <?= (int) $row['qty']; ?>
                        </td>

                        <td>
                          <?= htmlspecialchars($row['name']); ?>
                        </td>

                      </tr>

                    <?php
                    endwhile;

                  else:
                    ?>

                    <tr>
                      <td colspan="5" class="text-center text-muted">
                        Riwayat stok tidak tersedia
                      </td>
                    </tr>

                  <?php endif; ?>
                  ?>
                </tbody>
              </table>
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