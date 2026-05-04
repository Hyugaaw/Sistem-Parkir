<?php
session_start();
include './../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

$id_area = isset($_GET['id']) ? $_GET['id'] : '';
$error = '';
$success = '';

// Ambil data area
if ($id_area) {
    $query = "SELECT * FROM tb_area_parkir WHERE id_area = '$id_area'";
    $result = mysqli_query($koneksi, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $area = mysqli_fetch_assoc($result);
    } else {
        header("Location: area.php");
        exit();
    }
} else {
    header("Location: area.php");
    exit();
}

// Proses update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_area = mysqli_real_escape_string($koneksi, $_POST['nama_area']);
    $kapasitas = mysqli_real_escape_string($koneksi, $_POST['kapasitas']);
    
    // Validasi kapasitas
    if (!is_numeric($kapasitas) || $kapasitas < 1) {
        $error = "Kapasitas harus berupa angka positif minimal 1!";
    } else {
        // Cek apakah kapasitas baru lebih kecil dari terisi saat ini
        if ($kapasitas < $area['terisi']) {
            $error = "Kapasitas baru ($kapasitas) tidak boleh kurang dari jumlah terisi saat ini ({$area['terisi']})!";
        } else {
            // PENTING: Jangan update terisi langsung - terisi hanya diupdate otomatis via transaksi
            $update_query = "UPDATE tb_area_parkir SET 
                            nama_area = '$nama_area',
                            kapasitas = '$kapasitas'
                            WHERE id_area = '$id_area'";
            
            if (mysqli_query($koneksi, $update_query)) {
                $success = "Area parkir berhasil diupdate!";
                
                // Log aktivitas
                $id_admin = $_SESSION['id_user'];
                $aktivitas = "Edit area: $nama_area (kapasitas: {$area['kapasitas']} -> $kapasitas)";
                $waktu = date('Y-m-d H:i:s');
                $log_query = "INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_aktivitas) 
                             VALUES ('$id_admin', '$aktivitas', '$waktu')";
                mysqli_query($koneksi, $log_query);
                
                // Refresh data
                $query = "SELECT * FROM tb_area_parkir WHERE id_area = '$id_area'";
                $result = mysqli_query($koneksi, $query);
                $area = mysqli_fetch_assoc($result);
                
                header("refresh:2;url=area.php");
            } else {
                $error = "Error: " . mysqli_error($koneksi);
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
    <title>Edit Area Parkir - Aplikasi Parkir</title>
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
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group input:focus {
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
            text-decoration: none;
            text-align: center;
            display: inline-block;
            line-height: 44px;
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
        
        .warning-text {
            color: #f59e0b;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .info-text {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Edit Area Parkir</h1>
        </header>
        
        <div class="form-container">
            <?php if ($error): ?>
                <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" onsubmit="return validateKapasitas()">
                <div class="form-group">
                    <label for="nama_area">Nama Area *</label>
                    <input type="text" id="nama_area" name="nama_area" value="<?php echo htmlspecialchars($area['nama_area']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="kapasitas">Kapasitas (unit) *</label>
                    <input type="number" id="kapasitas" name="kapasitas" value="<?php echo $area['kapasitas']; ?>" required min="1" max="9999">
                    <div id="kapasitasWarning" class="warning-text" style="display: none;"></div>
                </div>
                
                <div class="form-group">
                    <label for="terisi">Terisi (unit)</label>
                    <input type="number" id="terisi" name="terisi" value="<?php echo $area['terisi']; ?>" readonly style="background-color: #f0f0f0; cursor: not-allowed;">
                    <p class="info-text">
                        ⓘ Nilai terisi diupdate otomatis berdasarkan transaksi parkir. Tidak bisa diubah secara manual.
                    </p>
                </div>

                <div class="form-group">
                    <label for="sisa_kapasitas">Sisa Kapasitas (unit)</label>
                    <input type="number" id="sisa_kapasitas" name="sisa_kapasitas" value="<?php echo $area['kapasitas'] - $area['terisi']; ?>" readonly style="background-color: #e8f5e9; cursor: not-allowed; font-weight: bold; color: #2e7d32;">
                    <p class="info-text">
                        ⓘ Sisa kapasitas = Kapasitas - Terisi. Update otomatis saat ada transaksi parkir.
                    </p>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" class="btn btn-submit">Update</button>
                    <a href="area.php" class="btn btn-batal">Batal</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const terisiValue = <?php echo $area['terisi']; ?>;
        
        // Update sisa kapasitas secara real-time saat kapasitas berubah
        const kapasitasInput = document.getElementById('kapasitas');
        const sisaKapasitasInput = document.getElementById('sisa_kapasitas');
        const kapasitasWarning = document.getElementById('kapasitasWarning');
        
        function updateSisaKapasitas() {
            const kapasitas = parseInt(kapasitasInput.value) || 0;
            const sisa = Math.max(0, kapasitas - terisiValue);
            sisaKapasitasInput.value = sisa;
            
            // Validasi kapasitas tidak boleh kurang dari terisi
            if (kapasitas < terisiValue) {
                kapasitasWarning.style.display = 'block';
                kapasitasWarning.innerHTML = `⚠️ Peringatan: Kapasitas baru (${kapasitas}) lebih kecil dari jumlah terisi saat ini (${terisiValue}). Tidak dapat menyimpan perubahan!`;
                kapasitasWarning.style.color = '#dc2626';
            } else {
                kapasitasWarning.style.display = 'none';
            }
        }
        
        function validateKapasitas() {
            const kapasitas = parseInt(kapasitasInput.value) || 0;
            
            if (kapasitas < terisiValue) {
                alert(`Kapasitas baru (${kapasitas}) tidak boleh kurang dari jumlah terisi saat ini (${terisiValue})!\n\nSilakan masukkan kapasitas minimal ${terisiValue} atau kosongkan area parkir terlebih dahulu.`);
                kapasitasInput.focus();
                return false;
            }
            
            if (kapasitas > 9999) {
                alert("Kapasitas maksimal adalah 9999 unit!");
                kapasitasInput.focus();
                return false;
            }
            
            return true;
        }
        
        // Event listeners
        kapasitasInput.addEventListener('change', updateSisaKapasitas);
        kapasitasInput.addEventListener('input', updateSisaKapasitas);
        
        // Update sisa kapasitas saat halaman dimuat
        window.addEventListener('load', updateSisaKapasitas);
    </script>
</body>
</html>