<?php

declare(strict_types=1);

session_start();
require_once "koneksi.php";

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
   METHOD CHECK (HARUS POST)
========================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: produk.php");
    exit;
}

/* =========================
   CSRF CHECK
========================= */
if (
    !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
    $_POST['csrf_token'] !== $_SESSION['csrf_token']
) {
    die("CSRF validation failed");
}

/* =========================
   VALIDATE ID
========================= */
$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    header("Location: produk.php?error=invalid_id");
    exit;
}

/* =========================
   AMBIL DATA GAMBAR (SAFE QUERY)
========================= */
$stmt = mysqli_prepare($conn, "SELECT gambar FROM products WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$gambar = $data['gambar'] ?? "";

/* =========================
   HAPUS FILE GAMBAR
========================= */
if (!empty($gambar)) {
    $path = "produk_img/" . basename($gambar);

    if (file_exists($path)) {
        unlink($path);
    }
}

/* =========================
   DELETE DATA (SAFE QUERY)
========================= */
$stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);

    header("Location: produk.php?success=deleted");
    exit;
} else {

    error_log("Delete product failed: " . mysqli_error($conn));

    mysqli_stmt_close($stmt);

    header("Location: produk.php?error=delete_failed");
    exit;
}
