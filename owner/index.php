<?php
session_start();
include './../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'owner') {
    header("Location: ../../auth/login.php");
    exit();
}

// Konfigurasi pagination untuk transaksi terbaru (sama dengan log_aktivitas.php)
$limit = 5; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$nama    = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Owner');
$inisial = strtoupper(substr($_SESSION['nama_lengkap'] ?? 'O', 0, 1));

// Ambil statistik untuk dashboard owner
$total_transaksi = 0;
$total_pendapatan = 0;
$total_kendaraan = 0;
$total_user = 0;
$total_area = 0;

$query_transaksi = "SELECT COUNT(*) as total, SUM(biaya_total) as pendapatan FROM tb_transaksi WHERE status = 'keluar'";
$result_transaksi = mysqli_query($koneksi, $query_transaksi);
if ($row = mysqli_fetch_assoc($result_transaksi)) {
    $total_transaksi = $row['total'] ?? 0;
    $total_pendapatan = $row['pendapatan'] ?? 0;
}

$query_kendaraan = "SELECT COUNT(*) as total FROM tb_kendaraan";
$result_kendaraan = mysqli_query($koneksi, $query_kendaraan);
$total_kendaraan = mysqli_fetch_assoc($result_kendaraan)['total'] ?? 0;

$query_user = "SELECT COUNT(*) as total FROM tb_user WHERE role != 'owner'";
$result_user = mysqli_query($koneksi, $query_user);
$total_user = mysqli_fetch_assoc($result_user)['total'] ?? 0;

$query_area = "SELECT COUNT(*) as total FROM tb_area_parkir";
$result_area = mysqli_query($koneksi, $query_area);
$total_area = mysqli_fetch_assoc($result_area)['total'] ?? 0;

// Hitung total data transaksi untuk pagination
$count_query = "SELECT COUNT(*) as total FROM tb_transaksi t
                LEFT JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
                LEFT JOIN tb_area_parkir a ON t.id_area = a.id_area
                WHERE t.status = 'keluar'";
$count_result = mysqli_query($koneksi, $count_query);
$total_data = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_data / $limit);

// Ambil data transaksi terbaru dengan pagination
$query_recent = "SELECT t.*, k.plat_nomor, a.nama_area 
                 FROM tb_transaksi t
                 LEFT JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
                 LEFT JOIN tb_area_parkir a ON t.id_area = a.id_area
                 WHERE t.status = 'keluar'
                 ORDER BY t.waktu_keluar DESC 
                 LIMIT $offset, $limit";
