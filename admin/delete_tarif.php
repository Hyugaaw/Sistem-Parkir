<?php
session_start();
include './../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

$id_tarif = isset($_GET['id']) ? $_GET['id'] : '';

if ($id_tarif) {
    // Ambil data tarif untuk log
    $query = "SELECT jenis_kendaraan FROM tb_tarif WHERE id_tarif = '$id_tarif'";
    $result = mysqli_query($koneksi, $query);
    $tarif = mysqli_fetch_assoc($result);
    
    // Delete tarif
    $delete_query = "DELETE FROM tb_tarif WHERE id_tarif = '$id_tarif'";
    
    if (mysqli_query($koneksi, $delete_query)) {
        // Log aktivitas
        $id_admin = $_SESSION['id_user'];
        $aktivitas = "Hapus tarif: " . $tarif['jenis_kendaraan'];
        $waktu = date('Y-m-d H:i:s');
        $log_query = "INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_aktivitas) 
                     VALUES ('$id_admin', '$aktivitas', '$waktu')";
        mysqli_query($koneksi, $log_query);
    }
}

header("Location: tarif.php");
exit();
?>
