<?php
session_start();

// Jika sudah login, redirect ke dashboard sesuai role
if (isset($_SESSION['id_user'])) {
    $role = $_SESSION['role'];
    
    if ($role == 'admin') {
        header("Location: admin/index.php");
    } elseif ($role == 'petugas') {
        header("Location: petugas/index.php");
    } elseif ($role == 'owner') {
        header("Location: owner/index.php");
    }
    exit();
}

// Redirect ke halaman login
header("Location: auth/login.php");
exit();
?>
