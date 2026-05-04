<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
include './../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../../auth/login.php");
    exit();
}

// Konfigurasi pagination (sama dengan log_aktivitas.php)
$limit = 5; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Pesan jika transaksi berhasil dihapus (dari delete_transaksi.php)
if (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
    $success_message = "Transaksi berhasil dihapus.";
}

// Hitung total data untuk pagination
$count_query = "SELECT COUNT(*) as total FROM tb_transaksi t
                LEFT JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
                LEFT JOIN tb_tarif tf ON t.id_tarif = tf.id_tarif
                LEFT JOIN tb_area_parkir a ON t.id_area = a.id_area";
$count_result = mysqli_query($koneksi, $count_query);
$total_data = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_data / $limit);

// Ambil data transaksi dengan pagination
$query = "SELECT t.*, k.plat_nomor, k.jenis_kendaraan, tf.tarif_per_jam, a.nama_area 
          FROM tb_transaksi t
          LEFT JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
          LEFT JOIN tb_tarif tf ON t.id_tarif = tf.id_tarif
          LEFT JOIN tb_area_parkir a ON t.id_area = a.id_area
          ORDER BY t.waktu_masuk DESC 
          LIMIT $offset, $limit";
$result = mysqli_query($koneksi, $query);
$transaksis = [];

while ($row = mysqli_fetch_assoc($result)) {
    $transaksis[] = $row;
}

$nama    = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Petugas');
$inisial = strtoupper(substr($_SESSION['nama_lengkap'] ?? 'P', 0, 1));

