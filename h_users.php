<?php

declare(strict_types=1);

session_start();
require_once "koneksi.php";

/* =========================
   SECURITY CHECK (LOGIN)
========================= */
if (empty($_SESSION['login']) || empty($_SESSION['user_id'])) {
  session_unset();
  session_destroy();
  header("Location: login.php");
  exit;
}

/* =========================
   VALIDATE ID
========================= */
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
  header("Location: users.php?error=invalid_id");
  exit;
}

/* =========================
   PREVENT SELF DELETE (RECOMMENDED)
========================= */
$currentUserId = (int) $_SESSION['user_id'];

if ($id === $currentUserId) {
  header("Location: users.php?error=self_delete_blocked");
  exit;
}

/* =========================
   DELETE USER (SAFE QUERY)
========================= */
$stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {

  mysqli_stmt_close($stmt);
  header("Location: users.php?success=deleted");
  exit;
} else {

  error_log("Delete user failed: " . mysqli_error($conn));
  mysqli_stmt_close($stmt);
  header("Location: users.php?error=delete_failed");
  exit;
}
