<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
include './../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../../auth/login.php");
    exit();
}

$id_parkir = isset($_GET['id']) ? $_GET['id'] : '';
$error = '';
$success = '';

// Ambil data transaksi
if ($id_parkir) {
    $query = "SELECT t.*, k.id_kendaraan, k.plat_nomor 
              FROM tb_transaksi t
              LEFT JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
              WHERE t.id_parkir = '$id_parkir'";
    $result = mysqli_query($koneksi, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $transaksi = mysqli_fetch_assoc($result);
    } else {
        header("Location: transaksi.php");
        exit();
    }
} else {
    header("Location: transaksi.php");
    exit();
}

// Ambil list tarif dan area
$tarif_query = "SELECT * FROM tb_tarif";
$tarif_result = mysqli_query($koneksi, $tarif_query);

$area_query = "SELECT * FROM tb_area_parkir";
$area_result = mysqli_query($koneksi, $area_query);

// Proses update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $waktu_keluar = !empty($_POST['waktu_keluar']) ? mysqli_real_escape_string($koneksi, $_POST['waktu_keluar']) : NULL;
    
    // VALIDASI: Waktu keluar harus lebih besar dari waktu masuk
    if ($waktu_keluar && $waktu_keluar <= $transaksi['waktu_masuk']) {
        $error = "❌ Waktu keluar harus lebih besar dari waktu masuk! (Masuk: {$transaksi['waktu_masuk']})";
    } else {
        // Tarif tidak diubah - gunakan yang sudah ada
        $id_tarif = $transaksi['id_tarif'];
        $id_area = mysqli_real_escape_string($koneksi, $_POST['id_area']);
        
        // Hitung durasi dan biaya untuk preview
        $durasi_jam = 0;
        $biaya_total = 0;
        
        // Simpan status dan area lama untuk update kapasitas
        $old_status = $transaksi['status'];
        $old_area = $transaksi['id_area'];
        
        // DEFAULT: preserve status lama
        $status = $old_status;
        
        if ($waktu_keluar) {
            $masuk = strtotime($transaksi['waktu_masuk']);
            $keluar = strtotime($waktu_keluar);
            $durasi_jam = max(1, ceil(($keluar - $masuk) / 3600)); // Minimal 1 jam
            
            // Ambil tarif untuk hitung biaya
            $tarif_detail = mysqli_fetch_assoc(mysqli_query($koneksi, 
                "SELECT tarif_per_jam FROM tb_tarif WHERE id_tarif = '$id_tarif'"));
            if ($tarif_detail) {
                $biaya_total = $durasi_jam * $tarif_detail['tarif_per_jam'];
            }
            
            // Logic: Jika waktu_keluar <= sekarang → langsung keluar
            // Jika waktu_keluar > sekarang → tetap masuk (tunggu jam tiba untuk auto-update)
            $now = date('Y-m-d H:i:s');
            if ($waktu_keluar <= $now) {
                $status = 'keluar';
            } else {
                $status = 'masuk';
            }
        }
        
        $update_query = "UPDATE tb_transaksi SET 
                        waktu_keluar = " . ($waktu_keluar ? "'$waktu_keluar'" : "NULL") . ",
                        id_tarif = '$id_tarif',
                        durasi_jam = '$durasi_jam',
                        biaya_total = '$biaya_total',
                        status = '$status',
                        id_area = '$id_area'
                        WHERE id_parkir = '$id_parkir'";
        
        if (mysqli_query($koneksi, $update_query)) {
            // Update kapasitas area hanya jika status TRULY berubah
            
            // Jika area berubah
            if ($old_area != $id_area) {
                // Kembalikan kapasitas area lama
                if ($old_status == 'masuk') {
                    $update_old_area = "UPDATE tb_area_parkir SET terisi = terisi - 1 WHERE id_area = '$old_area' AND terisi > 0";
                    mysqli_query($koneksi, $update_old_area);
                } elseif ($old_status == 'keluar') {
                    $update_old_area = "UPDATE tb_area_parkir SET terisi = terisi + 1 WHERE id_area = '$old_area'";
                    mysqli_query($koneksi, $update_old_area);
                }
                
                // Tambah ke area baru
                if ($status == 'masuk') {
                    $update_new_area = "UPDATE tb_area_parkir SET terisi = terisi + 1 WHERE id_area = '$id_area'";
                    mysqli_query($koneksi, $update_new_area);
                } elseif ($status == 'keluar') {
                    $update_new_area = "UPDATE tb_area_parkir SET terisi = terisi - 1 WHERE id_area = '$id_area' AND terisi > 0";
                    mysqli_query($koneksi, $update_new_area);
                }
            } elseif ($old_status == 'masuk' && $status == 'keluar') {
                // Status truly changed masuk→keluar: kurangi kapasitas
                $update_area = "UPDATE tb_area_parkir SET terisi = terisi - 1 WHERE id_area = '$id_area' AND terisi > 0";
                mysqli_query($koneksi, $update_area);
            } elseif ($old_status == 'keluar' && $status == 'masuk') {
                // Status berubah keluar→masuk: tambah kapasitas
                $update_area = "UPDATE tb_area_parkir SET terisi = terisi + 1 WHERE id_area = '$id_area'";
                mysqli_query($koneksi, $update_area);
            }
            // Jika status tetap 'masuk' = NO CHANGE to capacity

            $success = "Transaksi berhasil diupdate!";
            
            // Log aktivitas
            $id_petugas = $_SESSION['id_user'];
            $aktivitas = "Edit transaksi: " . $transaksi['plat_nomor'];
            $waktu = date('Y-m-d H:i:s');
            $log_query = "INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_aktivitas) 
                         VALUES ('$id_petugas', '$aktivitas', '$waktu')";
            mysqli_query($koneksi, $log_query);
            
            header("Location: transaksi.php");
            exit();

        } else {
            $error = "Error: " . mysqli_error($koneksi);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaksi - Aplikasi Parkir</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.5);
        }
        
        .form-group input[readonly] {
            background: #f0f0f0;
        }
        
        .form-buttons {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }
        
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .btn-submit {
            background: #667eea;
            color: white;
        }
        
        .btn-submit:hover {
            background: #5568d3;
        }
        
        .btn-batal {
            background: #ddd;
            color: #333;
        }
        
        .btn-batal:hover {
            background: #ccc;
        }
        
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .note {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Edit Transaksi Parkir</h1>
        </header>
        
        <div class="form-container">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="plat_nomor">Plat Nomor</label>
                    <input type="text" id="plat_nomor" value="<?php echo $transaksi['plat_nomor']; ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="waktu_masuk">Waktu Masuk</label>
                    <input type="datetime-local" id="waktu_masuk" value="<?php echo str_replace(' ', 'T', $transaksi['waktu_masuk']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="status">Status *</label>
                    <div style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: <?php echo $transaksi['status'] == 'masuk' ? '#d4edda' : '#f8d7da'; ?>">
                        <span style="display: inline-block; padding: 5px 12px; border-radius: 20px; background: <?php echo $transaksi['status'] == 'masuk' ? '#28a745' : '#dc3545'; ?>; color: white; font-weight: 600;">
                            <?php echo ucfirst($transaksi['status']); ?>
                        </span>
                        <span style="margin-left: 10px; color: #666; font-size: 13px;">
                            <?php echo $transaksi['status'] == 'masuk' ? '🟢 Kendaraan sedang parkir' : '🔴 Kendaraan sudah pergi'; ?>
                        </span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="id_area">Area Parkir *</label>
                    <select id="id_area" name="id_area" required>
                        <?php while ($area = mysqli_fetch_assoc($area_result)): ?>
                        <option value="<?php echo $area['id_area']; ?>" <?php echo $transaksi['id_area'] == $area['id_area'] ? 'selected' : ''; ?>>
                            <?php echo $area['nama_area']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="id_tarif">Tarif *</label>
                    <select id="id_tarif" name="id_tarif" disabled>
                        <?php 
                        mysqli_data_seek($tarif_result, 0);
                        while ($tarif = mysqli_fetch_assoc($tarif_result)): 
                        ?>
                        <option value="<?php echo $tarif['id_tarif']; ?>" <?php echo $transaksi['id_tarif'] == $tarif['id_tarif'] ? 'selected' : ''; ?>>
                            <?php echo ucfirst($tarif['jenis_kendaraan']) . ' - Rp ' . number_format($tarif['tarif_per_jam'], 0, ',', '.') . '/jam'; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <p class="note">ⓘ Tarif ditentukan saat kendaraan masuk dan tidak bisa diubah</p>
                </div>
                
                <div class="form-group">
                    <label for="waktu_keluar">Waktu Keluar</label>
                    <input type="datetime-local" id="waktu_keluar" name="waktu_keluar" 
                           value="<?php echo $transaksi['waktu_keluar'] ? str_replace(' ', 'T', $transaksi['waktu_keluar']) : ''; ?>" 
                           <?php echo $transaksi['waktu_keluar'] ? 'readonly style="background-color: #f0f0f0;"' : ''; ?>>
                    <p class="note">
                        <?php 
                        if ($transaksi['waktu_keluar']) {
                            $masuk = strtotime($transaksi['waktu_masuk']);
                            $keluar = strtotime($transaksi['waktu_keluar']);
                            $durasi_menit = round(($keluar - $masuk) / 60);
                            $jam = floor($durasi_menit / 60);
                            $menit = $durasi_menit % 60;
                            
                            $jam_masuk = date('H:i', $masuk);
                            $jam_keluar = date('H:i', $keluar);
                            
                            if ($jam > 0) {
                                $durasi_text = "$jam jam " . ($menit > 0 ? "$menit menit" : "");
                            } else {
                                $durasi_text = "$menit menit";
                            }
                            
                            echo "✓ Kendaraan sudah masuk pada $jam_masuk, akan keluar pada $jam_keluar ($durasi_text)";
                        } else {
                            echo 'Isi waktu keluar dan gunakan tombol update di bawah';
                        }
                        ?>
                    </p>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" class="btn btn-submit">Update</button>
                    <a href="transaksi.php" class="btn btn-batal" style="text-decoration: none; text-align: center;">Batal</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const waktuMasukInput = document.getElementById('waktu_masuk');
        const waktuKeluarInput = document.getElementById('waktu_keluar');
        
        function setMinWaktuKeluar() {
            if (waktuMasukInput.value) {
                const [tanggal, jam] = waktuMasukInput.value.split('T');
                const [tahun, bulan, hari] = tanggal.split('-');
                const [jam_val, menit_val] = jam.split(':');
                
                let menit_baru = parseInt(menit_val) + 1;
                let jam_baru = parseInt(jam_val);
                
                if (menit_baru >= 60) {
                    menit_baru = 0;
                    jam_baru += 1;
                }
                
                const minDateTime = `${tahun}-${bulan}-${hari}T${String(jam_baru).padStart(2, '0')}:${String(menit_baru).padStart(2, '0')}`;
                waktuKeluarInput.min = minDateTime;
            }
        }
        
        waktuKeluarInput.addEventListener('change', function() {
            const waktuMasuk = waktuMasukInput.value;
            const waktuKeluar = this.value;
            
            if (waktuKeluar && waktuMasuk) {
                if (waktuKeluar <= waktuMasuk) {
                    alert('⚠ Waktu keluar harus lebih besar dari waktu masuk!');
                    this.value = '';
                    return;
                }
            }
        });
        
        window.addEventListener('load', setMinWaktuKeluar);
    </script>
</body>
</html>