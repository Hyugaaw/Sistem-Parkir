<?php
session_start();
include './../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

// Konfigurasi pagination
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Proses hapus kendaraan jika ada parameter delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id_kendaraan_to_delete = (int)$_GET['delete'];
    
    $check_query = "SELECT id_kendaraan, plat_nomor FROM tb_kendaraan WHERE id_kendaraan = $id_kendaraan_to_delete";
    $check_result = mysqli_query($koneksi, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $kendaraan_data = mysqli_fetch_assoc($check_result);
        
        $check_transaksi_aktif = "SELECT COUNT(*) as total FROM tb_transaksi WHERE id_kendaraan = $id_kendaraan_to_delete AND status = 'masuk'";
        $transaksi_aktif_result = mysqli_query($koneksi, $check_transaksi_aktif);
        $transaksi_aktif_count = mysqli_fetch_assoc($transaksi_aktif_result)['total'];
        
        if ($transaksi_aktif_count > 0) {
            $error_message = "❌ Tidak dapat menghapus kendaraan dengan plat nomor '{$kendaraan_data['plat_nomor']}' karena masih ada $transaksi_aktif_count transaksi parkir yang BELUM SELESAI (masih dalam status masuk). Selesaikan transaksi tersebut terlebih dahulu sebelum menghapus kendaraan.";
        } else {
            mysqli_begin_transaction($koneksi);
            
            try {
                $delete_query = "DELETE FROM tb_kendaraan WHERE id_kendaraan = $id_kendaraan_to_delete";
                if (!mysqli_query($koneksi, $delete_query)) {
                    throw new Exception("Gagal menghapus kendaraan: " . mysqli_error($koneksi));
                }
                
                mysqli_commit($koneksi);
                
                $success_message = "✅ Kendaraan dengan plat nomor '{$kendaraan_data['plat_nomor']}' berhasil dihapus. Data transaksi lama tetap tersimpan.";
                
                $admin_id = $_SESSION['id_user'];
                $aktivitas = "Menghapus kendaraan dengan ID: $id_kendaraan_to_delete (Plat: {$kendaraan_data['plat_nomor']})";
                $waktu = date('Y-m-d H:i:s');
                $log_query = "INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_aktivitas) VALUES ($admin_id, '$aktivitas', '$waktu')";
                mysqli_query($koneksi, $log_query);
                
                $count_query_after = "SELECT COUNT(*) as total FROM tb_kendaraan";
                $count_result_after = mysqli_query($koneksi, $count_query_after);
                $total_data_after = mysqli_fetch_assoc($count_result_after)['total'];
                $total_pages_after = ceil($total_data_after / $limit);
                
                $redirect_page = $page;
                if ($total_pages_after > 0 && $page > $total_pages_after) {
                    $redirect_page = $total_pages_after;
                }
                
                if ($redirect_page != $page) {
                    header("Location: kendaraan.php?page=$redirect_page");
                    exit();
                }
                
            } catch (Exception $e) {
                mysqli_rollback($koneksi);
                $error_message = "❌ Gagal menghapus kendaraan: " . $e->getMessage();
            }
        }
    } else {
        $error_message = "❌ Kendaraan tidak ditemukan!";
    }
}

// Hitung total data untuk pagination
$count_query = "SELECT COUNT(*) as total FROM tb_kendaraan";
$count_result = mysqli_query($koneksi, $count_query);
$total_data = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_data / $limit);

// Ambil data kendaraan dengan pagination
$query = "SELECT * FROM tb_kendaraan 
          ORDER BY id_kendaraan LIMIT $offset, $limit";
$result = mysqli_query($koneksi, $query);
$kendaraans = [];

while ($row = mysqli_fetch_assoc($result)) {
    $kendaraans[] = $row;
}

$nama    = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Admin');
$inisial = strtoupper(substr($_SESSION['nama_lengkap'] ?? 'A', 0, 1));

