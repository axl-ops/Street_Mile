<?php

declare(strict_types=1);

session_start();
require_once 'koneksi.php';

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
function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

/* =========================
   VALID ID CHECK
========================= */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: produk.php?error=invalid_id");
    exit;
}

/* =========================
   GET PRODUCT DATA (SAFE)
========================= */
$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$hasil = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$hasil) {
    header("Location: produk.php?error=not_found");
    exit;
}

/* =========================
   UPDATE HANDLER
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {

    $nm_produk   = trim($_POST['nm_produk'] ?? '');
    $stok        = (int)($_POST['stok'] ?? 0);
    $min_stok    = (int)($_POST['min_stok'] ?? 0);
    $harga       = (float)($_POST['harga'] ?? 0);
    $id_kategori = (int)($_POST['id_kategori'] ?? 0);

    if ($nm_produk === '' || $id_kategori <= 0) {
        header("Location: e_produk.php?id=$id&error=empty");
        exit;
    }

    /* =========================
       IMAGE HANDLING
    ========================= */
    $imgfile = $_FILES['gambar']['name'] ?? '';

    if (!empty($imgfile)) {

        $tmp = $_FILES['gambar']['tmp_name'];
        $ext = strtolower(pathinfo($imgfile, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            header("Location: e_produk.php?id=$id&error=img_format");
            exit;
        }

        $imgnew = md5(time() . $imgfile) . "." . $ext;
        move_uploaded_file($tmp, "produk_img/" . $imgnew);

        $stmt = mysqli_prepare($conn, "
            UPDATE products 
            SET category_id=?, product_name=?, stock=?, min_stock=?, price=?, gambar=? 
            WHERE id=?
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "isidisi",
            $id_kategori,
            $nm_produk,
            $stok,
            $min_stok,
            $harga,
            $imgnew,
            $id
        );
    } else {

        $stmt = mysqli_prepare($conn, "
            UPDATE products 
            SET category_id=?, product_name=?, stock=?, min_stock=?, price=? 
            WHERE id=?
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "isidii",
            $id_kategori,
            $nm_produk,
            $stok,
            $min_stok,
            $harga,
            $id
        );
    }

    /* =========================
       EXECUTE
    ========================= */
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header("Location: produk.php?success=1");
        exit;
    } else {
        error_log("Update produk error: " . mysqli_error($conn));
        mysqli_stmt_close($stmt);
        header("Location: produk.php?error=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>Data Produk - Street Mile</title>
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
        html,
        body {
            font-family: Helvetica, Arial, sans-serif !important;
        }

        body * {
            font-family: inherit !important;
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
                <a class="nav-link " href="index.php">
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
            <h1>Data Produk</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item">Data Produk</li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->
        <section class="section">
            <div class="row">

                <div class="col-lg-6">

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Edit Data Produk</h5>

                            <!-- Vertical Form -->
                            <form class="row g-3" method="post" enctype="multipart/form-data">
                                <div class="col-12">
                                    <label for="kd_produk" class="form-label">Kode Produk</label>
                                    <input type="text" class="form-control" id="kd_produk" name="kd_produk" value="<?php echo $hasil['product_code']; ?>" readonly>
                                </div>

                                <div class="col-12">
                                    <label for="nm_produk" class="form-label">Nama Produk</label>
                                    <input type="text" class="form-control" id="nm_produk" name="nm_produk" value="<?php echo $hasil['product_name']; ?>" required>
                                </div>

                                <div class="col-12">
                                    <label for="stok" class="form-label">Stok</label>
                                    <input type="number" class="form-control" id="stok" name="stok" value="<?php echo $hasil['stock']; ?>" required>
                                </div>

                                <div class="col-12">
                                    <label for="min_stok" class="form-label">Minimal Stok</label>
                                    <input type="number" class="form-control" id="min_stok" name="min_stok" value="<?php echo $hasil['min_stock']; ?>" required>
                                </div>

                                <div class="col-12">
                                    <label for="harga" class="form-label">Harga</label>
                                    <input type="number" class="form-control" id="harga" name="harga" value="<?php echo $hasil['price']; ?>" required>
                                </div>

                                <div class="col-12">
                                    <label for="id_kategori" class="form-label">Kategori</label>
                                    <select class="form-control" id="id_kategori" name="id_kategori" required>
                                        <?php
                                        $kategori = mysqli_query($conn, "SELECT * FROM categories");
                                        while ($k = mysqli_fetch_array($kategori)) {
                                            $selected = ($k['id'] == $hasil['category_id']) ? "selected" : "";
                                            echo "<option value='{$k['id']}' $selected>{$k['category_name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Gambar Lama</label><br>
                                    <img src="produk_img/<?php echo $hasil['gambar']; ?>" width="80">
                                </div>

                                <div class="col-12">
                                    <label for="gambar" class="form-label">Ganti Gambar</label>
                                    <input type="file" class="form-control" id="gambar" name="gambar" accept="image/*">
                                </div>

                                <div class="text-center">
                                    <button type="button" class="btn btn-warning">
                                        <a href="produk.php" style="color: black; text-decoration:none;">Kembali</a>
                                    </button>
                                    <button type="reset" class="btn btn-secondary">Reset</button>
                                    <button type="submit" class="btn btn-success" name="update">Update</button>
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
            <!-- All the links in the footer should remain intact. -->
            <!-- You can delete the links only if you purchased the pro version. -->
            <!-- Licensing information: https://bootstrapmade.com/license/ -->
            <!-- Purchase the pro version with working PHP/AJAX contact form: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/ -->
            Designed by <a href="https://bootstrapmade.com/">Axel Indra Yudha</a>
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