<?php
session_start();
include './../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

// Konfigurasi Pagination untuk Area Parkir
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Hitung total area parkir
$totalAreaQuery = "SELECT COUNT(*) as total FROM tb_area_parkir";
$totalAreaResult = mysqli_query($koneksi, $totalAreaQuery);
$totalArea = mysqli_fetch_assoc($totalAreaResult)['total'];
$totalPages = ceil($totalArea / $limit);

// Ambil data area parkir dengan pagination
$area_query = "SELECT id_area, nama_area, kapasitas, terisi FROM tb_area_parkir ORDER BY nama_area LIMIT $limit OFFSET $offset";
$area_result = mysqli_query($koneksi, $area_query);

// Statistik lainnya (tanpa pagination)
$user_count      = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_user"))['total'];
$area_count      = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_area_parkir"))['total'];
$tarif_count     = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_tarif"))['total'];
$kendaraan_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_kendaraan"))['total'];
$total_terisi    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(terisi) as total FROM tb_area_parkir"))['total'] ?? 0;
$total_kapasitas = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(kapasitas) as total FROM tb_area_parkir"))['total'] ?? 0;
$total_tersedia  = $total_kapasitas - $total_terisi;

$nama    = htmlspecialchars($_SESSION['nama_lengkap']);
$inisial = strtoupper(substr($_SESSION['nama_lengkap'], 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — ParkAdmin</title>
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
            --pink:        #ec4899;
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
        .welcome p  { font-size: 13.5px; color: var(--muted); margin-top: 3px; }

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

        /* Stats */
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
            gap: 16px;
            animation: fadeUp .4s ease both;
            transition: box-shadow .22s, transform .22s;
            cursor: default;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover { box-shadow: var(--shadow-md); transform: translateY(-3px); }

        .stat-card:nth-child(1){animation-delay:.05s}
        .stat-card:nth-child(2){animation-delay:.09s}
        .stat-card:nth-child(3){animation-delay:.13s}
        .stat-card:nth-child(4){animation-delay:.17s}
        .stat-card:nth-child(5){animation-delay:.21s}
        .stat-card:nth-child(6){animation-delay:.25s}

        .stat-ico {
            width: 48px; height: 48px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
            transition: transform .2s;
        }

        .stat-card:hover .stat-ico { transform: scale(1.08) rotate(-3deg); }

        .stat-num { font-size: 26px; font-weight: 800; line-height: 1; letter-spacing: -.6px; }
        .stat-lbl { font-size: 12px; color: var(--muted); margin-top: 4px; font-weight: 500; }

        .c-blue   { background: var(--blue-bg); }
        .c-green  { background: var(--green-bg); }
        .c-orange { background: var(--orange-bg); }
        .c-purple { background: var(--purple-bg); }
        .c-teal   { background: var(--teal-bg); }
        .c-red    { background: var(--red-bg); }

        /* Section head */
        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            animation: fadeUp .4s ease .1s both;
        }

        .section-title { font-size: 15.5px; font-weight: 800; letter-spacing: -.3px; }
        .section-sub   { font-size: 12.5px; color: var(--muted); margin-top: 2px; }

        .section-live {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 11.5px;
            font-weight: 700;
            color: var(--green);
            background: var(--green-bg);
            padding: 5px 13px;
            border-radius: 99px;
            border: 1px solid rgba(18,183,106,.15);
        }

        .live-dot {
            width: 7px; height: 7px;
            background: var(--green);
            border-radius: 50%;
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0%,100% { opacity:1; transform:scale(1); }
            50%      { opacity:.4; transform:scale(.7); }
        }

        /* Table */
        .table-container {
            overflow-x: auto;
            border-radius: var(--radius);
            -webkit-overflow-scrolling: touch;
        }

        .table-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            animation: fadeUp .45s ease .12s both;
        }

        table { width:100%; border-collapse:collapse; min-width: 0; }

        thead tr { background: var(--bg); }

        thead th {
            padding: 12px 20px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .9px;
            text-transform: uppercase;
            color: var(--muted);
            text-align: left;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background .15s;
        }

        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #f7f8fd; }

        tbody td { padding: 14px 20px; font-size: 13.5px; vertical-align: middle; }

        .td-area { font-weight: 700; color: var(--text); }
        .td-num  { font-size: 13px; color: var(--text2); font-weight: 600; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .td-sisa { font-size: 13px; font-weight: 700; color: var(--text); }

        /* Progress bar */
        .bar-row { display:flex; align-items:center; gap:11px; }

        .bar-track {
            flex: 1;
            height: 8px;
            background: var(--bg2);
            border-radius: 99px;
            overflow: hidden;
            min-width: 60px;
        }

        .bar-fill {
            height: 100%;
            border-radius: 99px;
            transition: width .8s cubic-bezier(.4,0,.2,1);
        }

        .bar-pct {
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
            min-width: 36px;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        /* Badge */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11.5px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 99px;
            white-space: nowrap;
        }

        .badge-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }

        .b-ok   { background: var(--green-bg);  color: var(--green);  }
        .b-ok .badge-dot   { background: var(--green); }
        .b-warn { background: var(--orange-bg); color: var(--orange); }
        .b-warn .badge-dot { background: var(--orange); }
        .b-full { background: var(--red-bg);    color: var(--red);    }
        .b-full .badge-dot { background: var(--red); }

        /* Empty state */
        .empty-row td { text-align: center; padding: 40px; color: var(--muted); font-size: 13.5px; }

        /* Filter info */
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

        .page-dots:hover { background: var(--blue-bg); color: var(--blue); }

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
           RESPONSIVE — DESKTOP LEBAR
        ═══════════════════════════════════ */
        @media (max-width: 1150px) {
            .stats-grid { grid-template-columns: repeat(4, 1fr); }
        }

        @media (max-width: 920px) {
            .stats-grid { grid-template-columns: repeat(3, 1fr); }
        }

        /* ═══════════════════════════════════
           RESPONSIVE — TABLET / MOBILE
           Semua 5 kolom tetap tampil.
           Tabel bisa di-scroll horizontal jika layar terlalu sempit.
        ═══════════════════════════════════ */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                box-shadow: none;
            }

            .sidebar.open {
                transform: translateX(0);
                box-shadow: var(--shadow-lg);
            }

            .sb-close-btn { display: flex; }

            .main { margin-left: 0 !important; }

            .burger { display: flex; }

            .topbar { padding: 0 16px; }
            .content { padding: 16px; }

            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }

            /* Kecilkan padding & font — TIDAK ada kolom yang disembunyikan */
            thead th {
                padding: 8px 6px;
                font-size: 9px;
                letter-spacing: 0;
            }

            tbody td {
                padding: 8px 6px;
                font-size: 11px;
            }

            /* Progress bar lebih ringkas */
            .bar-track { min-width: 28px; }
            .bar-pct   { min-width: 22px; font-size: 9px; }
            .bar-row   { gap: 4px; }

            /* Badge lebih kecil */
            .badge { font-size: 10px; padding: 3px 6px; gap: 3px; }
            .badge-dot { width: 5px; height: 5px; }

            .pagination { gap: 6px; }

            .page-link, .page-dots {
                min-width: 32px;
                height: 32px;
                font-size: 12px;
            }

            .page-input-group { height: 32px; }
        }

        /* ═══════════════════════════════════
           RESPONSIVE — MOBILE KECIL
        ═══════════════════════════════════ */
        @media (max-width: 640px) {
            .content { padding: 12px; }

            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 9px; }

            .filter-info {
                flex-direction: column;
                align-items: flex-start;
            }

            .page-input-group {
                flex-wrap: wrap;
                height: auto;
                padding: 6px 8px;
            }

            .page-info {
                width: 100%;
                text-align: center;
                margin: 8px 0 0;
            }
        }

        /* ═══════════════════════════════════
           RESPONSIVE — MOBILE SANGAT KECIL
        ═══════════════════════════════════ */
        @media (max-width: 400px) {
            .topbar-page { font-size: 13px; }
            .welcome h1  { font-size: 18px; }

            thead th {
                padding: 6px 4px;
                font-size: 8px;
            }

            tbody td {
                padding: 6px 4px;
                font-size: 10px;
            }

            /* Di layar sangat kecil, sembunyikan angka % saja (bar tetap tampil) */
            .bar-pct { display: none; }
            .bar-track { min-width: 20px; }
        }
    </style>