function safeHtml($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kendaraan — ParkAdmin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <style>
        :root {
            --white:       #ffffff;
            --bg:          #f0f2f8;
            --bg2:         #e8ebf4;
            --border:      #e2e6f0;
            --text:        #14172b;
            --text2:       #3d4263;
            --muted:       #8b92b8;
            --dim:         #c5cad8;
            --blue:        #4361ee;
            --blue-light:  #6c8fff;
            --blue-bg:     #edf0ff;
            --blue-soft:   rgba(67,97,238,.10);
            --green:       #12b76a;
            --green-bg:    #e6f9f1;
            --orange:      #f59e0b;
            --orange-bg:   #fff8e6;
            --red:         #ef4444;
            --red-bg:      #fef2f2;
            --purple:      #8b5cf6;
            --purple-bg:   #f0ebff;
            --teal:        #06b6d4;
            --teal-bg:     #e0f9fd;
            --sidebar-w:   250px;
            --topbar-h:    64px;
            --radius:      16px;
            --radius-sm:   10px;
            --shadow-sm:   0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
            --shadow-md:   0 4px 16px rgba(0,0,0,.08), 0 2px 6px rgba(0,0,0,.05);
            --shadow-lg:   0 12px 40px rgba(0,0,0,.12), 0 4px 12px rgba(0,0,0,.06);
        }

        * { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            -webkit-font-smoothing: antialiased;
        }

        /* SIDEBAR */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--white);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 300;
            transition: transform .32s cubic-bezier(.4,0,.2,1), box-shadow .32s;
        }

        .sb-logo {
            padding: 0 20px;
            height: var(--topbar-h);
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }

        .sb-logo-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--blue), var(--blue-light));
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(67,97,238,.35);
            flex-shrink: 0;
        }

        .sb-logo-name { font-size: 15.5px; font-weight: 800; letter-spacing: -.4px; color: var(--text); }
        .sb-logo-sub  { font-size: 10.5px; color: var(--muted); margin-top: 1px; font-weight: 500; }

        .sb-close-btn {
            display: none;
            margin-left: auto;
            background: var(--bg);
            border: 1px solid var(--border);
            width: 32px; height: 32px;
            border-radius: 9px;
            align-items: center; justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            transition: background .18s, border-color .18s;
        }

        .sb-close-btn:hover { background: var(--bg2); border-color: var(--dim); }
        .sb-close-btn svg { display:block; }

        .sb-nav {
            padding: 14px 12px;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sb-nav::-webkit-scrollbar { width: 4px; }
        .sb-nav::-webkit-scrollbar-track { background: transparent; }
        .sb-nav::-webkit-scrollbar-thumb { background: var(--dim); border-radius: 99px; }

        .sb-section-label {
            font-size: 9.5px;
            font-weight: 800;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--dim);
            padding: 0 10px;
            margin: 16px 0 5px;
        }

        .sb-section-label:first-child { margin-top: 4px; }

        .sb-link {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 10px 10px;
            border-radius: 11px;
            color: var(--muted);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            margin-bottom: 2px;
            transition: all .18s;
            position: relative;
        }

        .sb-ico {
            width: 34px; height: 34px;
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
            transition: background .18s, transform .2s;
        }

        .sb-link:hover { color: var(--text2); background: var(--bg); }
        .sb-link:hover .sb-ico { background: var(--bg2); transform: scale(1.05); }

        .sb-link.active { color: var(--blue); background: var(--blue-bg); font-weight: 600; }
        .sb-link.active .sb-ico { background: var(--blue-soft); }
        .sb-link.active::before {
            content: '';
            position: absolute;
            right: 0; top: 50%;
            transform: translateY(-50%);
            width: 3px; height: 20px;
            background: var(--blue);
            border-radius: 99px 0 0 99px;
        }

        .sb-footer { padding: 12px; border-top: 1px solid var(--border); flex-shrink: 0; }

        .sb-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 10px;
            border-radius: 12px;
            background: var(--bg);
            border: 1px solid var(--border);
            transition: background .18s;
        }

        .sb-user:hover { background: var(--bg2); }

        .sb-avatar {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--blue), var(--purple));
            color: white;
            font-size: 13px;
            font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(139,92,246,.3);
        }

        .sb-uname { font-size: 12.5px; font-weight: 700; color: var(--text); line-height: 1.2; }
        .sb-urole { font-size: 10.5px; color: var(--muted); font-weight: 500; }

        .sb-logout {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 9px 10px;
            margin-top: 6px;
            border-radius: 11px;
            color: var(--red);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: background .18s;
        }

        .sb-logout:hover { background: var(--red-bg); }

        /* MAIN */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            min-width: 0;
        }

        /* TOPBAR */
        .topbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            height: var(--topbar-h);
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky; top: 0;
            z-index: 200;
            box-shadow: var(--shadow-sm);
        }

        .topbar-left { display:flex; align-items:center; gap:14px; }
        .topbar-right { display:flex; align-items:center; gap:10px; }

        .burger {
            display: none;
            width: 38px; height: 38px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            cursor: pointer;
            align-items: center; justify-content: center;
            flex-direction: column;
            gap: 5px;
            transition: background .18s, border-color .18s;
            flex-shrink: 0;
        }

        .burger:hover { background: var(--bg2); border-color: var(--dim); }

        .burger span {
            display: block;
            width: 18px; height: 2px;
            background: var(--text2);
            border-radius: 2px;
            transition: all .3s cubic-bezier(.4,0,.2,1);
        }

        .burger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
        .burger.open span:nth-child(2) { opacity:0; transform: scaleX(0); }
        .burger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

        .topbar-breadcrumb { display:flex; align-items:center; gap:7px; }
        .breadcrumb-home { font-size: 13px; color: var(--muted); font-weight: 500; }
        .breadcrumb-sep  { color: var(--dim); font-size: 12px; }
        .topbar-page     { font-size: 14.5px; font-weight: 700; color: var(--text); }

        .tb-avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--blue), var(--purple));
            color: white;
            font-size: 13px;
            font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 8px rgba(67,97,238,.28);
            cursor: pointer;
        }

        /* Alert Messages */
        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 500;
            animation: fadeUp .3s ease both;
        }

        .alert-success {
            background: var(--green-bg);
            color: var(--green);
            border-left: 4px solid var(--green);
        }

        .alert-error {
            background: var(--red-bg);
            color: var(--red);
            border-left: 4px solid var(--red);
        }

        /* CONTENT */
        .content { padding: 28px; flex: 1; }

        .welcome { margin-bottom: 24px; animation: fadeUp .35s ease both; }

        .welcome-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .welcome h1 { font-size: 22px; font-weight: 800; letter-spacing: -.5px; color: var(--text); }
        .welcome p  { font-size: 13.5px; color: var(--muted); margin-top: 3px; }

        /* Filter Info */
        .filter-info {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 12px 16px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .filter-info-left { font-size: 12.5px; color: var(--muted); }
        .filter-info-left strong { color: var(--text); font-weight: 700; }

        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            animation: fadeUp .4s ease .1s both;
            flex-wrap: wrap;
            gap: 12px;
        }

        .section-title { font-size: 15.5px; font-weight: 800; letter-spacing: -.3px; }
        .section-sub   { font-size: 12.5px; color: var(--muted); margin-top: 2px; }

        .btn-primary {
            background: var(--blue);
            color: white;
            padding: 9px 18px;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            font-weight: 700;
            font-family: inherit;
            transition: background .18s, transform .18s, box-shadow .18s;
            box-shadow: 0 4px 12px rgba(67,97,238,.30);
        }

        .btn-primary:hover {
            background: #3451d1;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(67,97,238,.38);
        }

        /* Table */
        .table-container {
            border-radius: var(--radius);
        }

        .table-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            box-shadow: var(--shadow-sm);
            animation: fadeUp .45s ease .12s both;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 950px;
        }

        thead tr { background: var(--bg); }

        thead th {
            padding: 12px 16px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .9px;
            text-transform: uppercase;
            color: var(--muted);
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background .15s;
        }

        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #f7f8fd; }

        tbody td {
            padding: 12px 16px;
            font-size: 13px;
            vertical-align: middle;
        }

        .td-id {
            font-size: 12px;
            color: var(--muted);
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .td-plat {
            font-weight: 700;
            color: var(--text);
            font-family: monospace;
            font-size: 13px;
        }

        /* Badge untuk jenis kendaraan */
        .badge-jenis {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 99px;
        }

        .b-motor  { background: var(--teal-bg);   color: var(--teal);   }
        .b-mobil  { background: var(--blue-bg);   color: var(--blue);   }
        .b-truk   { background: var(--orange-bg); color: var(--orange); }

        /* Action buttons */
        .action-group { display: flex; align-items: center; gap: 8px; }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 11px;
            font-weight: 600;
            font-family: inherit;
            transition: all .18s;
        }

        .btn-action:hover { opacity: .85; transform: translateY(-1px); }

        .btn-edit   { background: var(--blue-bg); color: var(--blue); }
        .btn-edit:hover { background: var(--blue); color: white; }

        .btn-delete { background: var(--red-bg); color: var(--red); }
        .btn-delete:hover { background: var(--red); color: white; }

        /* Empty state */
        .empty-row td {
            text-align: center;
            padding: 40px;
            color: var(--muted);
            font-size: 13.5px;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 24px;
            flex-wrap: wrap;
        }

        .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 10px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 9px;
            color: var(--text2);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all .18s;
            cursor: pointer;
        }

        .page-link:hover { background: var(--bg); border-color: var(--dim); color: var(--blue); }
        .page-link.active { background: var(--blue); border-color: var(--blue); color: white; }
        .page-link.disabled { opacity: 0.5; pointer-events: none; }

        .page-input-group {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: 4px 8px;
            height: 36px;
        }

        .page-input-group span { font-size: 12px; color: var(--muted); }

        .page-input {
            width: 55px;
            padding: 4px 6px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 12px;
            text-align: center;
            font-family: inherit;
            background: var(--white);
            color: var(--text);
            outline: none;
        }

        .page-input:focus { border-color: var(--blue); }

        .page-go-btn {
            background: var(--blue);
            border: none;
            padding: 4px 10px;
            border-radius: 6px;
            color: white;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: background .18s;
            font-family: inherit;
        }

        .page-go-btn:hover { background: var(--blue-light); }

        .page-info { font-size: 12px; color: var(--muted); margin-left: 12px; }

        .page-dots {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            background: transparent;
            color: var(--blue);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border-radius: 9px;
            transition: all .18s;
            padding: 0 4px;
        }

        .page-dots:hover { background: var(--blue-bg); }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.show { display: flex; }

        .modal-content {
            background: var(--white);
            border-radius: var(--radius);
            padding: 24px;
            max-width: 350px;
            width: 90%;
            text-align: center;
            box-shadow: var(--shadow-lg);
        }

        .modal-content h3 { margin-bottom: 16px; color: var(--text); }

        .modal-input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 14px;
            margin-bottom: 16px;
            font-family: inherit;
        }

        .modal-buttons { display: flex; gap: 10px; justify-content: center; }

        .modal-btn {
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-family: inherit;
        }

        .modal-btn.confirm { background: var(--blue); color: white; }
        .modal-btn.cancel  { background: var(--bg); color: var(--text2); border: 1px solid var(--border); }

        /* Overlay */
        .overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(10,12,28,.25);
            backdrop-filter: blur(3px);
            z-index: 290;
            opacity: 0;
            transition: opacity .3s;
        }

        .overlay.active { display: block; opacity: 1; }

        @keyframes fadeUp {
            from { opacity:0; transform:translateY(14px); }
            to   { opacity:1; transform:translateY(0); }
        }

        /* ═══════════════════════════════════
           RESPONSIVE — TABLET
        ═══════════════════════════════════ */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); box-shadow: none; }
            .sidebar.open { transform: translateX(0); box-shadow: var(--shadow-lg); }
            .sb-close-btn { display: flex; }
            .main { margin-left: 0 !important; }
            .burger { display: flex; }
            .topbar { padding: 0 16px; }
            .content { padding: 16px; }

            thead th { padding: 10px 8px; font-size: 10px; }
            tbody td { padding: 10px 8px; }

            .pagination { gap: 6px; }
            .page-link, .page-dots { min-width: 32px; height: 32px; font-size: 12px; }
            .page-input-group { height: 32px; }
        }

        /* ═══════════════════════════════════
           RESPONSIVE — MOBILE KECIL
        ═══════════════════════════════════ */
        @media (max-width: 640px) {
            .content { padding: 12px; }

            .section-head { flex-direction: column; align-items: flex-start; }
            .btn-primary { width: 100%; justify-content: center; }
            .filter-info { flex-direction: column; align-items: flex-start; }

            .btn-action { font-size: 10px; padding: 4px 7px; }
            .btn-action span { display: none; }

            .page-input-group { flex-wrap: wrap; height: auto; padding: 6px 8px; }
            .page-info { width: 100%; text-align: center; margin: 8px 0 0; }
        }

        /* ═══════════════════════════════════
           RESPONSIVE — MOBILE SANGAT KECIL
        ═══════════════════════════════════ */
        @media (max-width: 400px) {
            .topbar-page { font-size: 13px; }
            .welcome h1  { font-size: 18px; }
            .action-group { flex-direction: column; align-items: stretch; }
            .btn-action { justify-content: center; width: 100%; }
        }
    </style>
