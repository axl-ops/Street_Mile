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

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/*
|--------------------------------------------------------------------------
| GENERATE PRODUCT CODE
|--------------------------------------------------------------------------
*/
$auto = mysqli_query(
    $conn,
    "SELECT MAX(product_code) AS max_code FROM products"
);

$hasil = mysqli_fetch_assoc($auto);

$code = $hasil['max_code'] ?? null;

if ($code === null) {

    $urutan = 0;
} else {

    $urutan = (int) substr($code, 1, 3);
}

$urutan++;

$kd_produk = 'P' . sprintf('%03d', $urutan);

/*
|--------------------------------------------------------------------------
| INSERT PRODUK
|--------------------------------------------------------------------------
*/
if (isset($_POST['simpan'])) {

    /*
    |--------------------------------------------------------------------------
    | VALIDASI INPUT
    |--------------------------------------------------------------------------
    */
    $nm_produk = trim($_POST['nm_produk'] ?? '');
    $stok = (int) ($_POST['stok'] ?? 0);
    $min_stok = (int) ($_POST['min_stok'] ?? 0);
    $harga = (float) ($_POST['harga'] ?? 0);
    $id_kategori = (int) ($_POST['id_kategori'] ?? 0);

    if (
        empty($nm_produk) ||
        $stok < 0 ||
        $min_stok < 0 ||
        $harga < 0 ||
        $id_kategori <= 0
    ) {

        echo "<script>alert('Data tidak valid!');</script>";
    } else {

        /*
        |--------------------------------------------------------------------------
        | VALIDASI GAMBAR
        |--------------------------------------------------------------------------
        */
        if (
            !isset($_FILES['gambar']) ||
            $_FILES['gambar']['error'] !== UPLOAD_ERR_OK
        ) {

            echo "<script>alert('Upload gambar gagal!');</script>";
        } else {

            $imgfile = $_FILES['gambar']['name'];
            $tmp_file = $_FILES['gambar']['tmp_name'];
            $file_size = $_FILES['gambar']['size'];

            $extension = strtolower(
                pathinfo($imgfile, PATHINFO_EXTENSION)
            );

            $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

            /*
            |--------------------------------------------------------------------------
            | VALIDASI EXTENSION
            |--------------------------------------------------------------------------
            */
            if (!in_array($extension, $allowed_extensions, true)) {

                echo "<script>alert('Format gambar tidak valid!');</script>";
            }
            /*
            |--------------------------------------------------------------------------
            | VALIDASI UKURAN FILE
            |--------------------------------------------------------------------------
            */ elseif ($file_size > 2 * 1024 * 1024) {

                echo "<script>alert('Ukuran gambar maksimal 2MB!');</script>";
            } else {

                /*
                |--------------------------------------------------------------------------
                | GENERATE NAMA FILE AMAN
                |--------------------------------------------------------------------------
                */
                $imgnewfile =
                    bin2hex(random_bytes(16)) . '.' . $extension;

                $upload_path = 'produk_img/' . $imgnewfile;

                /*
                |--------------------------------------------------------------------------
                | UPLOAD FILE
                |--------------------------------------------------------------------------
                */
                if (!move_uploaded_file($tmp_file, $upload_path)) {

                    echo "<script>alert('Gagal upload gambar!');</script>";
                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | INSERT DATABASE (PREPARED STATEMENT)
                    |--------------------------------------------------------------------------
                    */
                    $stmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO products
                        (
                            category_id,
                            product_code,
                            product_name,
                            stock,
                            min_stock,
                            price,
                            gambar
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?)"
                    );

                    mysqli_stmt_bind_param(
                        $stmt,
                        "issiids",
                        $id_kategori,
                        $kd_produk,
                        $nm_produk,
                        $stok,
                        $min_stok,
                        $harga,
                        $imgnewfile
                    );

                    $query = mysqli_stmt_execute($stmt);

                    if ($query) {

                        header("Location: produk.php?success=1");
                        exit;
                    } else {

                        echo "<script>alert('Data gagal disimpan!');</script>";
                    }

                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}
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

        body,
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
        .logo span {
            font-family: var(--main-font) !important;
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
        </div>

        <nav class="header-nav ms-auto">
            <ul class="d-flex align-items-center">
                <li class="nav-item dropdown pe-3">

                    <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
                        <img src="assets/img/Profile1.png" alt="Profile" class="rounded-circle">
                    </a>

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
                    </ul>

                </li>
            </ul>
        </nav>

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

    </aside>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Data Produk</h1>

            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="produk.php">Data Produk</a></li>
                    <li class="breadcrumb-item active">Tambah</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-6">

                    <div class="card">
                        <div class="card-body">

                            <h5 class="card-title">Tambah Produk</h5>

                            <!-- Vertical Form -->
                            <form class="row g-3" method="post" enctype="multipart/form-data">

                                <div class="col-12">
                                    <label for="kd_produk" class="form-label">Kode Produk</label>
                                    <input type="text" class="form-control" id="kd_produk" name="kd_produk" value="<?= htmlspecialchars($kd_produk); ?>" readonly>
                                </div>

                                <div class="col-12">
                                    <label for="nm_produk" class="form-label">Nama produk</label>
                                    <input type="text" class="form-control" id="nm_produk" name="nm_produk" required>
                                </div>

                                <div class="col-12">
                                    <label for="stok" class="form-label">Stok</label>
                                    <input type="number" class="form-control" id="stok" name="stok" required>
                                </div>

                                <div class="col-12">
                                    <label for="min_stok" class="form-label">Minimal Stok</label>
                                    <input type="number" class="form-control" id="min_stok" name="min_stok" required>
                                </div>

                                <div class="col-12">
                                    <label for="harga" class="form-label">Harga</label>
                                    <input type="number" class="form-control" id="harga" name="harga" required>
                                </div>

                                <div class="col-12">
                                    <label for="id_kategori" class="form-label">Kategori</label>

                                    <select class="form-control" id="id_kategori" name="id_kategori" required>

                                        <option value="">-- Pilih Kategori --</option>

                                        <?php

                                        $queryKategori = mysqli_query(
                                            $conn,
                                            "SELECT id, category_name
     FROM categories
     ORDER BY category_name ASC"
                                        );

                                        if (
                                            $queryKategori instanceof mysqli_result &&
                                            mysqli_num_rows($queryKategori) > 0
                                        ):

                                            while ($kategori = mysqli_fetch_assoc($queryKategori)) :
                                        ?>

                                                <option value="<?= (int) $kategori['id']; ?>">

                                                    <?= htmlspecialchars($kategori['category_name']); ?>

                                                </option>

                                        <?php
                                            endwhile;

                                        endif;
                                        ?>

                                    </select>
                                </div>

                                <div class="col-12">
                                    <label for="gambar" class="form-label">Gambar Produk</label>
                                    <input type="file" class="form-control" id="gambar" name="gambar" accept="image/*" required>
                                </div>

                                <div class="text-center">

                                    <button type="button" class="btn btn-warning">
                                        <a href="kategori_produk.php" style="color: black; text-decoration:none;">Kembali</a>
                                    </button>

                                    <button type="reset" class="btn btn-secondary">Reset</button>

                                    <button type="submit" class="btn btn-success" name="simpan">Simpan</button>

                                </div>

                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- ======= Footer ======= -->
    <footer id="footer" class="footer">

        <div class="copyright">
            &copy; Copyright <strong><span>Street Mile</span></strong>. All Rights Reserved
        </div>

        <div class="credits">
            Designed by <a href="https://www.instagram.com/axelwavehassle/">Axel Indra Yudha</a>
        </div>

    </footer>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

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