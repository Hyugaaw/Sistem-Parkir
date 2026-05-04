<?php
// Set Timezone
date_default_timezone_set('Asia/Jakarta');

// Database Connection
$host = "localhost";
$user = "root";
$password = "";
$database = "parkir";

$koneksi = mysqli_connect($host, $user, $password, $database);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

mysqli_set_charset($koneksi, "utf8");

// Set database timezone ke Asia/Jakarta
mysqli_query($koneksi, "SET SESSION time_zone = '+07:00'");
?>