$result_recent = mysqli_query($koneksi, $query_recent);
$recent_transactions = [];
while ($row = mysqli_fetch_assoc($result_recent)) {
    $recent_transactions[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Owner — ParkOwner</title>
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
            --orange:      #f59e0b;
            --orange-bg:   #fff8e6;
            --green:       #12b76a;
            --green-bg:    #e6f9f1;
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

        .sb-link.active { color: var(--purple); background: var(--purple-bg); font-weight: 600; }
        .sb-link.active .sb-ico { background: var(--purple-soft); }

        .sb-link.active::before {
            content: '';
            position: absolute;
            right: 0; top: 50%;
            transform: translateY(-50%);
            width: 3px; height: 20px;
            background: var(--purple);
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
            background: linear-gradient(135deg, var(--purple), var(--purple-light));
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

        .topbar-breadcrumb { display: flex; align-items: center; gap: 7px; }
        .breadcrumb-home { font-size: 13px; color: var(--muted); font-weight: 500; }
        .breadcrumb-sep { color: var(--dim); font-size: 12px; }
        .topbar-page { font-size: 14.5px; font-weight: 700; color: var(--text); }

        .tb-avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--purple), var(--purple-light));
            color: white;
            font-size: 13px;
            font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 8px rgba(139,92,246,.28);
            cursor: pointer;
        }

        /* ═══════════════════════════════════
           CONTENT
        ═══════════════════════════════════ */
        .content { padding: 28px; flex: 1; }

        /* Welcome */
        .welcome { margin-bottom: 24px; animation: fadeUp .35s ease both; }

        .welcome-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .welcome h1 { font-size: 22px; font-weight: 800; letter-spacing: -.5px; color: var(--text); }
        .welcome p { font-size: 13.5px; color: var(--muted); margin-top: 3px; }

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

        .filter-info-left { font-size: 12.5px; color: var(--muted); }
        .filter-info-left strong { color: var(--text); font-weight: 700; }

        /* ═══════════════════════════════════
           STATS GRID
        ═══════════════════════════════════ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            animation: fadeUp .4s ease both;
            transition: box-shadow .22s, transform .22s;
            cursor: default;
            min-width: 0;
        }

        .stat-card:hover { box-shadow: var(--shadow-md); transform: translateY(-3px); }
        .stat-card:nth-child(1){animation-delay:.05s}
        .stat-card:nth-child(2){animation-delay:.09s}
        .stat-card:nth-child(3){animation-delay:.13s}
        .stat-card:nth-child(4){animation-delay:.17s}

        .stat-ico {
            width: 48px; height: 48px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
            transition: transform .2s;
        }

        .stat-card:hover .stat-ico { transform: scale(1.08) rotate(-3deg); }

        .stat-body { min-width: 0; flex: 1; }

        .stat-num {
            font-size: 22px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -.5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .stat-lbl {
            font-size: 12px;
            color: var(--muted);
            margin-top: 4px;
            font-weight: 500;
        }

        .c-purple { background: var(--purple-bg); color: var(--purple); }
        .c-green  { background: var(--green-bg);  color: var(--green);  }
        .c-orange { background: var(--orange-bg); color: var(--orange); }
        .c-blue   { background: var(--blue-bg);   color: var(--blue);   }

        /* ═══════════════════════════════════
           MENU CARDS
        ═══════════════════════════════════ */
        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            animation: fadeUp .4s ease .1s both;
        }

        .section-title { font-size: 15.5px; font-weight: 800; letter-spacing: -.3px; }
        .section-sub { font-size: 12.5px; color: var(--muted); margin-top: 2px; }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 28px;
            animation: fadeUp .45s ease .12s both;
        }

        .menu-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px 20px;
            text-decoration: none;
            transition: all .25s ease;
            display: flex;
            align-items: center;
            gap: 16px;
            cursor: pointer;
            min-width: 0;
        }

        .menu-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: transparent;
        }

        .menu-icon {
            width: 52px; height: 52px;
            background: linear-gradient(135deg, var(--purple), var(--purple-light));
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
            transition: transform .25s;
        }

        .menu-card:hover .menu-icon { transform: scale(1.05) rotate(-5deg); }

        .menu-info { min-width: 0; flex: 1; }

        .menu-info h3 {
            font-size: 16px;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .menu-info p {
            font-size: 12.5px;
            color: var(--muted);
            line-height: 1.4;
        }

        .menu-arrow {
            font-size: 20px;
            color: var(--dim);
            flex-shrink: 0;
            transition: transform .25s, color .25s;
        }

        .menu-card:hover .menu-arrow { transform: translateX(4px); color: var(--purple); }

        /* ═══════════════════════════════════
           TABLE
        ═══════════════════════════════════ */
        .table-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow-x: auto;
            box-shadow: var(--shadow-sm);
            animation: fadeUp .45s ease .15s both;
            -webkit-overflow-scrolling: touch;
        }

        table { width: 100%; border-collapse: collapse; min-width: 580px; }

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

        tbody tr { border-bottom: 1px solid var(--border); transition: background .15s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #f7f8fd; }
        tbody td { padding: 12px 16px; font-size: 13px; }

        .badge-success {
            background: var(--green-bg);
            color: var(--green);
            padding: 4px 10px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
        }

        .empty-row td { text-align: center; padding: 40px; color: var(--muted); font-size: 13.5px; }

        .view-all { text-align: right; margin-top: 12px; font-size: 12px; }
        .view-all a { color: var(--purple); text-decoration: none; font-weight: 600; }
        .view-all a:hover { text-decoration: underline; }

        /* ═══════════════════════════════════
           PAGINATION
        ═══════════════════════════════════ */
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
            min-width: 36px; height: 36px;
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

        .page-link:hover { background: var(--bg); border-color: var(--dim); color: var(--purple); }
        .page-link.active { background: var(--purple); border-color: var(--purple); color: white; }
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

        .page-input:focus { border-color: var(--purple); }

        .page-go-btn {
            background: var(--purple);
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

        .page-go-btn:hover { background: var(--purple-light); }

        .page-info { font-size: 12px; color: var(--muted); margin-left: 12px; }

        .page-dots {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px; height: 36px;
            background: transparent;
            color: var(--purple);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border-radius: 9px;
            transition: all .18s;
            padding: 0 4px;
        }

        .page-dots:hover { background: var(--purple-bg); }

        /* ═══════════════════════════════════
           MODAL
        ═══════════════════════════════════ */
        .modal {
            display: none;
            position: fixed; top: 0; left: 0;
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

        .modal-btn.confirm { background: var(--purple); color: white; }
        .modal-btn.cancel  { background: var(--bg); color: var(--text2); border: 1px solid var(--border); }

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

        .overlay.active { display: block; opacity: 1; }

        /* ═══════════════════════════════════
           RESPONSIVE — TABLET
        ═══════════════════════════════════ */
        @media (max-width: 1100px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); box-shadow: none; }
            .sidebar.open { transform: translateX(0); box-shadow: var(--shadow-lg); }
            .sb-close-btn { display: flex; }

            .main { margin-left: 0 !important; }
            .burger { display: flex; }

            .topbar { padding: 0 16px; }
            .content { padding: 16px; }

            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }

            .stat-ico { width: 42px; height: 42px; font-size: 19px; border-radius: 12px; }
            .stat-num { font-size: 18px; }
            .stat-lbl { font-size: 11px; }

            .welcome-date { display: none; }

            .menu-grid { grid-template-columns: 1fr; gap: 10px; }

            .pagination { gap: 6px; }
            .page-link, .page-dots { min-width: 32px; height: 32px; font-size: 12px; }
            .page-input-group { height: 32px; }
        }

        /* ═══════════════════════════════════
           RESPONSIVE — MOBILE KECIL
        ═══════════════════════════════════ */
        @media (max-width: 480px) {
            .content { padding: 12px; }

            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }

            .stat-card { padding: 14px 12px; gap: 10px; }
            .stat-ico { width: 38px; height: 38px; font-size: 17px; border-radius: 10px; }
            .stat-num { font-size: 16px; }
            .stat-lbl { font-size: 10.5px; }

            .welcome h1 { font-size: 19px; }

            .menu-card { padding: 16px 14px; gap: 12px; }
            .menu-icon { width: 44px; height: 44px; font-size: 20px; border-radius: 13px; }
            .menu-info h3 { font-size: 14px; }
            .menu-info p { font-size: 12px; }

            .filter-info { flex-direction: column; align-items: flex-start; }

            .page-input-group { flex-wrap: wrap; height: auto; padding: 6px 8px; }
            .page-info { width: 100%; text-align: center; margin: 6px 0 0; }
        }

        /* ═══════════════════════════════════
           RESPONSIVE — MOBILE SANGAT KECIL
        ═══════════════════════════════════ */
        @media (max-width: 380px) {
            .stats-grid { grid-template-columns: 1fr; }
            .stat-num { font-size: 20px; }
            .topbar-page { font-size: 13px; }
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
        <a href="index.php" class="sb-link active">
            <div class="sb-ico">🏠</div>
            Dashboard
        </a>
        <a href="rekap.php" class="sb-link">
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
                <span class="topbar-page">Dashboard</span>
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
                    <h1>Halo, <?php echo $nama; ?> 👑</h1>
                    <p>Selamat datang — pantau performa bisnis parkir Anda.</p>
                </div>
                <div class="welcome-date">
                    <span>📅</span>
                    <span id="currentDate">–</span>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-ico c-purple">💰</div>
                <div class="stat-body">
                    <div class="stat-num">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></div>
                    <div class="stat-lbl">Total Pendapatan</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-ico c-green">📝</div>
                <div class="stat-body">
                    <div class="stat-num"><?php echo $total_transaksi; ?></div>
                    <div class="stat-lbl">Total Transaksi</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-ico c-orange">🚗</div>
                <div class="stat-body">
                    <div class="stat-num"><?php echo $total_kendaraan; ?></div>
                    <div class="stat-lbl">Total Kendaraan</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-ico c-blue">👥</div>
                <div class="stat-body">
                    <div class="stat-num"><?php echo $total_user; ?></div>
                    <div class="stat-lbl">Total User</div>
                </div>
            </div>
        </div>

        <!-- Menu Section -->
        <div class="section-head">
            <div>
                <div class="section-title">Menu Owner</div>
                <div class="section-sub">Pilih menu untuk melihat laporan</div>
            </div>
        </div>

        <div class="menu-grid">
            <a href="rekap.php" class="menu-card">
                <div class="menu-icon">📊</div>
                <div class="menu-info">
                    <h3>Rekap Transaksi</h3>
                    <p>Laporan transaksi sesuai periode yang dipilih</p>
                </div>
                <div class="menu-arrow">→</div>
            </a>

            <div class="menu-card" style="opacity: 0.5; cursor: not-allowed;">
                <div class="menu-icon" style="background: linear-gradient(135deg, var(--muted), var(--dim));">📈</div>
                <div class="menu-info">
                    <h3>Laporan Tahunan</h3>
                    <p>Fitur akan segera hadir</p>
                </div>
                <div class="menu-arrow">→</div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="section-head">
            <div>
                <div class="section-title">Transaksi Terbaru</div>
                <div class="section-sub">Transaksi terakhir yang telah selesai</div>
            </div>
        </div>

        <!-- Filter Info -->
        <div class="filter-info">
            <div class="filter-info-left">
                📊 Menampilkan <strong><?php echo count($recent_transactions); ?></strong> dari <strong><?php echo $total_data; ?></strong> transaksi
            </div>
            <div class="filter-info-left">
                📄 <strong><?php echo $limit; ?></strong> data per halaman
            </div>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Plat Nomor</th>
                        <th>Area</th>
                        <th>Waktu Keluar</th>
                        <th>Biaya</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recent_transactions) > 0): ?>
                        <?php foreach ($recent_transactions as $t): ?>
                        <tr>
                            <td><?php echo $t['id_parkir']; ?></td>
                            <td><strong><?php echo htmlspecialchars($t['plat_nomor'] ?? '-'); ?></strong></td>
                            <td><?php echo htmlspecialchars($t['nama_area'] ?? '-'); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($t['waktu_keluar'])); ?></td>
                            <td>Rp <?php echo number_format($t['biaya_total'] ?? 0, 0, ',', '.'); ?></td>
                            <td><span class="badge-success">✓ Selesai</span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="empty-row">
                            <td colspan="6">Belum ada transaksi selesai.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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

        <?php if (count($recent_transactions) > 0 && $total_pages > 1): ?>
        <div class="view-all">
            <a href="rekap.php">Lihat semua transaksi →</a>
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

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeSidebar();
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) closeSidebar();
    });

    /* ── Pagination ── */
    function jumpToPage() {
        const input = document.getElementById('jumpToPage');
        let targetPage = parseInt(input.value);
        const maxPage = <?php echo $total_pages; ?>;
        if (isNaN(targetPage)) targetPage = 1;
        if (targetPage < 1) targetPage = 1;
        if (targetPage > maxPage) targetPage = maxPage;
        window.location.href = `?page=${targetPage}`;
    }

    const modal = document.getElementById('pageModal');
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
        window.location.href = `?page=${targetPage}`;
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
</script>

</body>
</html>