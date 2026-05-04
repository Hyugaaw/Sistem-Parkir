<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
include './../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../../auth/login.php");
    exit();
}

$error = '';
$success = '';

// Ambil list kendaraan, tarif, dan area
$kendaraan_query = "SELECT * FROM tb_kendaraan ORDER BY plat_nomor";
$kendaraan_result = mysqli_query($koneksi, $kendaraan_query);
$kendaraan_array = [];
while ($row = mysqli_fetch_assoc($kendaraan_result)) {
    $kendaraan_array[] = $row;
}

$tarif_query = "SELECT * FROM tb_tarif ORDER BY jenis_kendaraan";
$tarif_result = mysqli_query($koneksi, $tarif_query);
$tarif_array = [];
while ($row = mysqli_fetch_assoc($tarif_result)) {
    $tarif_array[] = $row;
}

$area_query = "SELECT * FROM tb_area_parkir ORDER BY nama_area";
$area_result = mysqli_query($koneksi, $area_query);
$area_array = [];
while ($row = mysqli_fetch_assoc($area_result)) {
    $area_array[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_kendaraan = mysqli_real_escape_string($koneksi, $_POST['id_kendaraan']);
    $waktu_keluar_raw = !empty($_POST['waktu_keluar']) ? $_POST['waktu_keluar'] : NULL;
    
    // Waktu masuk otomatis sekarang, tidak bisa diubah
    $waktu_masuk = date('Y-m-d H:i:s');
    $waktu_keluar = $waktu_keluar_raw ? date('Y-m-d H:i:s', strtotime($waktu_keluar_raw)) : NULL;
    
    // VALIDASI: Cek apakah kendaraan sudah sedang parkir (status masuk)
    $check_kendaraan_aktif = "SELECT id_parkir, waktu_masuk, id_area 
                              FROM tb_transaksi 
                              WHERE id_kendaraan = '$id_kendaraan' AND status = 'masuk'";
    $check_result = mysqli_query($koneksi, $check_kendaraan_aktif);
    
    if (mysqli_num_rows($check_result) > 0) {
        $parkir_aktif = mysqli_fetch_assoc($check_result);
        $waktu_masuk_sebelumnya = $parkir_aktif['waktu_masuk'];
        $error = "❌ Kendaraan ini sudah sedang parkir! Waktu masuk: $waktu_masuk_sebelumnya. Kendaraan harus keluar dulu sebelum bisa masuk lagi.";
    } else if ($waktu_keluar && $waktu_keluar <= $waktu_masuk) {
        $error = "❌ Waktu keluar harus lebih besar dari waktu masuk! (Masuk: $waktu_masuk)";
    } else {
        $id_tarif = mysqli_real_escape_string($koneksi, $_POST['id_tarif']);
        $id_area = mysqli_real_escape_string($koneksi, $_POST['id_area']);
        $status = 'masuk';
        
        // Ambil data area untuk validasi
        $area_check = "SELECT kapasitas, terisi FROM tb_area_parkir WHERE id_area = '$id_area'";
        $area_data = mysqli_fetch_assoc(mysqli_query($koneksi, $area_check));
        
        if (!$area_data) {
            $error = "Area tidak ditemukan!";
        } else {
            $durasi_jam = 0;
            $biaya_total = 0;
            $status = 'masuk';
            
            if ($waktu_keluar) {
                $masuk = strtotime($waktu_masuk);
                $keluar = strtotime($waktu_keluar);
                $durasi_jam = ceil(($keluar - $masuk) / 3600);
                
                $tarif_query_detail = "SELECT tarif_per_jam FROM tb_tarif WHERE id_tarif = '$id_tarif'";
                $tarif_detail = mysqli_fetch_assoc(mysqli_query($koneksi, $tarif_query_detail));
                if ($tarif_detail) {
                    $biaya_total = $durasi_jam * $tarif_detail['tarif_per_jam'];
                }
            }
            
            // Validasi kapasitas area
            if ($status == 'masuk' && $area_data['terisi'] >= $area_data['kapasitas']) {
                $error = "Area parkir sudah penuh! Kapasitas: " . $area_data['kapasitas'] . ", Terisi: " . $area_data['terisi'];
            } else {
                $query = "INSERT INTO tb_transaksi 
                          (id_kendaraan, waktu_masuk, waktu_keluar, id_tarif, durasi_jam, biaya_total, status, id_user, id_area) 
                          VALUES ('$id_kendaraan', '$waktu_masuk', " . ($waktu_keluar ? "'$waktu_keluar'" : "NULL") . ", 
                                  '$id_tarif', '$durasi_jam', '$biaya_total', '$status', '{$_SESSION['id_user']}', '$id_area')";
                
                if (mysqli_query($koneksi, $query)) {
                    if ($status == 'masuk') {
                        $update_area = "UPDATE tb_area_parkir SET terisi = terisi + 1 WHERE id_area = '$id_area'";
                    } else {
                        $update_area = "UPDATE tb_area_parkir SET terisi = IF(terisi > 0, terisi - 1, 0) WHERE id_area = '$id_area'";
                    }
                    
                    if (!mysqli_query($koneksi, $update_area)) {
                        $error = "Error updating area: " . mysqli_error($koneksi);
                    } else {
                        $id_petugas = $_SESSION['id_user'];
                        $aktivitas = "Input transaksi parkir - Status: $status";
                        $waktu = date('Y-m-d H:i:s');
                        $log_query = "INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_aktivitas) 
                                     VALUES ('$id_petugas', '$aktivitas', '$waktu')";
                        mysqli_query($koneksi, $log_query);
                        
                        header("Location: transaksi.php");
                        exit();
                    }
                } else {
                    $error = "Error: " . mysqli_error($koneksi);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Transaksi - Aplikasi Parkir</title>
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

        /* Styling untuk peringatan tarif tidak ditemukan */
        .tarif-warning {
            display: none;
            margin-top: 8px;
            padding: 10px 14px;
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffc107;
            border-radius: 5px;
            font-size: 13px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Input Transaksi Parkir</h1>
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
                    <label for="id_kendaraan">Kendaraan *</label>
                    <select id="id_kendaraan" name="id_kendaraan" required>
                        <option value="">-- Pilih Kendaraan --</option>
                        <?php foreach ($kendaraan_array as $kendaraan): ?>
                        <option value="<?php echo $kendaraan['id_kendaraan']; ?>" data-jenis="<?php echo $kendaraan['jenis_kendaraan']; ?>">
                            <?php echo $kendaraan['plat_nomor'] . ' (' . ucfirst($kendaraan['jenis_kendaraan']) . ')'; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="id_area">Area Parkir *</label>
                    <select id="id_area" name="id_area" required>
                        <option value="">-- Pilih Area --</option>
                        <?php foreach ($area_array as $area): ?>
                        <option value="<?php echo $area['id_area']; ?>">
                            <?php echo $area['nama_area'] . ' (' . $area['terisi'] . '/' . $area['kapasitas'] . ')'; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="id_tarif">Tarif *</label>
                    <select id="id_tarif" name="id_tarif" required style="background-color: #f0f0f0; color: #666; cursor: not-allowed;">
                        <option value="">-- Tarif akan otomatis sesuai kendaraan --</option>
                        <?php foreach ($tarif_array as $tarif): ?>
                        <option value="<?php echo $tarif['id_tarif']; ?>" data-jenis="<?php echo $tarif['jenis_kendaraan']; ?>">
                            <?php echo ucfirst($tarif['jenis_kendaraan']) . ' - Rp ' . number_format($tarif['tarif_per_jam'], 0, ',', '.') . '/jam'; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="note">ⓘ Tarif ditentukan secara otomatis berdasarkan jenis kendaraan yang dipilih dan tidak bisa diubah</p>

                    <!-- Peringatan jika tarif tidak ditemukan -->
                    <div id="tarif-not-found" class="tarif-warning"></div>
                </div>
                
                <div class="form-group">
                    <label for="waktu_masuk">Waktu Masuk *</label>
                    <input type="datetime-local" id="waktu_masuk" name="waktu_masuk" readonly style="background-color: #f0f0f0; color: #666; cursor: not-allowed;">
                    <p class="note">ⓘ Waktu Otomatis Sekarang</p>
                </div>
                
                <div class="form-group">
                    <label for="waktu_keluar">Waktu Keluar</label>
                    <input type="datetime-local" id="waktu_keluar" name="waktu_keluar">
                    <p class="note">Kosongkan jika kendaraan masih parkir</p>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" class="btn btn-submit">Simpan</button>
                    <a href="transaksi.php" class="btn btn-batal" style="text-decoration: none; text-align: center;">Batal</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const waktuMasukInput = document.getElementById('waktu_masuk');
        const waktuKeluarInput = document.getElementById('waktu_keluar');
        const tarifSelect = document.getElementById('id_tarif');
        const tarifNotFound = document.getElementById('tarif-not-found');
        const form = document.querySelector('form');
        
        // Set waktu masuk otomatis ke sekarang (fixed)
        function setDefaultWaktuMasuk() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            
            const currentDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
            waktuMasukInput.value = currentDateTime;
            setMinWaktuKeluar();
        }
        
        // Set minimum waktu keluar = waktu masuk
        function setMinWaktuKeluar() {
            if (waktuMasukInput.value) {
                waktuKeluarInput.min = waktuMasukInput.value;
            }
        }
        
        // Validasi saat input waktu keluar
        waktuKeluarInput.addEventListener('change', validateWaktuKeluar);
        
        function validateWaktuKeluar() {
            const waktuMasuk = waktuMasukInput.value;
            const waktuKeluar = waktuKeluarInput.value;
            
            if (!waktuKeluar) return;
            
            if (!waktuMasuk) {
                alert('⚠ Isi waktu masuk terlebih dahulu!');
                waktuKeluarInput.value = '';
                return;
            }
            
            if (waktuKeluar <= waktuMasuk) {
                alert('⚠ Waktu keluar HARUS lebih besar dari waktu masuk!\n\nWaktu masuk: ' + waktuMasuk.replace('T', ' '));
                waktuKeluarInput.value = '';
                return;
            }
            
            const masuk = new Date(waktuMasuk + ':00Z');
            const keluar = new Date(waktuKeluar + ':00Z');
            const selisihMenit = Math.floor((keluar - masuk) / 60000);
            const selisihJam = Math.ceil(selisihMenit / 60);
            
            console.log('✓ Data valid | Durasi: ' + selisihJam + ' jam');
        }
        
        // Validasi final saat submit
        form.addEventListener('submit', function(e) {
            // Cek apakah tarif tidak ditemukan
            if (tarifNotFound.style.display === 'block') {
                e.preventDefault();
                alert('⚠ Tidak dapat menyimpan transaksi karena tarif untuk kendaraan ini belum tersedia!\n\nSilakan tambahkan tarif terlebih dahulu di menu Manajemen Tarif.');
                return;
            }

            const waktuKeluar = waktuKeluarInput.value;
            const waktuMasuk = waktuMasukInput.value; // Hanya untuk referensi di error message
            
            // Validasi waktu_masuk dilakukan di server saja (tidak di client) karena server yang mengontrol timestampnya
            if (waktuKeluar && waktuKeluar <= waktuMasuk) {
                e.preventDefault();
                alert('⚠ Waktu keluar harus lebih besar dari waktu masuk!\n\nWaktu masuk: ' + waktuMasuk.replace('T', ' '));
                return;
            }
        });
        
        // Sinkronisasi tarif saat kendaraan dipilih
        document.getElementById('id_kendaraan').addEventListener('change', function() {
            const selectedKendaraan = this.options[this.selectedIndex];
            const jenisKendaraan = selectedKendaraan.dataset.jenis;

            // Reset peringatan & tarif
            tarifNotFound.style.display = 'none';
            tarifNotFound.innerHTML = '';
            tarifSelect.value = '';
            tarifSelect.dataset.lastValue = '';

            if (jenisKendaraan) {
                let found = false;
                for (let option of tarifSelect.options) {
                    if (option.dataset.jenis === jenisKendaraan) {
                        tarifSelect.value = option.value;
                        tarifSelect.dataset.lastValue = option.value;
                        found = true;
                        break;
                    }
                }

                if (!found) {
                    tarifNotFound.innerHTML = `⚠️ Belum ada tarif untuk kendaraan jenis <strong>${jenisKendaraan}</strong>.`;
                    tarifNotFound.style.display = 'block';
                }
            }
        });
        
        // Prevent user dari mengubah tarif manually
        tarifSelect.addEventListener('change', function() {
            const lastValue = this.dataset.lastValue || '';
            if (this.value !== lastValue && lastValue !== '') {
                alert('⚠ Tarif tidak bisa diubah! Tarif ditentukan otomatis berdasarkan jenis kendaraan.');
                this.value = lastValue;
            }
            this.dataset.lastValue = this.value;
        });
        
        // Prevent click/focus pada tarif select jika kendaraan belum dipilih
        tarifSelect.addEventListener('click', function(e) {
            if (this.value === '' && tarifNotFound.style.display !== 'block') {
                alert('⚠ Pilih kendaraan terlebih dahulu!');
                document.getElementById('id_kendaraan').focus();
                e.preventDefault();
            }
        });
        
        // Initialize saat page load
        window.addEventListener('load', setDefaultWaktuMasuk);
    </script>
</body>
</html>