<?php
session_start();
include './../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

$id_kendaraan = isset($_GET['id']) ? $_GET['id'] : '';

if ($id_kendaraan) {
    // Ambil data kendaraan untuk log
    $query = "SELECT plat_nomor FROM tb_kendaraan WHERE id_kendaraan = '$id_kendaraan'";
    $result = mysqli_query($koneksi, $query);
    $kendaraan = mysqli_fetch_assoc($result);
    
    // Delete kendaraan
    $delete_query = "DELETE FROM tb_kendaraan WHERE id_kendaraan = '$id_kendaraan'";
    
    if (mysqli_query($koneksi, $delete_query)) {
        // Log aktivitas
        $id_admin = $_SESSION['id_user'];
        $aktivitas = "Hapus kendaraan: " . $kendaraan['plat_nomor'];
        $waktu = date('Y-m-d H:i:s');
        $log_query = "INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_aktivitas) 
                     VALUES ('$id_admin', '$aktivitas', '$waktu')";
        mysqli_query($koneksi, $log_query);
    }
}

header("Location: kendaraan.php");
exit();
?>