</head>
<body>

<div class="overlay" id="overlay"></div>

<!-- Modal Lompat Halaman -->
<div id="pageModal" class="modal">
    <div class="modal-content">
        <h3>Lompat ke halaman</h3>
        <input type="number" id="pageNumberInput" class="modal-input" min="1" max="<?php echo $total_pages; ?>" placeholder="Masukkan nomor halaman">
        <div class="modal-buttons">
            <button onclick="goToPage()" class="modal-btn confirm">Lompat</button>
            <button onclick="closeModal()" class="modal-btn cancel">Batal</button>
        </div>
    </div>
</div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">

    <div class="sb-logo">
        <div class="sb-logo-icon">🅿️</div>
        <div>
            <div class="sb-logo-name">ParkAdmin</div>
            <div class="sb-logo-sub">Management System</div>
        </div>
        <button class="sb-close-btn" id="sbCloseBtn" title="Tutup menu" aria-label="Tutup menu">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                <path d="M1 1L13 13M13 1L1 13" stroke="#8b92b8" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>
    </div>

    <nav class="sb-nav">
        <div class="sb-section-label">Utama</div>
        <a href="index.php" class="sb-link">
            <div class="sb-ico">🏠</div>
            Dashboard
        </a>
        <a href="user.php" class="sb-link">
            <div class="sb-ico">👥</div>
            Kelola User
        </a>
        <a href="tarif.php" class="sb-link">
            <div class="sb-ico">💰</div>
            Kelola Tarif
        </a>
        <a href="area.php" class="sb-link">
            <div class="sb-ico">🗺️</div>
            Kelola Area
        </a>
        <a href="kendaraan.php" class="sb-link active">
            <div class="sb-ico">🚗</div>
            Kelola Kendaraan
        </a>

        <div class="sb-section-label">Laporan</div>
        <a href="log_aktivitas.php" class="sb-link">
            <div class="sb-ico">📋</div>
            Log Aktivitas
        </a>
    </nav>

    <div class="sb-footer">
        <div class="sb-user">
            <div class="sb-avatar"><?php echo $inisial; ?></div>
            <div>
                <div class="sb-uname"><?php echo $nama; ?></div>
                <div class="sb-urole">Administrator</div>
            </div>
        </div>
        <a href="./../auth/logout.php" class="sb-logout">
            <span>🚪</span> Keluar
        </a>
    </div>