</head>
<body>

<div class="overlay" id="overlay"></div>

<!-- Modal Lompat Halaman -->
<div id="pageModal" class="modal">
    <div class="modal-content">
        <h3>Lompat ke halaman</h3>
        <input type="number" id="pageNumberInput" class="modal-input" min="1" max="<?php echo $totalPages; ?>" placeholder="Masukkan nomor halaman">
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
        <a href="index.php" class="sb-link active">
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
        <a href="kendaraan.php" class="sb-link">
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
                <span class="breadcrumb-home">ParkAdmin</span>
                <span class="breadcrumb-sep">›</span>
                <span class="topbar-page">Dashboard</span>
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
                    <h1>Halo, <?php echo $nama; ?> 👋</h1>
                    <p>Selamat datang kembali.</p>
                </div>
                <div class="welcome-date">
                    <span>📅</span>
                    <span id="currentDate">–</span>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">

            <div class="stat-card">
                <div class="stat-ico c-blue">👥</div>
                <div>
                    <div class="stat-num"><?php echo $user_count; ?></div>
                    <div class="stat-lbl">Total User</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-ico c-purple">🗺️</div>
                <div>
                    <div class="stat-num"><?php echo $area_count; ?></div>
                    <div class="stat-lbl">Area Parkir</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-ico c-orange">💰</div>
                <div>
                    <div class="stat-num"><?php echo $tarif_count; ?></div>
                    <div class="stat-lbl">Total Tarif</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-ico c-teal">🚗</div>
                <div>
                    <div class="stat-num"><?php echo $kendaraan_count; ?></div>
                    <div class="stat-lbl">Kendaraan</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-ico c-red">🅿️</div>
                <div>
                    <div class="stat-num"><?php echo $total_terisi; ?></div>
                    <div class="stat-lbl">Slot Terisi</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-ico c-green">✅</div>
                <div>
                    <div class="stat-num"><?php echo $total_tersedia; ?></div>
                    <div class="stat-lbl">Slot Tersedia</div>
                </div>
            </div>

        </div>

        <!-- Filter Info -->
        <div class="filter-info">
            <div class="filter-info-left">
                📊 Menampilkan <strong><?php echo mysqli_num_rows($area_result); ?></strong> dari <strong><?php echo $totalArea; ?></strong> area parkir
            </div>
            <div class="filter-info-left">
                📄 <strong><?php echo $limit; ?></strong> data per halaman
            </div>
        </div>

        <!-- Section Head -->
        <div class="section-head">
            <div>
                <div class="section-title">Status Area Parkir</div>
                <div class="section-sub">Kapasitas dan ketersediaan per area</div>
            </div>
            <div class="section-live">
                <span class="live-dot"></span>
                Real-Time
            </div>
        </div>

        <!-- Table -->
        <div class="table-container">
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Area</th>
                            <th>Terisi / Kapasitas</th>
                            <th>Sisa</th>
                            <th>Penggunaan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $has_row = false;
                        while ($area = mysqli_fetch_assoc($area_result)):
                            $has_row = true;
                            $sisa = $area['kapasitas'] - $area['terisi'];
                            $pct  = $area['kapasitas'] > 0
                                ? round(($area['terisi'] / $area['kapasitas']) * 100)
                                : 0;

                            if ($pct < 50) {
                                $bar = '#12b76a'; $bc = 'b-ok';   $st = 'Tersedia';
                            } elseif ($pct < 80) {
                                $bar = '#f59e0b'; $bc = 'b-warn'; $st = 'Hampir Penuh';
                            } else {
                                $bar = '#ef4444'; $bc = 'b-full'; $st = 'Penuh';
                            }
                        ?>
                        <tr>
                            <td class="td-area"><?php echo htmlspecialchars($area['nama_area']); ?></td>
                            <td class="td-num"><?php echo $area['terisi']; ?> / <?php echo $area['kapasitas']; ?></td>
                            <td class="td-sisa"><?php echo $sisa; ?></td>
                            <td>
                                <div class="bar-row">
                                    <div class="bar-track">
                                        <div class="bar-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $bar; ?>;"></div>
                                    </div>
                                    <span class="bar-pct"><?php echo $pct; ?>%</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?php echo $bc; ?>">
                                    <span class="badge-dot"></span>
                                    <?php echo $st; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (!$has_row): ?>
                        <tr class="empty-row">
                            <td colspan="5">Belum ada data area parkir.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="page-link">‹ Sebelumnya</a>
            <?php else: ?>
                <span class="page-link disabled">‹ Sebelumnya</span>
            <?php endif; ?>

            <?php
            $startPage = max(1, $page - 2);
            $endPage   = min($totalPages, $page + 2);

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

            if ($endPage < $totalPages) {
                if ($endPage < $totalPages - 1) {
                    echo '<span class="page-dots" onclick="openPageModal()">...</span>';
                }
                echo '<a href="?page=' . $totalPages . '" class="page-link">' . $totalPages . '</a>';
            }
            ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="page-link">Selanjutnya ›</a>
            <?php else: ?>
                <span class="page-link disabled">Selanjutnya ›</span>
            <?php endif; ?>

            <div class="page-input-group">
                <span>Ke halaman</span>
                <input type="number" id="jumpToPage" class="page-input" min="1" max="<?php echo $totalPages; ?>" value="<?php echo $page; ?>">
                <button onclick="jumpToPage()" class="page-go-btn">Go</button>
            </div>

            <span class="page-info">
                Halaman <?php echo $page; ?> dari <?php echo $totalPages; ?>
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

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeSidebar();
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) closeSidebar();
    });

    /* ── Progress bar animate on load ── */
    document.querySelectorAll('.bar-fill').forEach(bar => {
        const w = bar.style.width;
        bar.style.width = '0';
        requestAnimationFrame(() => {
            setTimeout(() => { bar.style.width = w; }, 100);
        });
    });

    /* ── Pagination Functions ── */
    function jumpToPage() {
        const input = document.getElementById('jumpToPage');
        let targetPage = parseInt(input.value);
        const maxPage = <?php echo $totalPages; ?>;

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
        const maxPage = <?php echo $totalPages; ?>;

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