<?php
session_start();
include './../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jenis_kendaraan = mysqli_real_escape_string($koneksi, $_POST['jenis_kendaraan']);
    $tarif_per_jam = (int)$_POST['tarif_per_jam']; // Convert ke integer
    
    // Validasi tarif
    if ($tarif_per_jam < 1 || $tarif_per_jam > 1000000) {
        $error = "Tarif harus antara 1 hingga 1000000!";
    } else {
        // Cek jenis kendaraan sudah ada atau tidak
        $cek_query = "SELECT jenis_kendaraan FROM tb_tarif WHERE jenis_kendaraan = '$jenis_kendaraan'";
        $cek_result = mysqli_query($koneksi, $cek_query);
        
        if (mysqli_num_rows($cek_result) > 0) {
            $error = "Jenis kendaraan sudah terdaftar!";
        } else {
            // Insert tarif - tanpa tanda kutip pada nilai numerik
            $query = "INSERT INTO tb_tarif (jenis_kendaraan, tarif_per_jam) 
                     VALUES ('$jenis_kendaraan', $tarif_per_jam)";
            
            if (mysqli_query($koneksi, $query)) {
                $success = "Tarif berhasil ditambahkan!";
                
                // Log aktivitas
                $id_user = $_SESSION['id_user'];
                $aktivitas = "Tambah tarif: $jenis_kendaraan";
                $waktu = date('Y-m-d H:i:s');
                $log_query = "INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_aktivitas) 
                             VALUES ('$id_user', '$aktivitas', '$waktu')";
                mysqli_query($koneksi, $log_query);
                
                header("refresh:2;url=tarif.php");
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
    <title>Tambah Tarif - Aplikasi Parkir</title>
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
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Tambah Tarif Parkir</h1>
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
                    <label for="jenis_kendaraan">Jenis Kendaraan *</label>
                    <select id="jenis_kendaraan" name="jenis_kendaraan" required>
                        <option value="">-- Pilih Jenis --</option>
                        <option value="motor">Motor</option>
                        <option value="mobil">Mobil</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="tarif_per_jam">Tarif Per Jam (Rp) *</label>
                    <input type="number" id="tarif_per_jam" name="tarif_per_jam" required min="1" max="1000000" placeholder="Contoh: 15000">
                </div>
                
                <div class="form-buttons">
                    <button type="submit" class="btn btn-submit">Simpan</button>
                    <a href="tarif.php" class="btn btn-batal" style="text-decoration: none; text-align: center;">Batal</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