// Helper function untuk handle null values
function safeHtml($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Parkir — ParkPetugas</title>
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
            --green:       #12b76a;
            --green-bg:    #e6f9f1;
            --orange:      #f59e0b;
            --orange-bg:   #fff8e6;
            --red:         #ef4444;
            --red-bg:      #fef2f2;
            --blue:        #4361ee;
            --blue-light:  #6c8fff;
            --blue-bg:     #edf0ff;
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

        /* ═══════════════════════════════════
           SIDEBAR (Petugas Version)
        ═══════════════════════════════════ */
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
            overflow: hidden;
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
            background: linear-gradient(135deg, var(--green), var(--teal));
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(18,183,106,.35);
            flex-shrink: 0;
        }

        .sb-logo-name {
            font-size: 15.5px;
            font-weight: 800;
            letter-spacing: -.4px;
            color: var(--text);
        }

        .sb-logo-sub {
            font-size: 10.5px;
            color: var(--muted);
            margin-top: 1px;
            font-weight: 500;
        }

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

        .sb-close-btn:hover {
            background: var(--bg2);
            border-color: var(--dim);
        }

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

        .sb-link:hover {
            color: var(--text2);
            background: var(--bg);
        }

        .sb-link:hover .sb-ico {
            background: var(--bg2);
            transform: scale(1.05);
        }

        .sb-link.active {
            color: var(--green);
            background: var(--green-bg);
            font-weight: 600;
        }

        .sb-link.active .sb-ico {
            background: rgba(18,183,106,.10);
        }

        .sb-link.active::before {
            content: '';
            position: absolute;
            right: 0; top: 50%;
            transform: translateY(-50%);
            width: 3px; height: 20px;
            background: var(--green);
            border-radius: 99px 0 0 99px;
        }

        .sb-footer {
            padding: 12px;
            border-top: 1px solid var(--border);
            flex-shrink: 0;
        }

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
            background: linear-gradient(135deg, var(--green), var(--teal));
            color: white;
            font-size: 13px;
            font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(18,183,106,.3);
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

        /* ═══════════════════════════════════
           MAIN
        ═══════════════════════════════════ */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            min-width: 0;
        }

        /* ═══════════════════════════════════
           TOPBAR
        ═══════════════════════════════════ */
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

        .topbar-left { display:flex; align-items:center; gap:14px; min-width: 0; }
        .topbar-right { display:flex; align-items:center; gap:10px; flex-shrink: 0; }

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

        .burger:hover {
            background: var(--bg2);
            border-color: var(--dim);
        }

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

        .topbar-breadcrumb {
            display: flex;
            align-items: center;
            gap: 7px;
            min-width: 0;
        }

        .breadcrumb-home {
            font-size: 13px;
            color: var(--muted);
            font-weight: 500;
            white-space: nowrap;
        }

        .breadcrumb-sep {
            color: var(--dim);
            font-size: 12px;
        }

        .topbar-page {
            font-size: 14.5px;
            font-weight: 700;
            color: var(--text);
            white-space: nowrap;
        }

        .tb-avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--green), var(--teal));
            color: white;
            font-size: 13px;
            font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 8px rgba(18,183,106,.28);
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

        /* ═══════════════════════════════════
           CONTENT
        ═══════════════════════════════════ */
        .content {
            padding: 28px;
            flex: 1;
        }

        /* Welcome */
        .welcome {
            margin-bottom: 24px;
            animation: fadeUp .35s ease both;
        }

        .welcome-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .welcome h1 {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -.5px;
            color: var(--text);
        }

        .welcome p {
            font-size: 13.5px;
            color: var(--muted);
            margin-top: 3px;
        }

        .welcome-date {
            font-size: 12.5px;
            color: var(--muted);
            font-weight: 500;
            background: var(--white);
            border: 1px solid var(--border);
            padding: 7px 14px;
            border-radius: 99px;
            display: flex;
            align-items: center;
            gap: 7px;
        }

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

        .filter-info-left {
            font-size: 12.5px;
            color: var(--muted);
        }

        .filter-info-left strong {
            color: var(--text);
            font-weight: 700;
        }

        .server-time {
            font-family: monospace;
            font-size: 12px;
            color: var(--green);
            background: var(--green-bg);
            padding: 4px 10px;
            border-radius: 99px;
        }

        /* Section head */
        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            animation: fadeUp .4s ease .1s both;
            flex-wrap: wrap;
            gap: 12px;
        }

        .section-title {
            font-size: 15.5px;
            font-weight: 800;
            letter-spacing: -.3px;
        }

        .section-sub {
            font-size: 12.5px;
            color: var(--muted);
            margin-top: 2px;
        }

        /* Button Primary */
        .btn-primary {
            background: var(--green);
            color: white;
            padding: 9px 18px;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: all .2s;
            box-shadow: 0 4px 12px rgba(18,183,106,.30);
            white-space: nowrap;
        }

        .btn-primary:hover {
            background: var(--teal);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(18,183,106,.38);
        }

        /* Table */
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
            min-width: 900px;
        }

        thead tr {
            background: var(--bg);
        }

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
            padding: 14px 16px;
            font-size: 13px;
        }

        /* Status Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 99px;
        }

        .badge-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .badge-success {
            background: var(--green-bg);
            color: var(--green);
        }
        .badge-success .badge-dot { background: var(--green); }

        .badge-warning {
            background: var(--orange-bg);
            color: var(--orange);
        }
        .badge-warning .badge-dot { background: var(--orange); }

        /* Timer */
        .timer {
            font-weight: 700;
            color: var(--orange);
            font-size: 12px;
            font-variant-numeric: tabular-nums;
            margin-top: 4px;
            display: inline-block;
        }

        .info-masuk {
            font-size: 10px;
            color: var(--muted);
            display: block;
        }

        /* Action Buttons */
        .action-group {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 10px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 11px;
            font-weight: 600;
            transition: all .18s;
            white-space: nowrap;
        }

        .btn-action:hover {
            transform: translateY(-1px);
            opacity: .85;
        }

        .btn-print {
            background: var(--blue-bg);
            color: var(--blue);
        }
        .btn-print:hover {
            background: var(--blue);
            color: white;
        }

        .btn-edit {
            background: var(--orange-bg);
            color: var(--orange);
        }
        .btn-edit:hover {
            background: var(--orange);
            color: white;
        }

        .btn-delete {
            background: var(--red-bg);
            color: var(--red);
        }
        .btn-delete:hover {
            background: var(--red);
            color: white;
        }

        /* Empty state */
        .empty-row td {
            text-align: center;
            padding: 40px;
            color: var(--muted);
            font-size: 13.5px;
        }

        /* Pagination (sama persis dengan log_aktivitas.php) */
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
            white-space: nowrap;
        }

        .page-link:hover {
            background: var(--bg);
            border-color: var(--dim);
            color: var(--green);
        }

        .page-link.active {
            background: var(--green);
            border-color: var(--green);
            color: white;
        }

        .page-link.disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        /* Pagination dengan input */
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

        .page-input-group span {
            font-size: 12px;
            color: var(--muted);
        }

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

        .page-input:focus {
            border-color: var(--green);
        }

        .page-go-btn {
            background: var(--green);
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

        .page-go-btn:hover {
            background: var(--teal);
        }

        .page-info {
            font-size: 12px;
            color: var(--muted);
            margin-left: 12px;
        }

        /* Clickable dots */
        .page-dots {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            background: transparent;
            color: var(--green);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border-radius: 9px;
            transition: all .18s;
            padding: 0 4px;
        }

        .page-dots:hover {
            background: var(--green-bg);
            color: var(--green);
        }

        /* Modal untuk lompat halaman */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: var(--white);
            border-radius: var(--radius);
            padding: 24px;
            max-width: 350px;
            width: 90%;
            text-align: center;
            box-shadow: var(--shadow-lg);
        }

        .modal-content h3 {
            margin-bottom: 16px;
            color: var(--text);
        }

        .modal-input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 14px;
            margin-bottom: 16px;
            font-family: inherit;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .modal-btn {
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-family: inherit;
        }

        .modal-btn.confirm {
            background: var(--green);
            color: white;
        }

        .modal-btn.cancel {
            background: var(--bg);
            color: var(--text2);
            border: 1px solid var(--border);
        }

        /* ═══════════════════════════════════
           ANIMATIONS
        ═══════════════════════════════════ */
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(14px); }
            to   { opacity:1; transform:translateY(0); }
        }

        /* ═══════════════════════════════════
           OVERLAY
        ═══════════════════════════════════ */
        .overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(10,12,28,.25);
            backdrop-filter: blur(3px);
            z-index: 290;
            opacity: 0;
            transition: opacity .3s;
        }

        .overlay.active {
            display: block;
            opacity: 1;
        }

        /* ═══════════════════════════════════
           RESPONSIVE
        ═══════════════════════════════════ */
        /* Tablet & mobile: sembunyikan sidebar, aktifkan burger */
        @media (max-width: 900px) {
            .sidebar {
                transform: translateX(-100%);
                box-shadow: none;
                width: 260px;
            }
            .sidebar.open {
                transform: translateX(0);
                box-shadow: var(--shadow-lg);
            }
            .sb-close-btn { display: flex; }
            .main { margin-left: 0; }
            .burger { display: flex; }
            .content { padding: 20px; }
        }

        /* Mobile 481–900px */
        @media (max-width: 768px) {
            .content { padding: 16px; }
            .welcome-date { display: none; }
            .section-head { flex-direction: column; align-items: flex-start; }
            .pagination { gap: 6px; }
            .page-link, .page-dots { min-width: 32px; height: 32px; font-size: 12px; }
            .page-input-group { height: 32px; }
            .filter-info { flex-direction: column; align-items: flex-start; gap: 8px; }
        }

        /* Mobile 480px ke bawah */
        @media (max-width: 480px) {
            .topbar { padding: 0 14px; }
            .content { padding: 14px 12px; }
            .welcome h1 { font-size: 18px; }
            .welcome p { font-size: 12.5px; }

            .pagination { gap: 5px; }
            .page-link, .page-dots { min-width: 30px; height: 30px; font-size: 11.5px; padding: 0 7px; }
            .page-input-group { height: 30px; }
            .page-info { display: none; }
        }

        /* Mobile kecil 380px ke bawah */
        @media (max-width: 380px) {
            .topbar { padding: 0 10px; }
            .topbar-page { font-size: 13px; }
            .breadcrumb-home { display: none; }
            .breadcrumb-sep  { display: none; }
            .content { padding: 12px 10px; }
            .welcome h1 { font-size: 16px; }
            .btn-primary { font-size: 12px; padding: 8px 13px; }
            .page-input-group { display: none; }
        }

        /* Extra kecil 320px ke bawah */
        @media (max-width: 320px) {
            .content { padding: 10px 8px; }
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

<!-- ═══════════════ SIDEBAR (Petugas) ═══════════════ -->
<aside class="sidebar" id="sidebar">

    <div class="sb-logo">
        <div class="sb-logo-icon">🅿️</div>
        <div>
            <div class="sb-logo-name">ParkPetugas</div>
            <div class="sb-logo-sub">Parkir System</div>
        </div>
        <button class="sb-close-btn" id="sbCloseBtn" title="Tutup menu" aria-label="Tutup menu">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                <path d="M1 1L13 13M13 1L1 13" stroke="#8b92b8" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>
    </div>

    <nav class="sb-nav">
        <div class="sb-section-label">Menu Utama</div>
        <a href="index.php" class="sb-link">
            <div class="sb-ico">🏠</div>
            Dashboard
        </a>
        <a href="transaksi.php" class="sb-link active">
            <div class="sb-ico">💰</div>
            Transaksi Parkir
        </a>
        <a href="struk.php" class="sb-link">
            <div class="sb-ico">🧾</div>
            Cetak Struk
        </a>
    </nav>

    <div class="sb-footer">
        <div class="sb-user">
            <div class="sb-avatar"><?php echo $inisial; ?></div>
            <div>
                <div class="sb-uname"><?php echo $nama; ?></div>
                <div class="sb-urole">Petugas Parkir</div>
            </div>
        </div>
        <a href="./../auth/logout.php" class="sb-logout">
            <span>🚪</span> Keluar
        </a>
    </div>

</aside>

<!-- ═══════════════ MAIN ═══════════════ -->
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
                <span class="breadcrumb-home">ParkPetugas</span>
                <span class="breadcrumb-sep">›</span>
                <span class="topbar-page">Transaksi Parkir</span>
            </div>
        </div>
        <div class="topbar-right">
            <!-- Live pill dihapus -->
            <div class="tb-avatar" title="<?php echo $nama; ?>"><?php echo $inisial; ?></div>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="content">

        <!-- Welcome -->
        <div class="welcome">
            <div class="welcome-inner">
                <div>
                    <h1>Transaksi Parkir 💰</h1>
                    <p>Kelola dan pantau semua transaksi parkir dengan timer realtime.</p>
                </div>
                <div class="welcome-date">
                    <span>📅</span>
                    <span id="currentDate">–</span>
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

        <!-- Filter Info (sama dengan log_aktivitas.php) -->
        <div class="filter-info">
            <div class="filter-info-left">
                📊 Menampilkan <strong><?php echo count($transaksis); ?></strong> dari <strong><?php echo $total_data; ?></strong> transaksi
            </div>
            <div class="filter-info-left">
                📄 <strong><?php echo $limit; ?></strong> data per halaman
            </div>
            <div class="filter-info-left">
                🕐 Server: <span class="server-time" id="serverTime">-</span>
            </div>
        </div>

        <!-- Section -->
        <div class="section-head">
            <div>
                <div class="section-title">Daftar Transaksi</div>
                <div class="section-sub">Transaksi dengan status "Masuk" akan otomatis keluar saat waktu habis</div>
            </div>
            <a href="tambah_transaksi.php" class="btn-primary">+ Transaksi Baru</a>
        </div>

        <!-- Table -->
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Plat Nomor</th>
                        <th>Waktu Masuk</th>
                        <th>Waktu Keluar</th>
                        <th>Area</th>
                        <th>Biaya</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($transaksis) > 0): ?>
                        <?php foreach ($transaksis as $t): ?>
                        <tr data-id="<?php echo $t['id_parkir']; ?>" 
                            data-status="<?php echo safeHtml($t['status']); ?>" 
                            data-waktu-masuk="<?php echo safeHtml($t['waktu_masuk']); ?>" 
                            data-waktu-keluar="<?php echo safeHtml($t['waktu_keluar']); ?>">
                            <td><?php echo safeHtml($t['id_parkir']); ?></td>
                            <td><strong><?php echo safeHtml($t['plat_nomor']); ?></strong></td>
                            <td><?php echo safeHtml($t['waktu_masuk']); ?></td>
                            <td class="timer-cell">
                                <?php if ($t['status'] == 'keluar'): ?>
                                    <?php echo safeHtml($t['waktu_keluar']); ?>
                                <?php else: ?>
                                    <span class="info-masuk" data-info>Menghitung durasi...</span>
                                    <span class="timer" data-timer>Memuat timer...</span>
                                <?php endif; ?>
                             </div>
                            <td><?php echo safeHtml($t['nama_area']); ?></td>
                            <td>Rp <?php echo number_format($t['biaya_total'] ?? 0, 0, ',', '.'); ?></td>
                            <td>
                                <?php if ($t['status'] == 'keluar'): ?>
                                    <span class="badge badge-success">
                                        <span class="badge-dot"></span>
                                        ✓ Keluar
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-warning">
                                        <span class="badge-dot"></span>
                                        ⏱ Masuk
                                    </span>
                                <?php endif; ?>
                             </div>
                            <td>
                                <div class="action-group">
                                    <a href="edit_transaksi.php?id=<?php echo $t['id_parkir']; ?>" class="btn-action btn-edit">✏️ Edit</a>
                                    <a href="delete_transaksi.php?id=<?php echo $t['id_parkir']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus transaksi ini?\n\n⚠️ TINDAKAN INI TIDAK DAPAT DIKEMBALIKAN!')">🗑️ Hapus</a>
                                </div>
                             </div>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="empty-row">
                            <td colspan="8">Belum ada data transaksi. Silakan tambah transaksi baru.<?php echo "\n"; ?>                            
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination (sama persis dengan log_aktivitas.php) -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <!-- Tombol Previous -->
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="page-link">‹ Sebelumnya</a>
            <?php else: ?>
                <span class="page-link disabled">‹ Sebelumnya</span>
            <?php endif; ?>

            <!-- Nomor Halaman dengan "..." yang bisa diklik -->
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

            <!-- Tombol Next -->
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="page-link">Selanjutnya ›</a>
            <?php else: ?>
                <span class="page-link disabled">Selanjutnya ›</span>
            <?php endif; ?>

            <!-- Input langsung untuk lompat halaman -->
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
    // Display current date
    const dateEl = document.getElementById('currentDate');
    if (dateEl) {
        const d = new Date();
        dateEl.textContent = d.toLocaleDateString('id-ID', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });
    }

    // Display server time
    function updateServerTime() {
        const now = new Date();
        const serverTimeEl = document.getElementById('serverTime');
        if (serverTimeEl) {
            serverTimeEl.textContent = now.toLocaleTimeString('id-ID');
        }
    }
    updateServerTime();
    setInterval(updateServerTime, 1000);

    // Format waktu untuk display
    function formatWaktu(detik) {
        if (detik < 0) return 'Waktu habis';
        if (detik < 60) return detik + ' detik';
        const menit = Math.floor(detik / 60);
        if (menit < 60) return menit + ' menit ' + (detik % 60) + ' detik';
        const jam = Math.floor(menit / 60);
        if (jam < 24) return jam + ' jam ' + (menit % 60) + ' menit';
        const hari = Math.floor(jam / 24);
        return hari + ' hari ' + (jam % 24) + ' jam';
    }

    // Flag untuk mencegah multiple request
    let isProcessing = false;

    // Update timer dan cek status otomatis keluar
    function updateTimers() {
        const rows = document.querySelectorAll('table tbody tr[data-status="masuk"]');
        
        rows.forEach((row) => {
            const waktuMasukStr = row.getAttribute('data-waktu-masuk');
            const waktuKeluarStr = row.getAttribute('data-waktu-keluar');
            
            if (waktuMasukStr && waktuKeluarStr && waktuKeluarStr !== '-' && waktuKeluarStr !== '' && waktuKeluarStr !== 'null') {
                try {
                    // Parse waktu masuk dan keluar
                    const parts_masuk = waktuMasukStr.trim().split(/[\s-:]+/);
                    const parts_keluar = waktuKeluarStr.trim().split(/[\s-:]+/);
                    
                    if (parts_masuk.length >= 6 && parts_keluar.length >= 6) {
                        const [tahun_m, bulan_m, hari_m, jam_m, menit_m, detik_m] = parts_masuk;
                        const [tahun_k, bulan_k, hari_k, jam_k, menit_k, detik_k] = parts_keluar;
                        
                        const waktuMasukObj = new Date(tahun_m, bulan_m - 1, hari_m, jam_m, menit_m, detik_m || 0);
                        const waktuKeluarObj = new Date(tahun_k, bulan_k - 1, hari_k, jam_k, menit_k, detik_k || 0);
                        const sekarang = new Date();
                        
                        // Hitung berapa lama sudah masuk
                        const sudah_masuk_detik = Math.floor((sekarang - waktuMasukObj) / 1000);
                        const sudah_masuk_menit = Math.floor(sudah_masuk_detik / 60);
                        const sudah_masuk_jam = Math.floor(sudah_masuk_menit / 60);
                        
                        // Hitung sisa waktu sampai keluar
                        const sisa_detik = Math.floor((waktuKeluarObj - sekarang) / 1000);
                        
                        const infoElement = row.querySelector('[data-info]');
                        const timerElement = row.querySelector('[data-timer]');
                        
                        // Update info sudah berapa lama masuk
                        if (infoElement) {
                            if (sudah_masuk_jam > 0) {
                                infoElement.textContent = `🚗 Masuk ${sudah_masuk_jam} jam ${sudah_masuk_menit % 60} menit yang lalu`;
                            } else if (sudah_masuk_menit > 0) {
                                infoElement.textContent = `🚗 Masuk ${sudah_masuk_menit} menit yang lalu`;
                            } else {
                                infoElement.textContent = `🚗 Masuk ${sudah_masuk_detik} detik yang lalu`;
                            }
                        }
                        
                        // Update timer countdown
                        if (timerElement) {
                            if (sisa_detik > 0) {
                                // Masih ada waktu, tampilkan countdown
                                const jamVal = Math.floor(sisa_detik / 3600);
                                const menitVal = Math.floor((sisa_detik % 3600) / 60);
                                const detikVal = sisa_detik % 60;
                                
                                if (jamVal > 0) {
                                    timerElement.innerHTML = `⏱ Keluar dalam ${jamVal} jam ${menitVal} menit ${detikVal} detik`;
                                } else if (menitVal > 0) {
                                    timerElement.innerHTML = `⏱ Keluar dalam ${menitVal} menit ${detikVal} detik`;
                                } else {
                                    timerElement.innerHTML = `⏱ Keluar dalam ${detikVal} detik`;
                                }
                                
                                // Ubah warna jika sisa waktu kurang dari 5 menit
                                if (sisa_detik < 300) {
                                    timerElement.style.color = '#ef4444';
                                    timerElement.style.fontWeight = 'bold';
                                } else if (sisa_detik < 600) {
                                    timerElement.style.color = '#f59e0b';
                                } else {
                                    timerElement.style.color = '#12b76a';
                                }
                            } else if (sisa_detik <= 0) {
                                // Waktu sudah habis atau lewat, otomatis update status ke KELUAR
                                timerElement.innerHTML = '⏰ WAKTU HABIS! Memproses keluar otomatis...';
                                timerElement.style.color = '#ef4444';
                                timerElement.style.fontWeight = 'bold';
                                
                                // Panggil fungsi untuk update status ke keluar
                                if (!isProcessing) {
                                    autoCheckout(row);
                                }
                            }
                        }
                    }
                } catch (e) {
                    console.error('Error parsing waktu:', e);
                }
            }
        });
    }
    
    // Fungsi untuk otomatis checkout ketika waktu habis
    function autoCheckout(row) {
        isProcessing = true;
        const idParkir = row.getAttribute('data-id');
        
        console.log('🔄 Auto checkout untuk ID:', idParkir);
        
        const formData = new FormData();
        formData.append('id_parkir', idParkir);
        formData.append('auto_checkout', 'true');
        
        fetch('check_keluar.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.status === 'keluar') {
                console.log('✅ Auto checkout berhasil untuk ID:', idParkir);
                
                // Update tampilan row
                row.setAttribute('data-status', 'keluar');
                const statusCell = row.querySelector('td:nth-child(7)');
                if (statusCell) {
                    statusCell.innerHTML = '<span class="badge badge-success"><span class="badge-dot"></span>✓ Keluar</span>';
                }
                
                // Update timer cell dengan waktu keluar
                const timerCell = row.querySelector('.timer-cell');
                if (timerCell && data.waktu_keluar) {
                    timerCell.innerHTML = data.waktu_keluar;
                }
                
                // Reload halaman setelah 2 detik untuk menampilkan data terbaru
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                console.log('⚠️ Auto checkout gagal atau status masih:', data.status);
            }
            isProcessing = false;
        })
        .catch(error => {
            console.error('❌ Error auto checkout:', error);
            isProcessing = false;
        });
    }
    
    // Update timer setiap detik
    let timerInterval = setInterval(updateTimers, 1000);
    
    // Initial update
    updateTimers();

    /* ── Sidebar toggle ── */
    const sidebar   = document.getElementById('sidebar');
    const burgerBtn = document.getElementById('burgerBtn');
    const sbCloseBtn = document.getElementById('sbCloseBtn');
    const overlay   = document.getElementById('overlay');

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

    /* Close on ESC */
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeSidebar();
    });

    /* Close on resize to desktop */
    window.addEventListener('resize', () => {
        if (window.innerWidth > 900) closeSidebar();
    });

    /* ── Pagination Functions (sama persis dengan log_aktivitas.php) ── */
    function jumpToPage() {
        const input = document.getElementById('jumpToPage');
        let targetPage = parseInt(input.value);
        const maxPage = <?php echo $total_pages; ?>;
        
        if (isNaN(targetPage)) targetPage = 1;
        if (targetPage < 1) targetPage = 1;
        if (targetPage > maxPage) targetPage = maxPage;
        
        window.location.href = `?page=${targetPage}`;
    }
    
    // Modal functions untuk "..." yang bisa diklik
    const modal = document.getElementById('pageModal');
    const pageNumberInput = document.getElementById('pageNumberInput');
    
    function openPageModal() {
        if (modal) {
            pageNumberInput.value = '';
            modal.classList.add('show');
            pageNumberInput.focus();
        }
    }
    
    function closeModal() {
        if (modal) {
            modal.classList.remove('show');
        }
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
    
    // Enter key pada modal
    if (pageNumberInput) {
        pageNumberInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                goToPage();
            }
        });
    }
    
    // Enter key pada input jump
    const jumpInput = document.getElementById('jumpToPage');
    if (jumpInput) {
        jumpInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                jumpToPage();
            }
        });
    }
    
    // Tutup modal dengan ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('show')) {
            closeModal();
        }
    });
    
    // Tutup modal dengan klik di luar
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }
</script>

</body>
</html>