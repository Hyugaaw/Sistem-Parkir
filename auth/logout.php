<?php
session_start();
include '../koneksi.php';

if (isset($_SESSION['id_user'])) {
    $id_user = $_SESSION['id_user'];
    $aktivitas = "Logout";
    $waktu = date('Y-m-d H:i:s');
    
    $log_query = "INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_aktivitas) 
                 VALUES ('$id_user', '$aktivitas', '$waktu')";
    mysqli_query($koneksi, $log_query);
}

session_destroy();
header("Location: login.php");
exit();
?>
