<?php
session_start();
include './../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'owner') {
    header("Location: ../../auth/login.php");
    exit();
}

// Konfigurasi pagination
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : '';
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : '';

// Query total data
$count_query = "SELECT COUNT(*) as total FROM tb_transaksi t
                LEFT JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
                LEFT JOIN tb_area_parkir a ON t.id_area = a.id_area
                WHERE t.status = 'keluar'";

if ($tanggal_mulai && $tanggal_akhir) {
    $count_query .= " AND DATE(t.waktu_keluar) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'";
}

$count_result = mysqli_query($koneksi, $count_query);
$total_data = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_data / $limit);

// Query data transaksi
$query = "SELECT t.*, k.plat_nomor, k.jenis_kendaraan, a.nama_area 
          FROM tb_transaksi t
          LEFT JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
          LEFT JOIN tb_area_parkir a ON t.id_area = a.id_area
          WHERE t.status = 'keluar'";

if ($tanggal_mulai && $tanggal_akhir) {
    $query .= " AND DATE(t.waktu_keluar) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'";
}

$query .= " ORDER BY t.waktu_keluar DESC LIMIT $offset, $limit";

$result = mysqli_query($koneksi, $query);
$transaksis = [];

while ($row = mysqli_fetch_assoc($result)) {
    $transaksis[] = $row;
}

// Total biaya
$total_biaya_query = "SELECT SUM(biaya_total) as total FROM tb_transaksi t WHERE t.status = 'keluar'";
if ($tanggal_mulai && $tanggal_akhir) {
    $total_biaya_query .= " AND DATE(t.waktu_keluar) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'";
}
$total_biaya_result = mysqli_query($koneksi, $total_biaya_query);
$total_biaya = mysqli_fetch_assoc($total_biaya_result)['total'] ?? 0;

// Kendaraan unik
$unique_kendaraan_query = "SELECT COUNT(DISTINCT t.id_kendaraan) as total FROM tb_transaksi t WHERE t.status = 'keluar'";
if ($tanggal_mulai && $tanggal_akhir) {
    $unique_kendaraan_query .= " AND DATE(t.waktu_keluar) BETWEEN '$tanggal_mulai' AND '$tanggal_akhir'";
}
$unique_kendaraan_result = mysqli_query($koneksi, $unique_kendaraan_query);
$total_unique_kendaraan = mysqli_fetch_assoc($unique_kendaraan_result)['total'] ?? 0;

$nama    = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Owner');
$inisial = strtoupper(substr($_SESSION['nama_lengkap'] ?? 'O', 0, 1));

