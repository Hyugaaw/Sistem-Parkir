<?php
session_start();
include './../koneksi.php';

// Set timezone ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'petugas') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_parkir = isset($_POST['id_parkir']) ? mysqli_real_escape_string($koneksi, $_POST['id_parkir']) : null;
    
    if (!$id_parkir) {
        echo json_encode(['success' => false, 'message' => 'ID Parkir tidak ditemukan']);
        exit();
    }
    
    // Ambil data transaksi dengan query yang lebih lengkap
    $query = "SELECT id_parkir, id_kendaraan, waktu_masuk, waktu_keluar, id_tarif, durasi_jam, biaya_total, status, id_user, id_area 
              FROM tb_transaksi 
              WHERE id_parkir = '$id_parkir'";
    $result = mysqli_query($koneksi, $query);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Query error: ' . mysqli_error($koneksi)]);
        exit();
    }
    
    $transaksi = mysqli_fetch_assoc($result);
    
    if (!$transaksi) {
        echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan']);
        exit();
    }
    
    error_log("CHECK_KELUAR - ID: $id_parkir");
    error_log("  Status DB: {$transaksi['status']}");
    error_log("  Waktu Masuk: {$transaksi['waktu_masuk']}");
    error_log("  Waktu Keluar: {$transaksi['waktu_keluar']}");
    
    // Cek apakah status masih masuk dan waktu keluar sudah ada
    if ($transaksi['status'] == 'masuk' && !empty($transaksi['waktu_keluar'])) {
        
        // Gunakan MySQL untuk perbandingan waktu yang lebih akurat
        $check_query = "SELECT IF(NOW() >= waktu_keluar, 1, 0) as sudah_keluar 
                        FROM tb_transaksi 
                        WHERE id_parkir = '$id_parkir'";
        
        $check_result = mysqli_query($koneksi, $check_query);
        $check_data = mysqli_fetch_assoc($check_result);
        
        error_log("  NOW(): " . date('Y-m-d H:i:s'));
        error_log("  Sudah Keluar (MySQL): " . $check_data['sudah_keluar']);
        
        if ($check_data['sudah_keluar'] == 1) {
            // Waktu keluar sudah tiba!
            error_log("  ✓ WAKTU KELUAR SUDAH TIBA!");
            
            // Hitung durasi dan biaya
            $durasi_detik = strtotime($transaksi['waktu_keluar']) - strtotime($transaksi['waktu_masuk']);
            $durasi_jam = max(1, ceil($durasi_detik / 3600)); // Minimal 1 jam
            
            // Ambil tarif
            $tarif_query = "SELECT tarif_per_jam FROM tb_tarif WHERE id_tarif = '{$transaksi['id_tarif']}'";
            $tarif_result = mysqli_query($koneksi, $tarif_query);
            $tarif_data = mysqli_fetch_assoc($tarif_result);
            
            if (!$tarif_data) {
                echo json_encode(['success' => false, 'message' => 'Tarif tidak ditemukan']);
                exit();
            }
            
            $biaya_total = $durasi_jam * $tarif_data['tarif_per_jam'];
            
            error_log("  Durasi: $durasi_jam jam, Biaya: $biaya_total");
            
            // UPDATE STATUS KE KELUAR
            $update_query = "UPDATE tb_transaksi 
                            SET status = 'keluar',
                                durasi_jam = $durasi_jam,
                                biaya_total = $biaya_total
                            WHERE id_parkir = '$id_parkir'";
            
            if (mysqli_query($koneksi, $update_query)) {
                error_log("  ✓ Status updated to 'keluar'");
                
                // KURANGI KAPASITAS AREA
                $update_area_query = "UPDATE tb_area_parkir 
                                      SET terisi = IF(terisi > 0, terisi - 1, 0) 
                                      WHERE id_area = '{$transaksi['id_area']}'";
                
                if (mysqli_query($koneksi, $update_area_query)) {
                    error_log("  ✓ Area capacity updated");
                } else {
                    error_log("  ✗ Area update failed: " . mysqli_error($koneksi));
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Status diubah ke keluar',
                    'status' => 'keluar',
                    'durasi_jam' => $durasi_jam,
                    'biaya_total' => $biaya_total,
                    'waktu_sekarang' => date('Y-m-d H:i:s')
                ]);
                exit();
            } else {
                error_log("  ✗ Update failed: " . mysqli_error($koneksi));
                echo json_encode([
                    'success' => false,
                    'message' => 'Update gagal: ' . mysqli_error($koneksi)
                ]);
                exit();
            }
        }
    }
    
    // Masih dalam status masuk atau belum tiba
    $response = [
        'success' => true,
        'status' => $transaksi['status'],
        'waktu_sekarang' => date('Y-m-d H:i:s'),
        'waktu_keluar' => $transaksi['waktu_keluar']
    ];
    
    // Hitung sisa waktu jika ada jadwal keluar
    if (!empty($transaksi['waktu_keluar'])) {
        $sisa_detik = strtotime($transaksi['waktu_keluar']) - time();
        $response['sisa_waktu'] = max(0, $sisa_detik);
    }
    
    error_log("  Status response: " . json_encode($response));
    echo json_encode($response);
    
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
}
?>