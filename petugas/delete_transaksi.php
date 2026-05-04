<?php
session_start();
include './../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../../auth/login.php");
    exit();
}

$id_parkir = isset($_GET['id']) ? $_GET['id'] : '';

if ($id_parkir) {
    // Ambil data transaksi untuk log dan update area
    $query = "SELECT t.id_area, t.status, k.plat_nomor FROM tb_kendaraan k 
              JOIN tb_transaksi t ON k.id_kendaraan = t.id_kendaraan
              WHERE t.id_parkir = '$id_parkir'";
    $result = mysqli_query($koneksi, $query);
    $data = mysqli_fetch_assoc($result);
    
    // Delete transaksi
    $delete_query = "DELETE FROM tb_transaksi WHERE id_parkir = '$id_parkir'";
    
    if (mysqli_query($koneksi, $delete_query)) {
        // Update kembali kapasitas area hanya jika transaksi masuk
        // Jika status 'masuk': kurangi terisi (menghapus kendaraan yang seharusnya ada di parkir)
        // Jika status 'keluar': jangan update (kendaraan sudah pergi, tidak mempengaruhi kapasitas saat ini)
        if ($data['status'] == 'masuk') {
            $update_area = "UPDATE tb_area_parkir SET terisi = terisi - 1 WHERE id_area = '{$data['id_area']}' AND terisi > 0";
            mysqli_query($koneksi, $update_area);
        }
        
        // Log aktivitas
        $id_petugas = $_SESSION['id_user'];
        $aktivitas = "Hapus transaksi: " . $data['plat_nomor'];
        $waktu = date('Y-m-d H:i:s');
        $log_query = "INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_aktivitas) 
                     VALUES ('$id_petugas', '$aktivitas', '$waktu')";
        mysqli_query($koneksi, $log_query);
    }
}

header("Location: transaksi.php?deleted=1");
exit();
?>
