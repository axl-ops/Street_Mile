<?php

declare(strict_types=1);

/* =========================
   SESSION SECURITY
========================= */
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

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
function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/* =========================
   VALIDATE ID (SAFE)
========================= */
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header("Location: users.php");
    exit;
}

/* =========================
   FETCH USER (PREPARED)
========================= */
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$users = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$users) {
    header("Location: users.php");
    exit;
}

/* =========================
   UPDATE USER
========================= */
if (isset($_POST['update'])) {

    $name      = trim($_POST['name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $role      = $_POST['role'] ?? '';
    $is_active = (int)($_POST['is_active'] ?? 1);

    /* VALIDATION */
    if ($name === '' || $email === '' || $role === '') {
        echo "<script>alert('Field wajib diisi!');window.location='users.php';</script>";
        exit;
    }

    /* CHECK EMAIL DUPLICATE */
    $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
    mysqli_stmt_bind_param($check, "si", $email, $id);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if (mysqli_stmt_num_rows($check) > 0) {
        mysqli_stmt_close($check);
        echo "<script>alert('Email sudah digunakan!');window.location='users.php';</script>";
        exit;
    }

    mysqli_stmt_close($check);

    /* =========================
       UPDATE QUERY
    ========================= */
    if (!empty($password)) {

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $update = mysqli_prepare($conn, "
            UPDATE users 
            SET name=?, email=?, password=?, role=?, is_active=? 
            WHERE id=?
        ");

        mysqli_stmt_bind_param(
            $update,
            "ssssii",
            $name,
            $email,
            $password_hash,
            $role,
            $is_active,
            $id
        );
    } else {

        $update = mysqli_prepare($conn, "
            UPDATE users 
            SET name=?, email=?, role=?, is_active=? 
            WHERE id=?
        ");

        mysqli_stmt_bind_param(
            $update,
            "sssii",
            $name,
            $email,
            $role,
            $is_active,
            $id
        );
    }

    if (mysqli_stmt_execute($update)) {
        header("Location: users.php?success=1");
        exit;
    } else {
        error_log("Update user error: " . mysqli_error($conn));
        header("Location: users.php?error=1");
        exit;
    }

    mysqli_stmt_close($update);
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
        html,
        body {
            font-family: Helvetica, Arial, sans-serif !important;
            font-weight: 400;
            letter-spacing: 0.2px;
        }

        /* force semua elemen ikut font body */
        body * {
            font-family: inherit !important;
        }

        /* optional: pastikan tombol & input tidak beda font di browser tertentu */
        button,
        input,
        select,
        textarea {
            font-family: inherit !important;
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

            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="index.php">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- Kategori Produk -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="kategori_produk.php">
                    <i class="bi bi-tags"></i>
                    <span>Kategori Produk</span>
                </a>
            </li>

            <!-- Data Produk -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="produk.php">
                    <i class="bi bi-box-seam"></i>
                    <span>Data Produk</span>
                </a>
            </li>

            <!-- Laporan -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="laporan.php">
                    <i class="bi bi-bar-chart-line"></i>
                    <span>Laporan</span>
                </a>
            </li>

            <!-- Manajemen User (AKTIF) -->
            <li class="nav-item">
                <a class="nav-link" href="users.php">
                    <i class="bi bi-people"></i>
                    <span>Manajemen User</span>
                </a>
            </li>

        </ul>

    </aside><!-- End Sidebar -->

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Manajemen User</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item">Manajemen User</li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->
        <section class="section">
            <div class="row">
                <div class="col-lg-6">

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Vertical Form</h5>

                            <!-- Vertical Form -->
                            <form class="row g-3" method="post">

                                <div class="col-12">
                                    <label class="form-label">Nama</label>
                                    <input type="text"
                                        class="form-control"
                                        name="name"
                                        value="<?php echo $users['name']; ?>"
                                        required>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Email</label>
                                    <input type="email"
                                        class="form-control"
                                        name="email"
                                        value="<?php echo $users['email']; ?>"
                                        required>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Password</label>
                                    <input type="password"
                                        class="form-control"
                                        name="password">

                                    <small class="text-muted">
                                        Kosongkan jika tidak ingin mengubah password
                                    </small>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Role</label>

                                    <select class="form-control" name="role" required>
                                        <option value="admin"
                                            <?php if ($users['role'] == 'admin') echo 'selected'; ?>>
                                            Admin
                                        </option>

                                        <option value="staff"
                                            <?php if ($users['role'] == 'staff') echo 'selected'; ?>>
                                            Staff
                                        </option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Status</label>

                                    <select class="form-control" name="is_active">
                                        <option value="1"
                                            <?php if ($users['is_active'] == 1) echo 'selected'; ?>>
                                            Aktif
                                        </option>

                                        <option value="0"
                                            <?php if ($users['is_active'] == 0) echo 'selected'; ?>>
                                            Nonaktif
                                        </option>
                                    </select>
                                </div>

                                <div class="text-center">
                                    <a href="users.php" class="btn btn-warning">
                                        Kembali
                                    </a>

                                    <button type="submit"
                                        class="btn btn-success"
                                        name="update">
                                        Update
                                    </button>
                                </div>

                            </form>
                            <!-- Vertical Form -->

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