<?php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "axel25550005";

// Membuat koneksi
$conn = mysqli_connect($host, $user, $pass, $db);

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal.");
}

// Set charset UTF-8
mysqli_set_charset($conn, "utf8mb4");

?>