</aside>

<!-- MAIN -->
<div class="main">

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="topbar-left">
            <button class="burger" id="burgerBtn" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="topbar-breadcrumb">
                <span class="breadcrumb-home">ParkAdmin</span>
                <span class="breadcrumb-sep">›</span>
                <span class="topbar-page">Kelola Kendaraan</span>
            </div>
        </div>
        <div class="topbar-right">
            <div class="tb-avatar" title="<?php echo $nama; ?>"><?php echo $inisial; ?></div>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="content">

        <!-- Welcome -->
        <div class="welcome">
            <div class="welcome-inner">
                <div>
                    <h1>Data Kendaraan 🚗</h1>
                    <p>Kelola daftar kendaraan yang terdaftar dalam sistem.</p>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            ✅ <?php echo $success_message; ?>
        </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
        <div class="alert alert-error">
            ❌ <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <!-- Filter Info -->
        <div class="filter-info">
            <div class="filter-info-left">
                📊 Menampilkan <strong><?php echo count($kendaraans); ?></strong> dari <strong><?php echo $total_data; ?></strong> kendaraan
            </div>
            <div class="filter-info-left">
                📄 <strong><?php echo $limit; ?></strong> data per halaman
            </div>
        </div>

        <!-- Section -->
        <div class="section-head">
            <div>
                <div class="section-title">Daftar Kendaraan</div>
                <div class="section-sub">Semua data kendaraan yang terdaftar</div>
            </div>
            <a href="tambah_kendaraan.php" class="btn-primary">+ Tambah Kendaraan</a>
        </div>

        <!-- Table -->
        <div class="table-container">
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Plat Nomor</th>
                            <th>Jenis</th>
                            <th>Warna</th>
                            <th>Pemilik</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($kendaraans) > 0): ?>
                            <?php foreach ($kendaraans as $kendaraan):
                                $jenis = strtolower($kendaraan['jenis_kendaraan'] ?? '');
                                if ($jenis === 'motor') {
                                    $badge_class = 'b-motor';
                                } elseif ($jenis === 'mobil') {
                                    $badge_class = 'b-mobil';
                                } elseif ($jenis === 'truk' || $jenis === 'truck') {
                                    $badge_class = 'b-truk';
                                } else {
                                    $badge_class = 'b-mobil';
                                }
                            ?>
                            <tr>
                                <td class="td-id">#<?php echo safeHtml($kendaraan['id_kendaraan']); ?></td>
                                <td class="td-plat"><?php echo safeHtml($kendaraan['plat_nomor']); ?></td>
                                <td>
                                    <span class="badge-jenis <?php echo $badge_class; ?>">
                                        <?php echo safeHtml($kendaraan['jenis_kendaraan']); ?>
                                    </span>
                                </td>
                                <td><?php echo safeHtml($kendaraan['warna']); ?></td>
                                <td><?php echo safeHtml($kendaraan['pemilik']); ?></td>
                                <td>
                                    <div class="action-group">
                                        <a href="edit_kendaraan.php?id=<?php echo $kendaraan['id_kendaraan']; ?>" class="btn-action btn-edit">✏️ Edit</a>
                                        <a href="?delete=<?php echo $kendaraan['id_kendaraan']; ?>&page=<?php echo $page; ?>" class="btn-action btn-delete" onclick="return confirm('⚠️ PERINGATAN!\n\nKendaraan yang sedang digunakan oleh transaksi parkir yang BELUM SELESAI (status masuk) TIDAK DAPAT DIHAPUS.\n\nJika kendaraan ini sudah tidak digunakan oleh transaksi aktif, maka akan dihapus dan data transaksi lama akan tetap tersimpan.\n\nYakin ingin menghapus kendaraan ini?')">🗑️ Hapus</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="empty-row">
                                <td colspan="6">Belum ada data kendaraan. Silakan tambah kendaraan baru.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="page-link">‹ Sebelumnya</a>
            <?php else: ?>
                <span class="page-link disabled">‹ Sebelumnya</span>
            <?php endif; ?>

            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($total_pages, $page + 2);
            
            if ($startPage > 1) {
                echo '<a href="?page=1" class="page-link">1</a>';
                if ($startPage > 2) {
                    echo '<span class="page-dots" onclick="openPageModal()">...</span>';
                }
            }
            
            for ($i = $startPage; $i <= $endPage; $i++): ?>
                <a href="?page=<?php echo $i; ?>" 
                   class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor;
            
            if ($endPage < $total_pages) {
                if ($endPage < $total_pages - 1) {
                    echo '<span class="page-dots" onclick="openPageModal()">...</span>';
                }
                echo '<a href="?page=' . $total_pages . '" class="page-link">' . $total_pages . '</a>';
            }
            ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="page-link">Selanjutnya ›</a>
            <?php else: ?>
                <span class="page-link disabled">Selanjutnya ›</span>
            <?php endif; ?>

            <div class="page-input-group">
                <span>Ke halaman</span>
                <input type="number" id="jumpToPage" class="page-input" min="1" max="<?php echo $total_pages; ?>" value="<?php echo $page; ?>">
                <button onclick="jumpToPage()" class="page-go-btn">Go</button>
            </div>

            <span class="page-info">
                Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?>
            </span>
        </div>
        <?php endif; ?>

    </div><!-- /content -->
