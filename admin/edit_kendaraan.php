<?php
session_start();
include './../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

$id_kendaraan = isset($_GET['id']) ? $_GET['id'] : '';
$error = '';
$success = '';

// Ambil data kendaraan
if ($id_kendaraan) {
    $query = "SELECT * FROM tb_kendaraan WHERE id_kendaraan = '$id_kendaraan'";
    $result = mysqli_query($koneksi, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $kendaraan = mysqli_fetch_assoc($result);
    } else {
        header("Location: kendaraan.php");
        exit();
    }
} else {
    header("Location: kendaraan.php");
    exit();
}

// Proses update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jenis_kendaraan = mysqli_real_escape_string($koneksi, $_POST['jenis_kendaraan']);
    $warna = mysqli_real_escape_string($koneksi, $_POST['warna']);
    $pemilik = mysqli_real_escape_string($koneksi, $_POST['pemilik']);
    
    $update_query = "UPDATE tb_kendaraan SET 
                    jenis_kendaraan = '$jenis_kendaraan',
                    warna = '$warna',
                    pemilik = '$pemilik'
                    WHERE id_kendaraan = '$id_kendaraan'";
    
    if (mysqli_query($koneksi, $update_query)) {
        $success = "Kendaraan berhasil diupdate!";
        
        // Log aktivitas
        $id_admin = $_SESSION['id_user'];
        $aktivitas = "Edit kendaraan: " . $kendaraan['plat_nomor'];
        $waktu = date('Y-m-d H:i:s');
        $log_query = "INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_aktivitas) 
                     VALUES ('$id_admin', '$aktivitas', '$waktu')";
        mysqli_query($koneksi, $log_query);
        
        // Refresh data
        $query = "SELECT * FROM tb_kendaraan WHERE id_kendaraan = '$id_kendaraan'";
        $result = mysqli_query($koneksi, $query);
        $kendaraan = mysqli_fetch_assoc($result);
        
        header("refresh:2;url=kendaraan.php");
    } else {
        $error = "Error: " . mysqli_error($koneksi);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kendaraan - Aplikasi Parkir</title>
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
            <h1>Edit Kendaraan</h1>
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
                    <input type="text" id="plat_nomor" value="<?php echo $kendaraan['plat_nomor']; ?>" readonly style="background: #f0f0f0;">
                </div>
                
                <div class="form-group">
                    <label for="jenis_kendaraan">Jenis Kendaraan *</label>
                    <select id="jenis_kendaraan" name="jenis_kendaraan" required>
                        <option value="motor" <?php echo $kendaraan['jenis_kendaraan'] == 'motor' ? 'selected' : ''; ?>>Motor</option>
                        <option value="mobil" <?php echo $kendaraan['jenis_kendaraan'] == 'mobil' ? 'selected' : ''; ?>>Mobil</option>
                        <option value="lainnya" <?php echo $kendaraan['jenis_kendaraan'] == 'lainnya' ? 'selected' : ''; ?>>Lainnya</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="warna">Warna *</label>
                    <input type="text" id="warna" name="warna" value="<?php echo $kendaraan['warna']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="pemilik">Pemilik *</label>
                    <input type="text" id="pemilik" name="pemilik" value="<?php echo $kendaraan['pemilik']; ?>" required>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" class="btn btn-submit">Update</button>
                    <a href="kendaraan.php" class="btn btn-batal" style="text-decoration: none; text-align: center;">Batal</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
