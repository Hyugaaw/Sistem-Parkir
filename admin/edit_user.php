<?php
session_start();
include './../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

$id_user = isset($_GET['id']) ? $_GET['id'] : '';
$error = '';
$success = '';

// Ambil data user
if ($id_user) {
    $query = "SELECT * FROM tb_user WHERE id_user = '$id_user'";
    $result = mysqli_query($koneksi, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
    } else {
        header("Location: user.php");
        exit();
    }
} else {
    header("Location: user.php");
    exit();
}

// Proses update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];
    $role = mysqli_real_escape_string($koneksi, $_POST['role']);
    $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;
    
    // Cek apakah username sudah digunakan oleh user lain (case-sensitive)
    $check_username_query = "SELECT id_user FROM tb_user WHERE BINARY username = BINARY '$username' AND id_user != '$id_user'";
    $check_result = mysqli_query($koneksi, $check_username_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "Username sudah digunakan oleh user lain!";
    } else {
        // Jika password kosong, jangan update password
        if (empty($password)) {
            $update_query = "UPDATE tb_user SET 
                            nama_lengkap = '$nama_lengkap',
                            username = '$username',
                            role = '$role',
                            status_aktif = '$status_aktif'
                            WHERE id_user = '$id_user'";
        } else {
            $update_query = "UPDATE tb_user SET 
                            nama_lengkap = '$nama_lengkap',
                            username = '$username',
                            password = '$password',
                            role = '$role',
                            status_aktif = '$status_aktif'
                            WHERE id_user = '$id_user'";
        }
        
        if (mysqli_query($koneksi, $update_query)) {
            $success = "User berhasil diupdate!";
            
            // Log aktivitas
            $id_admin = $_SESSION['id_user'];
            $aktivitas = "Edit user: " . $user['username'] . " -> " . $username;
            $waktu = date('Y-m-d H:i:s');
            $log_query = "INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_aktivitas) 
                         VALUES ('$id_admin', '$aktivitas', '$waktu')";
            mysqli_query($koneksi, $log_query);
            
            // Refresh data
            $query = "SELECT * FROM tb_user WHERE id_user = '$id_user'";
            $result = mysqli_query($koneksi, $query);
            $user = mysqli_fetch_assoc($result);
            
            header("refresh:2;url=user.php");
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
    <title>Edit User - Aplikasi Parkir</title>
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
        
        .form-group-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group-checkbox input {
            width: auto;
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
            <h1>Edit User</h1>
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
                    <label for="nama_lengkap">Nama Lengkap *</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    <p class="note">Username dapat diubah, pastikan tidak ada username yang sama</p>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Kosongkan jika tidak ingin mengubah">
                    <p class="note">Biarkan kosong untuk tidak mengubah password</p>
                </div>
                
                <div class="form-group">
                    <label for="role">Role *</label>
                    <select id="role" name="role" required>
                        <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="petugas" <?php echo $user['role'] == 'petugas' ? 'selected' : ''; ?>>Petugas</option>
                        <option value="owner" <?php echo $user['role'] == 'owner' ? 'selected' : ''; ?>>Owner</option>
                    </select>
                </div>
                
                <div class="form-group form-group-checkbox">
                    <input type="checkbox" id="status_aktif" name="status_aktif" value="1" <?php echo $user['status_aktif'] == 1 ? 'checked' : ''; ?>>
                    <label for="status_aktif">Aktifkan User</label>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" class="btn btn-submit">Update</button>
                    <a href="user.php" class="btn btn-batal">Batal</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>