function safeHtml($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Helper: hitung durasi dari waktu_masuk & waktu_keluar
function hitungDurasi($waktu_masuk, $waktu_keluar) {
    $masuk  = strtotime($waktu_masuk);
    $keluar = strtotime($waktu_keluar);
    if (!$masuk || !$keluar || $keluar <= $masuk) return '< 1 menit';

    $selisih = $keluar - $masuk; // detik
    $jam     = (int)($selisih / 3600);
    $menit   = (int)(($selisih % 3600) / 60);

    if ($jam > 0 && $menit > 0) return "{$jam} jam {$menit} menit";
    if ($jam > 0)                return "{$jam} jam";
    if ($menit > 0)              return "{$menit} menit";
    return '< 1 menit';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Transaksi — ParkOwner</title>
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
            --purple:      #8b5cf6;
            --purple-light:#a78bfa;
            --purple-bg:   #f0ebff;
            --purple-soft: rgba(139,92,246,.10);
            --green:       #12b76a;
            --green-bg:    #e6f9f1;
            --orange:      #f59e0b;
            --orange-bg:   #fff8e6;
            --red:         #ef4444;
            --red-bg:      #fef2f2;
            --blue:        #4361ee;
            --blue-bg:     #edf0ff;
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
           SIDEBAR
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
            background: linear-gradient(135deg, var(--purple), var(--purple-light));
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(139,92,246,.35);
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
            font-size: 9.5px; font-weight: 800; letter-spacing: 1.2px;
            text-transform: uppercase; color: var(--dim);
            padding: 0 10px; margin: 16px 0 5px;
        }

        .sb-section-label:first-child { margin-top: 4px; }

        .sb-link {
            display: flex; align-items: center; gap: 11px;
            padding: 10px 10px; border-radius: 11px;
            color: var(--muted); text-decoration: none;
            font-size: 13.5px; font-weight: 500;
            margin-bottom: 2px; transition: all .18s; position: relative;
        }

        .sb-ico {
            width: 34px; height: 34px; border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; flex-shrink: 0;
            transition: background .18s, transform .2s;
        }

        .sb-link:hover { color: var(--text2); background: var(--bg); }
        .sb-link:hover .sb-ico { background: var(--bg2); transform: scale(1.05); }
        .sb-link.active { color: var(--purple); background: var(--purple-bg); font-weight: 600; }
        .sb-link.active .sb-ico { background: var(--purple-soft); }
        .sb-link.active::before {
            content: ''; position: absolute; right: 0; top: 50%;
            transform: translateY(-50%); width: 3px; height: 20px;
            background: var(--purple); border-radius: 99px 0 0 99px;
        }

        .sb-footer { padding: 12px; border-top: 1px solid var(--border); flex-shrink: 0; }

        .sb-user {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 10px; border-radius: 12px;
            background: var(--bg); border: 1px solid var(--border); transition: background .18s;
        }

        .sb-user:hover { background: var(--bg2); }

        .sb-avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: linear-gradient(135deg, var(--purple), var(--purple-light));
            color: white; font-size: 13px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; box-shadow: 0 2px 8px rgba(139,92,246,.3);
        }

        .sb-uname { font-size: 12.5px; font-weight: 700; color: var(--text); line-height: 1.2; }
        .sb-urole { font-size: 10.5px; color: var(--muted); font-weight: 500; }

        .sb-logout {
            display: flex; align-items: center; gap: 9px;
            padding: 9px 10px; margin-top: 6px; border-radius: 11px;
            color: var(--red); text-decoration: none; font-size: 13px;
            font-weight: 600; transition: background .18s;
        }

        .sb-logout:hover { background: var(--red-bg); }

        /* ═══════════════════════════════════
           MAIN
        ═══════════════════════════════════ */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1; display: flex; flex-direction: column;
            min-height: 100vh; min-width: 0;
        }

        /* ═══════════════════════════════════
           TOPBAR
        ═══════════════════════════════════ */
        .topbar {
            background: var(--white); border-bottom: 1px solid var(--border);
            height: var(--topbar-h); padding: 0 28px;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 200; box-shadow: var(--shadow-sm);
        }

        .topbar-left { display:flex; align-items:center; gap:14px; }
        .topbar-right { display:flex; align-items:center; gap:10px; }

        .burger {
            display: none;
            width: 38px; height: 38px;
            background: var(--bg); border: 1px solid var(--border);
            border-radius: 10px; cursor: pointer;
            align-items: center; justify-content: center;
            flex-direction: column; gap: 5px;
            transition: background .18s, border-color .18s; flex-shrink: 0;
        }

        .burger:hover { background: var(--bg2); border-color: var(--dim); }

        .burger span {
            display: block; width: 18px; height: 2px;
            background: var(--text2); border-radius: 2px;
            transition: all .3s cubic-bezier(.4,0,.2,1);
        }

        .burger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
        .burger.open span:nth-child(2) { opacity:0; transform: scaleX(0); }
        .burger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

        .topbar-breadcrumb { display: flex; align-items: center; gap: 7px; }
        .breadcrumb-home { font-size: 13px; color: var(--muted); font-weight: 500; }
        .breadcrumb-sep  { color: var(--dim); font-size: 12px; }
        .topbar-page     { font-size: 14.5px; font-weight: 700; color: var(--text); }

        .tb-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, var(--purple), var(--purple-light));
            color: white; font-size: 13px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 8px rgba(139,92,246,.28); cursor: pointer;
        }

        /* ═══════════════════════════════════
           CONTENT
        ═══════════════════════════════════ */
        .content { padding: 28px; flex: 1; }

        .welcome { margin-bottom: 24px; animation: fadeUp .35s ease both; }

        .welcome-inner {
            display: flex; align-items: center;
            justify-content: space-between; flex-wrap: wrap; gap: 12px;
        }

        .welcome h1 { font-size: 22px; font-weight: 800; letter-spacing: -.5px; color: var(--text); }
        .welcome p  { font-size: 13.5px; color: var(--muted); margin-top: 3px; }

        .welcome-date {
            font-size: 12.5px; color: var(--muted); font-weight: 500;
            background: var(--white); border: 1px solid var(--border);
            padding: 7px 14px; border-radius: 99px;
            display: flex; align-items: center; gap: 7px;
        }

        /* ═══════════════════════════════════
           FILTER CARD
        ═══════════════════════════════════ */
        .filter-card {
            background: var(--white); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 20px;
            margin-bottom: 24px; animation: fadeUp .4s ease .05s both;
        }

        .filter-title {
            font-size: 13px; font-weight: 600; color: var(--text);
            margin-bottom: 12px; display: flex; align-items: center; gap: 8px;
        }

        .filter-form {
            display: flex; flex-wrap: wrap;
            align-items: flex-end; gap: 12px;
        }

        .filter-group { flex: 1; min-width: 140px; }

        .filter-group label {
            display: block; font-size: 11px; font-weight: 600;
            color: var(--muted); margin-bottom: 5px;
            text-transform: uppercase; letter-spacing: 0.5px;
        }

        .filter-group input {
            width: 100%; padding: 10px 14px;
            border: 1px solid var(--border); border-radius: var(--radius-sm);
            font-family: inherit; font-size: 13px; transition: all .2s;
            background: var(--white); color: var(--text);
        }

        .filter-group input:focus {
            outline: none; border-color: var(--purple);
            box-shadow: 0 0 0 3px rgba(139,92,246,.1);
        }

        .btn-filter, .btn-print, .btn-reset {
            padding: 10px 18px; border: none; border-radius: var(--radius-sm);
            font-weight: 600; font-size: 13px; cursor: pointer;
            transition: all .2s; display: inline-flex; align-items: center; gap: 7px;
            font-family: inherit; white-space: nowrap;
        }

        .btn-filter { background: var(--purple); color: white; }
        .btn-filter:hover { background: var(--purple-light); transform: translateY(-1px); }

        .btn-print { background: var(--green); color: white; }
        .btn-print:hover { background: #0e9f5e; transform: translateY(-1px); }

        .btn-reset { background: var(--bg2); color: var(--text2); text-decoration: none; }
        .btn-reset:hover { background: var(--dim); }

        /* ═══════════════════════════════════
           STATS SUMMARY
        ═══════════════════════════════════ */
        .stats-summary {
            display: grid; grid-template-columns: repeat(2, 1fr);
            gap: 14px; margin-bottom: 24px;
            animation: fadeUp .4s ease .08s both;
        }

        .summary-card {
            background: var(--white); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 20px;
            display: flex; align-items: center; justify-content: space-between;
            transition: box-shadow .22s; min-width: 0;
        }

        .summary-card:hover { box-shadow: var(--shadow-md); }

        .summary-info h4 {
            font-size: 11px; font-weight: 600; color: var(--muted);
            text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px;
        }

        .summary-value { font-size: 28px; font-weight: 800; color: var(--text); }

        .summary-icon {
            width: 48px; height: 48px; background: var(--purple-bg);
            border-radius: 14px; display: flex; align-items: center;
            justify-content: center; font-size: 24px; flex-shrink: 0;
        }

        /* ═══════════════════════════════════
           FILTER INFO
        ═══════════════════════════════════ */
        .filter-info {
            background: var(--white); border: 1px solid var(--border);
            border-radius: var(--radius-sm); padding: 12px 16px;
            margin-bottom: 16px; display: flex; align-items: center;
            justify-content: space-between; flex-wrap: wrap; gap: 12px;
        }

        .filter-info-left { font-size: 12.5px; color: var(--muted); }
        .filter-info-left strong { color: var(--text); font-weight: 700; }

        /* ═══════════════════════════════════
           TABLE
        ═══════════════════════════════════ */
        .table-card {
            background: var(--white); border: 1px solid var(--border);
            border-radius: var(--radius); overflow-x: auto;
            box-shadow: var(--shadow-sm); animation: fadeUp .45s ease .12s both;
            -webkit-overflow-scrolling: touch;
        }

        table { width: 100%; border-collapse: collapse; min-width: 700px; }

        thead tr { background: var(--bg); }

        thead th {
            padding: 12px 16px; font-size: 11px; font-weight: 800;
            letter-spacing: .9px; text-transform: uppercase;
            color: var(--muted); text-align: left; border-bottom: 1px solid var(--border);
        }

        tbody tr { border-bottom: 1px solid var(--border); transition: background .15s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #f7f8fd; }
        tbody td { padding: 12px 16px; font-size: 13px; }

        .badge-success {
            background: var(--green-bg); color: var(--green);
            padding: 4px 10px; border-radius: 99px;
            font-size: 11px; font-weight: 600; display: inline-block;
        }

        .empty-row td {
            text-align: center; padding: 40px;
            color: var(--muted); font-size: 13.5px;
        }

        /* ═══════════════════════════════════
           TOTAL SECTION
        ═══════════════════════════════════ */
        .total-section {
            background: linear-gradient(135deg, var(--purple), var(--purple-light));
            border-radius: var(--radius); padding: 20px 24px; margin-top: 20px;
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 12px; animation: fadeUp .45s ease .15s both;
        }

        .total-label  { font-size: 14px; font-weight: 600; color: rgba(255,255,255,.9); }
        .total-amount { font-size: 26px; font-weight: 800; color: white; }

        /* ═══════════════════════════════════
           PAGINATION
        ═══════════════════════════════════ */
        .pagination {
            display: flex; justify-content: center; align-items: center;
            gap: 8px; margin-top: 24px; flex-wrap: wrap;
        }

        .page-link {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 36px; height: 36px; padding: 0 10px;
            background: var(--white); border: 1px solid var(--border);
            border-radius: 9px; color: var(--text2); font-size: 13px;
            font-weight: 500; text-decoration: none; transition: all .18s; cursor: pointer;
        }

        .page-link:hover { background: var(--bg); border-color: var(--dim); color: var(--purple); }
        .page-link.active { background: var(--purple); border-color: var(--purple); color: white; }
        .page-link.disabled { opacity: 0.5; pointer-events: none; }

        .page-input-group {
            display: inline-flex; align-items: center; gap: 6px;
            background: var(--white); border: 1px solid var(--border);
            border-radius: 9px; padding: 4px 8px; height: 36px;
        }

        .page-input-group span { font-size: 12px; color: var(--muted); }

        .page-input {
            width: 55px; padding: 4px 6px; border: 1px solid var(--border);
            border-radius: 6px; font-size: 12px; text-align: center;
            font-family: inherit; background: var(--white); color: var(--text); outline: none;
        }

        .page-input:focus { border-color: var(--purple); }

        .page-go-btn {
            background: var(--purple); border: none; padding: 4px 10px;
            border-radius: 6px; color: white; font-size: 11px; font-weight: 600;
            cursor: pointer; transition: background .18s; font-family: inherit;
        }

        .page-go-btn:hover { background: var(--purple-light); }

        .page-info { font-size: 12px; color: var(--muted); margin-left: 12px; }

        .page-dots {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 36px; height: 36px; background: transparent;
            color: var(--purple); font-size: 13px; font-weight: 600;
            cursor: pointer; border-radius: 9px; transition: all .18s; padding: 0 4px;
        }

        .page-dots:hover { background: var(--purple-bg); }

        /* ═══════════════════════════════════
           MODAL
        ═══════════════════════════════════ */
        .modal {
            display: none; position: fixed; top: 0; left: 0;
            width: 100%; height: 100%; background: rgba(0,0,0,0.5);
            z-index: 1000; justify-content: center; align-items: center;
        }

        .modal.show { display: flex; }

        .modal-content {
            background: var(--white); border-radius: var(--radius);
            padding: 24px; max-width: 350px; width: 90%;
            text-align: center; box-shadow: var(--shadow-lg);
        }

        .modal-content h3 { margin-bottom: 16px; color: var(--text); }

        .modal-input {
            width: 100%; padding: 12px; border: 1px solid var(--border);
            border-radius: var(--radius-sm); font-size: 14px;
            margin-bottom: 16px; font-family: inherit;
        }

        .modal-buttons { display: flex; gap: 10px; justify-content: center; }

        .modal-btn {
            padding: 8px 20px; border-radius: 8px; font-size: 13px;
            font-weight: 600; cursor: pointer; border: none; font-family: inherit;
        }

        .modal-btn.confirm { background: var(--purple); color: white; }
        .modal-btn.cancel  { background: var(--bg); color: var(--text2); border: 1px solid var(--border); }

        /* ═══════════════════════════════════
           PAGE SETTINGS
        ═══════════════════════════════════ */
        @page {
            size: A4;
            margin: 0.5cm;
            padding: 0;
        }

        /* ═══════════════════════════════════
           PRINT
        ═══════════════════════════════════ */
        @media print {
            * { margin: 0 !important; padding: 0 !important; }
            
            body {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .sidebar, .topbar, .filter-card, .stats-summary,
            .no-print, .pagination, .filter-info,
            .welcome, .main::before, .main::after { display: none !important; }
            
            .main {
                margin-left: 0 !important;
                margin-top: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            
            .content {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
            }
            
            .table-card {
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                page-break-inside: avoid;
            }
            
            table {
                width: 100% !important;
                border-collapse: collapse;
            }
            
            thead { display: table-header-group !important; }
            tbody { display: table-row-group !important; }
            tr { page-break-inside: avoid; }
            
            th, td {
                border: 1px solid #ddd !important;
                padding: 8px !important;
                text-align: left;
            }
            
            th {
                background: #f5f5f5 !important;
                font-weight: bold !important;
            }
            
            .total-section {
                margin-top: 20px !important;
                padding: 16px !important;
                border: 1px solid #ddd !important;
                background: #f9f9f9 !important;
                text-align: center;
                page-break-inside: avoid;
            }
            
            .total-section span {
                display: block !important;
                margin: 4px 0 !important;
                color: black !important;
            }
            
            .total-label {
                font-weight: bold !important;
                font-size: 14px !important;
            }
            
            .total-amount {
                font-size: 18px !important;
                font-weight: bold !important;
                color: #12b76a !important;
            }
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
            display: none; position: fixed; inset: 0;
            background: rgba(10,12,28,.25); backdrop-filter: blur(3px);
            z-index: 290; opacity: 0; transition: opacity .3s;
        }

        .overlay.active { display: block; opacity: 1; }

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

            .welcome-date { display: none; }

            .filter-form { flex-direction: column; }
            .filter-group { width: 100%; min-width: unset; }

            .btn-filter, .btn-print, .btn-reset {
                width: 100%; justify-content: center;
            }

            .stats-summary { grid-template-columns: 1fr; }
            .summary-value { font-size: 24px; }

            .total-section { flex-direction: column; text-align: center; }
            .total-amount  { font-size: 22px; }

            .pagination { gap: 6px; }
            .page-link, .page-dots { min-width: 32px; height: 32px; font-size: 12px; }
            .page-input-group { height: 32px; }
        }

        /* ═══════════════════════════════════
           RESPONSIVE — MOBILE KECIL
        ═══════════════════════════════════ */
        @media (max-width: 480px) {
            .content { padding: 12px; }

            .welcome h1 { font-size: 19px; }

            .filter-card { padding: 16px; }

            .filter-info { flex-direction: column; align-items: flex-start; }

            .summary-card { padding: 14px 16px; }
            .summary-value { font-size: 22px; }
            .summary-icon { width: 40px; height: 40px; font-size: 20px; }

            .page-input-group { flex-wrap: wrap; height: auto; padding: 6px 8px; }
            .page-info { width: 100%; text-align: center; margin: 6px 0 0; }
        }

        /* ═══════════════════════════════════
           RESPONSIVE — MOBILE SANGAT KECIL
        ═══════════════════════════════════ */
        @media (max-width: 380px) {
            .topbar-page { font-size: 13px; }
            .welcome h1  { font-size: 18px; }
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

<!-- ═══════════════ SIDEBAR ═══════════════ -->
<aside class="sidebar" id="sidebar">

    <div class="sb-logo">
        <div class="sb-logo-icon">🅿️</div>
        <div>
            <div class="sb-logo-name">ParkOwner</div>
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
        <a href="rekap.php" class="sb-link active">
            <div class="sb-ico">📊</div>
            Rekap Transaksi
        </a>
    </nav>

    <div class="sb-footer">
        <div class="sb-user">
            <div class="sb-avatar"><?php echo $inisial; ?></div>
            <div>
                <div class="sb-uname"><?php echo $nama; ?></div>
                <div class="sb-urole">Owner</div>
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
                <span class="breadcrumb-home">ParkOwner</span>
                <span class="breadcrumb-sep">›</span>
                <span class="topbar-page">Rekap Transaksi</span>
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
                    <h1>Rekap Transaksi 📊</h1>
                    <p>Lihat dan filter laporan transaksi parkir.</p>
                </div>
                <div class="welcome-date">
                    <span>📅</span>
                    <span id="currentDate">–</span>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-card">
            <div class="filter-title">
                <span>🔍</span> Filter Laporan
            </div>
            <form method="GET" class="filter-form" id="filterForm">
                <div class="filter-group">
                    <label>Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" value="<?php echo $tanggal_mulai; ?>">
                </div>
                <div class="filter-group">
                    <label>Tanggal Akhir</label>
                    <input type="date" name="tanggal_akhir" value="<?php echo $tanggal_akhir; ?>">
                </div>
                <button type="submit" class="btn-filter">📅 Tampilkan</button>
                <button type="button" class="btn-print" onclick="printLaporan()">🖨️ Print</button>
                <a href="rekap.php" class="btn-reset">⟳ Reset</a>
            </form>
        </div>

        <!-- Stats Summary -->
        <div class="stats-summary">
            <div class="summary-card">
                <div class="summary-info">
                    <h4>Total Transaksi</h4>
                    <div class="summary-value"><?php echo number_format($total_data, 0, ',', '.'); ?></div>
                </div>
                <div class="summary-icon">📝</div>
            </div>
            <div class="summary-card">
                <div class="summary-info">
                    <h4>Total Kendaraan</h4>
                    <div class="summary-value"><?php echo number_format($total_unique_kendaraan, 0, ',', '.'); ?></div>
                </div>
                <div class="summary-icon">🚗</div>
            </div>
        </div>

        <!-- Filter Info -->
        <div class="filter-info">
            <div class="filter-info-left">
                📊 Menampilkan <strong><?php echo count($transaksis); ?></strong> dari <strong><?php echo $total_data; ?></strong> transaksi
                <?php if ($tanggal_mulai && $tanggal_akhir): ?>
                    <span style="margin-left: 12px;">📅 Periode: <strong><?php echo date('d/m/Y', strtotime($tanggal_mulai)); ?></strong> – <strong><?php echo date('d/m/Y', strtotime($tanggal_akhir)); ?></strong></span>
                <?php endif; ?>
            </div>
            <div class="filter-info-left">
                📄 <strong><?php echo $limit; ?></strong> data per halaman
            </div>
        </div>

        <!-- Table -->
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Plat Nomor</th>
                        <th>Jenis</th>
                        <th>Area</th>
                        <th>Waktu Masuk</th>
                        <th>Waktu Keluar</th>
                        <th>Durasi</th>
                        <th>Biaya</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($transaksis) > 0): ?>
                        <?php foreach ($transaksis as $t): ?>
                        <tr>
                            <td><?php echo safeHtml($t['id_parkir']); ?></td>
                            <td><strong><?php echo safeHtml($t['plat_nomor']); ?></strong></td>
                            <td><?php echo safeHtml($t['jenis_kendaraan']); ?></td>
                            <td><?php echo safeHtml($t['nama_area']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($t['waktu_masuk'])); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($t['waktu_keluar'])); ?></td>
                            <td><?php echo hitungDurasi($t['waktu_masuk'], $t['waktu_keluar']); ?></td>
                            <td>Rp <?php echo number_format($t['biaya_total'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="empty-row">
                            <td colspan="8">
                                <?php if ($tanggal_mulai && $tanggal_akhir): ?>
                                    Tidak ada transaksi pada periode <?php echo date('d/m/Y', strtotime($tanggal_mulai)); ?> – <?php echo date('d/m/Y', strtotime($tanggal_akhir)); ?>.
                                <?php else: ?>
                                    Belum ada transaksi selesai.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Total Section -->
        <?php if ($total_biaya > 0): ?>
        <div class="total-section">
            <span class="total-label">💰 Total Pendapatan<?php echo ($tanggal_mulai && $tanggal_akhir) ? ' Periode Ini' : ''; ?></span>
            <span class="total-amount">Rp <?php echo number_format($total_biaya, 0, ',', '.'); ?></span>
            <?php if ($tanggal_mulai && $tanggal_akhir): ?>
            <span style="font-size: 12px; color: #666; margin-top: 8px;">
                📅 Periode: <?php echo date('d/m/Y', strtotime($tanggal_mulai)); ?> - <?php echo date('d/m/Y', strtotime($tanggal_akhir)); ?>
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&tanggal_mulai=<?php echo urlencode($tanggal_mulai); ?>&tanggal_akhir=<?php echo urlencode($tanggal_akhir); ?>" class="page-link">‹ Sebelumnya</a>
            <?php else: ?>
                <span class="page-link disabled">‹ Sebelumnya</span>
            <?php endif; ?>

            <?php
            $startPage = max(1, $page - 2);
            $endPage   = min($total_pages, $page + 2);

            if ($startPage > 1) {
                echo '<a href="?page=1&tanggal_mulai=' . urlencode($tanggal_mulai) . '&tanggal_akhir=' . urlencode($tanggal_akhir) . '" class="page-link">1</a>';
                if ($startPage > 2) echo '<span class="page-dots" onclick="openPageModal()">...</span>';
            }

            for ($i = $startPage; $i <= $endPage; $i++): ?>
                <a href="?page=<?php echo $i; ?>&tanggal_mulai=<?php echo urlencode($tanggal_mulai); ?>&tanggal_akhir=<?php echo urlencode($tanggal_akhir); ?>"
                   class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor;

            if ($endPage < $total_pages) {
                if ($endPage < $total_pages - 1) echo '<span class="page-dots" onclick="openPageModal()">...</span>';
                echo '<a href="?page=' . $total_pages . '&tanggal_mulai=' . urlencode($tanggal_mulai) . '&tanggal_akhir=' . urlencode($tanggal_akhir) . '" class="page-link">' . $total_pages . '</a>';
            }
            ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&tanggal_mulai=<?php echo urlencode($tanggal_mulai); ?>&tanggal_akhir=<?php echo urlencode($tanggal_akhir); ?>" class="page-link">Selanjutnya ›</a>
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
    /* ── Date ── */
    const dateEl = document.getElementById('currentDate');
    if (dateEl) {
        const d = new Date();
        dateEl.textContent = d.toLocaleDateString('id-ID', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });
    }

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

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSidebar(); });
    window.addEventListener('resize', () => { if (window.innerWidth > 768) closeSidebar(); });

    /* ── Pagination ── */
    const searchParams = new URLSearchParams(window.location.search);
    const tanggalMulai = searchParams.get('tanggal_mulai') || '';
    const tanggalAkhir = searchParams.get('tanggal_akhir') || '';

    function jumpToPage() {
        const input = document.getElementById('jumpToPage');
        let targetPage = parseInt(input.value);
        const maxPage = <?php echo $total_pages; ?>;
        if (isNaN(targetPage)) targetPage = 1;
        if (targetPage < 1) targetPage = 1;
        if (targetPage > maxPage) targetPage = maxPage;
        let url = `?page=${targetPage}`;
        if (tanggalMulai) url += `&tanggal_mulai=${tanggalMulai}`;
        if (tanggalAkhir) url += `&tanggal_akhir=${tanggalAkhir}`;
        window.location.href = url;
    }

    const modal           = document.getElementById('pageModal');
    const pageNumberInput = document.getElementById('pageNumberInput');

    function openPageModal() {
        if (modal) { pageNumberInput.value = ''; modal.classList.add('show'); pageNumberInput.focus(); }
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
        let url = `?page=${targetPage}`;
        if (tanggalMulai) url += `&tanggal_mulai=${tanggalMulai}`;
        if (tanggalAkhir) url += `&tanggal_akhir=${tanggalAkhir}`;
        window.location.href = url;
    }

    if (pageNumberInput) {
        pageNumberInput.addEventListener('keypress', e => { if (e.key === 'Enter') goToPage(); });
    }

    const jumpInput = document.getElementById('jumpToPage');
    if (jumpInput) {
        jumpInput.addEventListener('keypress', e => { if (e.key === 'Enter') jumpToPage(); });
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && modal && modal.classList.contains('show')) closeModal();
    });

    if (modal) {
        modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
    }

    /* ── Print Function ── */
    function printLaporan() {
        window.print();
    }
</script>

</body>
</html>