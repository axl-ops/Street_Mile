<?php

declare(strict_types=1);

session_start();
require_once "koneksi.php";

/* =========================
   LOGIN CHECK
========================= */
if (empty($_SESSION['login']) || empty($_SESSION['user_id'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

/* =========================
   METHOD CHECK (WAJIB POST)
========================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: kategori_produk.php");
    exit;
}

/* =========================
   CSRF VALIDATION
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
    header("Location: kategori_produk.php?error=invalid_id");
    exit;
}

/* =========================
   DELETE SAFE QUERY
========================= */
$stmt = mysqli_prepare($conn, "DELETE FROM categories WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);

    header("Location: kategori_produk.php?success=deleted");
    exit;
} else {

    error_log("Delete category failed: " . mysqli_error($conn));

    mysqli_stmt_close($stmt);

    header("Location: kategori_produk.php?error=delete_failed");
    exit;
}
