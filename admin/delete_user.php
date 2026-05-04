<?php
session_start();
include './../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

$id_user = isset($_GET['id']) ? $_GET['id'] : '';

if ($id_user) {
    // Ambil data user untuk log
    $query = "SELECT username FROM tb_user WHERE id_user = '$id_user'";
    $result = mysqli_query($koneksi, $query);
    $user = mysqli_fetch_assoc($result);
    
    // Delete user
    $delete_query = "DELETE FROM tb_user WHERE id_user = '$id_user'";
    
    if (mysqli_query($koneksi, $delete_query)) {
        // Log aktivitas
        $id_admin = $_SESSION['id_user'];
        $aktivitas = "Hapus user: " . $user['username'];
        $waktu = date('Y-m-d H:i:s');
        $log_query = "INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_aktivitas) 
                     VALUES ('$id_admin', '$aktivitas', '$waktu')";
        mysqli_query($koneksi, $log_query);
    }
}

header("Location: user.php");
exit();
?>