</div><!-- /main -->

<script>
    /* ── Sidebar toggle ── */
    const sidebar    = document.getElementById('sidebar');
    const burgerBtn  = document.getElementById('burgerBtn');
    const sbCloseBtn = document.getElementById('sbCloseBtn');
    const overlay    = document.getElementById('overlay');

    function openSidebar() {
        sidebar.classList.add('open');
        burgerBtn.classList.add('open');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        burgerBtn.classList.remove('open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    function toggleSidebar() {
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    }

    burgerBtn.addEventListener('click', toggleSidebar);
    sbCloseBtn.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeSidebar();
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) closeSidebar();
    });

    /* ── Pagination Functions ── */
    function jumpToPage() {
        const input = document.getElementById('jumpToPage');
        let targetPage = parseInt(input.value);
        const maxPage = <?php echo $total_pages; ?>;
        
        if (isNaN(targetPage)) targetPage = 1;
        if (targetPage < 1) targetPage = 1;
        if (targetPage > maxPage) targetPage = maxPage;
        
        window.location.href = `?page=${targetPage}`;
    }

    const modal           = document.getElementById('pageModal');
    const pageNumberInput = document.getElementById('pageNumberInput');

    function openPageModal() {
        if (modal) {
            pageNumberInput.value = '';
            modal.classList.add('show');
            pageNumberInput.focus();
        }
    }

    function closeModal() {
        if (modal) modal.classList.remove('show');
    }

    function goToPage() {
        let targetPage = parseInt(pageNumberInput.value);
        const maxPage = <?php echo $total_pages; ?>;
        
        if (isNaN(targetPage)) targetPage = 1;
        if (targetPage < 1) targetPage = 1;
        if (targetPage > maxPage) targetPage = maxPage;
        
        closeModal();
        window.location.href = `?page=${targetPage}`;
    }

    if (pageNumberInput) {
        pageNumberInput.addEventListener('keypress', e => {
            if (e.key === 'Enter') goToPage();
        });
    }

    const jumpInput = document.getElementById('jumpToPage');
    if (jumpInput) {
        jumpInput.addEventListener('keypress', e => {
            if (e.key === 'Enter') jumpToPage();
        });
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && modal && modal.classList.contains('show')) closeModal();
    });

    if (modal) {
        modal.addEventListener('click', e => {
            if (e.target === modal) closeModal();
        });
    }
</script>

</body>
</html>