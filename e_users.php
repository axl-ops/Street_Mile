<?php
include "koneksi.php";

$id   = $_GET['id'];
$data = mysqli_query($conn, "SELECT * FROM users WHERE id='$id'");
$users = mysqli_fetch_array($data);

if (isset($_POST['update'])) {

    $name      = mysqli_real_escape_string($conn, $_POST['name']);
    $email     = mysqli_real_escape_string($conn, $_POST['email']);
    $password  = $_POST['password'];
    $role      = $_POST['role'];
    $is_active = $_POST['is_active'];

    // cek email (kecuali email milik users ini sendiri)
    $cek = mysqli_query($conn, "SELECT * FROM users 
                                WHERE email='$email' 
                                AND id!='{$id}'");

    if (mysqli_num_rows($cek) > 0) {
        echo "<script>
                alert('Email sudah digunakan users lain!');
                window.location='users.php';
              </script>";
        exit;
    }

    // jika password diisi → update password
    if (!empty($password)) {

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $query = mysqli_query($conn, "UPDATE users SET
                    name='$name',
                    email='$email',
                    password='$password_hash',
                    role='$role',
                    is_active='$is_active'
                    WHERE id='$id'
                ");

    } else {

        // jika password kosong → jangan update password
        $query = mysqli_query($conn, "UPDATE users SET
                    name='$name',
                    email='$email',
                    role='$role',
                    is_active='$is_active'
                    WHERE id='$id'
                ");
    }

    if ($query) {
        echo "<script>
                alert('User berhasil diupdate!');
                window.location='users.php';
              </script>";
    } else {
        echo "<script>
                alert('User gagal diupdate!');
                window.location='users.php';
              </script>";
    }
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
    <link href="assets/img/favicon.png" rel="icon">
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

</head>

<body>

    <!-- ======= Header ======= -->
    <header id="header" class="header fixed-top d-flex align-items-center">

        <div class="d-flex align-items-center justify-content-between">
            <a href="index.php" class="logo d-flex align-items-center">
                <img src="assets/img/logo.png" alt="">
                <span class="d-none d-lg-block">Street Mile</span>
            </a>
            <i class="bi bi-list toggle-sidebar-btn"></i>
        </div><!-- End Logo -->

        <nav class="header-nav ms-auto">
            <ul class="d-flex align-items-center">

                <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
                    <img src="assets/img/profile-img.jpg" alt="Profile" class="rounded-circle">
                </a><!-- End Profile Iamge Icon -->

                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
                    <li class="dropdown-header">
                        <h6>Kevin Anderson</h6>
                        <span>Web Designer</span>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>

                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="users-profile.html">
                            <i class="bi bi-person"></i>
                            <span>My Profile</span>
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>

                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="users-profile.html">
                            <i class="bi bi-gear"></i>
                            <span>Account Settings</span>
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>

                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="pages-faq.html">
                            <i class="bi bi-question-circle"></i>
                            <span>Need Help?</span>
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>

                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="#">
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
                    <span>Data_Produk</span>
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
            Designed by <a href="">AxelIndraYudha</a>
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