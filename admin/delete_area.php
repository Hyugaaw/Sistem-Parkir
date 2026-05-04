<?php
session_start();
include './../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

$id_area = isset($_GET['id']) ? $_GET['id'] : '';

if ($id_area) {
    // Ambil data area untuk log
    $query = "SELECT nama_area FROM tb_area_parkir WHERE id_area = '$id_area'";
    $result = mysqli_query($koneksi, $query);
    $area = mysqli_fetch_assoc($result);
    
    // Delete area
    $delete_query = "DELETE FROM tb_area_parkir WHERE id_area = '$id_area'";
    
    if (mysqli_query($koneksi, $delete_query)) {
        // Log aktivitas
        $id_admin = $_SESSION['id_user'];
        $aktivitas = "Hapus area: " . $area['nama_area'];
        $waktu = date('Y-m-d H:i:s');
        $log_query = "INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_aktivitas) 
                     VALUES ('$id_admin', '$aktivitas', '$waktu')";
        mysqli_query($koneksi, $log_query);
    }
}

header("Location: area.php");
exit();
?>
