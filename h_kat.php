<?php
include "koneksi.php";
$id = $_GET['id'];
$hapus = mysqli_query($conn, "DELETE FROM categories WHERE id='$id'");

if ($hapus) {
        echo "<script>alert('Data berhasil dihapus!')</script>";
        header("refresh:0, kategori_produk.php");
    } else {
        echo "<script>alert('Data gagal dihapus!')</script>";
        header("refresh:0, kategori_produk.php");
    }
?>