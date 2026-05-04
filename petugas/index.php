<?php
session_start();
include './../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../../auth/login.php");
    exit();
}

$nama    = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Petugas');
$inisial = strtoupper(substr($_SESSION['nama_lengkap'] ?? 'P', 0, 1));

$transaksi_hari_ini = 0;
$total_pendapatan_hari_ini = 0;
$kendaraan_aktif = 0;

$today = date('Y-m-d');
$query_transaksi = "SELECT COUNT(*) as total, SUM(biaya_total) as pendapatan FROM tb_transaksi WHERE DATE(waktu_masuk) = '$today' AND status = 'keluar'";
$result_transaksi = mysqli_query($koneksi, $query_transaksi);
if ($row = mysqli_fetch_assoc($result_transaksi)) {
    $transaksi_hari_ini = $row['total'] ?? 0;
    $total_pendapatan_hari_ini = $row['pendapatan'] ?? 0;
}

$query_aktif = "SELECT COUNT(*) as total FROM tb_transaksi WHERE status = 'masuk' OR waktu_keluar IS NULL";
$result_aktif = mysqli_query($koneksi, $query_aktif);
$kendaraan_aktif = mysqli_fetch_assoc($result_aktif)['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Petugas — ParkPetugas</title>
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
            --blue-bg:     #edf0ff;
            --teal:        #06b6d4;
            --sidebar-w:   250px;
            --topbar-h:    60px;
            --radius:      16px;
            --radius-sm:   10px;
            --shadow-sm:   0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
            --shadow-md:   0 4px 16px rgba(0,0,0,.08), 0 2px 6px rgba(0,0,0,.05);
            --shadow-lg:   0 12px 40px rgba(0,0,0,.12), 0 4px 12px rgba(0,0,0,.06);
        }

        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        html { -webkit-text-size-adjust: 100%; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            -webkit-font-smoothing: antialiased;
        }

        /* ═══════════ SIDEBAR ═══════════ */
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
            padding: 0 16px;
            height: var(--topbar-h);
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }

        .sb-logo-icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--green), var(--teal));
            border-radius: 11px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            box-shadow: 0 4px 12px rgba(18,183,106,.35);
            flex-shrink: 0;
        }

        .sb-logo-name {
            font-size: 15px;
            font-weight: 800;
            letter-spacing: -.4px;
            color: var(--text);
        }

        .sb-logo-sub {
            font-size: 10px;
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
            transition: background .18s;
        }

        .sb-close-btn:hover { background: var(--bg2); }
        .sb-close-btn svg { display:block; }

        .sb-nav {
            padding: 12px 10px;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sb-nav::-webkit-scrollbar { width: 3px; }
        .sb-nav::-webkit-scrollbar-track { background: transparent; }
        .sb-nav::-webkit-scrollbar-thumb { background: var(--dim); border-radius: 99px; }

        .sb-section-label {
            font-size: 9.5px;
            font-weight: 800;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--dim);
            padding: 0 10px;
            margin: 14px 0 5px;
        }

        .sb-section-label:first-child { margin-top: 4px; }

        .sb-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 10px;
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

        .sb-link.active {
            color: var(--green);
            background: var(--green-bg);
            font-weight: 600;
        }

        .sb-link.active .sb-ico { background: rgba(18,183,106,.10); }

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
            padding: 10px;
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

        /* ═══════════ MAIN ═══════════ */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            min-width: 0;
        }

        /* ═══════════ TOPBAR ═══════════ */
        .topbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            height: var(--topbar-h);
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky; top: 0;
            z-index: 200;
            box-shadow: var(--shadow-sm);
        }

        .topbar-left { display:flex; align-items:center; gap:12px; min-width:0; }
        .topbar-right { display:flex; align-items:center; gap:10px; flex-shrink:0; }

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
            transition: background .18s;
            flex-shrink: 0;
        }

        .burger:hover { background: var(--bg2); }

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

        .breadcrumb-sep { color: var(--dim); font-size: 12px; }

        .topbar-page {
            font-size: 14px;
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
            flex-shrink: 0;
        }

        /* ═══════════ CONTENT ═══════════ */
        .content {
            padding: 24px;
            flex: 1;
        }

        /* Welcome */
        .welcome {
            margin-bottom: 20px;
            animation: fadeUp .35s ease both;
        }

        .welcome-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        .welcome h1 {
            font-size: 21px;
            font-weight: 800;
            letter-spacing: -.5px;
            color: var(--text);
            line-height: 1.2;
        }

        .welcome p {
            font-size: 13px;
            color: var(--muted);
            margin-top: 4px;
        }

        .welcome-date {
            font-size: 12px;
            color: var(--muted);
            font-weight: 500;
            background: var(--white);
            border: 1px solid var(--border);
            padding: 7px 14px;
            border-radius: 99px;
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 18px 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            animation: fadeUp .4s ease both;
            transition: box-shadow .22s, transform .22s;
            cursor: default;
            min-width: 0;
        }

        .stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .stat-card:nth-child(1){animation-delay:.05s}
        .stat-card:nth-child(2){animation-delay:.09s}
        .stat-card:nth-child(3){animation-delay:.13s}

        .stat-ico {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
            transition: transform .2s;
        }

        .stat-card:hover .stat-ico { transform: scale(1.08) rotate(-3deg); }

        .stat-body { min-width: 0; }

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
            font-size: 11.5px;
            color: var(--muted);
            margin-top: 5px;
            font-weight: 500;
            line-height: 1.3;
        }

        .c-green  { background: var(--green-bg); color: var(--green); }
        .c-orange { background: var(--orange-bg); color: var(--orange); }
        .c-blue   { background: var(--blue-bg); color: var(--blue); }

        /* Menu Cards */
        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            animation: fadeUp .4s ease .1s both;
        }

        .section-title {
            font-size: 15px;
            font-weight: 800;
            letter-spacing: -.3px;
        }

        .section-sub {
            font-size: 12.5px;
            color: var(--muted);
            margin-top: 2px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
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
        }

        .menu-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: transparent;
        }

        .menu-icon {
            width: 56px; height: 56px;
            background: linear-gradient(135deg, var(--green), var(--teal));
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 26px;
            flex-shrink: 0;
            transition: transform .25s;
        }

        .menu-card:hover .menu-icon { transform: scale(1.06) rotate(-5deg); }

        .menu-info { min-width: 0; }

        .menu-info h3 {
            font-size: 16px;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 5px;
        }

        .menu-info p {
            font-size: 12.5px;
            color: var(--muted);
            line-height: 1.4;
        }

        .menu-arrow {
            margin-left: auto;
            font-size: 20px;
            color: var(--dim);
            transition: transform .25s, color .25s;
            flex-shrink: 0;
        }

        .menu-card:hover .menu-arrow {
            transform: translateX(4px);
            color: var(--green);
        }

        /* ═══════════ ANIMATIONS ═══════════ */
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(12px); }
            to   { opacity:1; transform:translateY(0); }
        }

        /* ═══════════ OVERLAY ═══════════ */
        .overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(10,12,28,.3);
            backdrop-filter: blur(2px);
            z-index: 290;
            opacity: 0;
            transition: opacity .3s;
        }

        .overlay.active { display: block; opacity: 1; }

        /* ═══════════════════════════════════════
           RESPONSIVE — dari lebar terkecil naik
        ═══════════════════════════════════════ */

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
        }

        /* Tablet 600–900px */
        @media (max-width: 900px) and (min-width: 601px) {
            .content { padding: 20px; }

            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
            }

            .menu-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
        }

        /* Mobile 480–600px */
        @media (max-width: 600px) {
            .topbar { padding: 0 14px; }
            .content { padding: 14px 12px; }

            .welcome h1 { font-size: 17px; }
            .welcome p { font-size: 12px; }
            .welcome-date { display: none; }

            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
                margin-bottom: 16px;
            }

            .stat-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
                padding: 14px 12px;
                border-radius: 12px;
            }

            .stat-ico {
                width: 36px; height: 36px;
                font-size: 16px;
                border-radius: 9px;
            }

            .stat-num { font-size: 16px; }
            .stat-lbl { font-size: 10.5px; margin-top: 3px; }

            .menu-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .menu-card { padding: 18px 14px; gap: 14px; }

            .menu-icon {
                width: 48px; height: 48px;
                font-size: 22px;
                border-radius: 14px;
            }

            .menu-info h3 { font-size: 15px; }
            .menu-info p  { font-size: 12px; }
        }

        /* Mobile kecil < 380px (iPhone SE, Android budget) */
        @media (max-width: 380px) {
            .topbar { padding: 0 10px; }
            .topbar-page { font-size: 13px; }
            .breadcrumb-home { display: none; }
            .breadcrumb-sep  { display: none; }

            .content { padding: 12px 10px; }

            .welcome h1 { font-size: 16px; }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .stat-card {
                flex-direction: row;
                align-items: center;
                gap: 12px;
                padding: 14px 12px;
            }

            .stat-ico { width: 38px; height: 38px; font-size: 17px; }
            .stat-num { font-size: 18px; }

            .section-title { font-size: 14px; }

            .menu-card { padding: 16px 12px; gap: 12px; }

            .menu-icon { width: 44px; height: 44px; font-size: 20px; border-radius: 12px; }
            .menu-info h3 { font-size: 14px; }
            .menu-info p  { font-size: 11.5px; }
            .menu-arrow   { font-size: 18px; }
        }

        /* Extra kecil < 320px */
        @media (max-width: 320px) {
            .content { padding: 10px 8px; }
            .menu-card { padding: 14px 10px; }
            .menu-icon { width: 40px; height: 40px; font-size: 18px; }
        }
    </style>
</head>
<body>

<div class="overlay" id="overlay"></div>

<!-- ═══════════════ SIDEBAR ═══════════════ -->
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
        <a href="index.php" class="sb-link active">
            <div class="sb-ico">🏠</div>
            Dashboard
        </a>
        <a href="transaksi.php" class="sb-link">
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
                    <p>Selamat datang — kelola transaksi dengan mudah.</p>
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
                <div class="stat-ico c-green">🚗</div>
                <div class="stat-body">
                    <div class="stat-num"><?php echo $kendaraan_aktif; ?></div>
                    <div class="stat-lbl">Kendaraan Aktif</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-ico c-orange">📝</div>
                <div class="stat-body">
                    <div class="stat-num"><?php echo $transaksi_hari_ini; ?></div>
                    <div class="stat-lbl">Transaksi Selesai</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-ico c-blue">💰</div>
                <div class="stat-body">
                    <div class="stat-num">Rp&nbsp;<?php echo number_format($total_pendapatan_hari_ini, 0, ',', '.'); ?></div>
                    <div class="stat-lbl">Pendapatan Hari Ini</div>
                </div>
            </div>
        </div>

        <!-- Menu Section -->
        <div class="section-head">
            <div>
                <div class="section-title">Menu Petugas</div>
                <div class="section-sub">Pilih menu untuk melanjutkan tugas</div>
            </div>
        </div>

        <div class="menu-grid">
            <a href="transaksi.php" class="menu-card">
                <div class="menu-icon">💰</div>
                <div class="menu-info">
                    <h3>Kelola Transaksi</h3>
                    <p>Input dan monitoring transaksi parkir</p>
                </div>
                <div class="menu-arrow">→</div>
            </a>

            <a href="struk.php" class="menu-card">
                <div class="menu-icon">🧾</div>
                <div class="menu-info">
                    <h3>Cetak Struk</h3>
                    <p>Cetak kwitansi / struk parkir</p>
                </div>
                <div class="menu-arrow">→</div>
            </a>
        </div>

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

    burgerBtn.addEventListener('click', () =>
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar()
    );

    sbCloseBtn.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSidebar(); });
    window.addEventListener('resize', () => { if (window.innerWidth > 900) closeSidebar(); });
</script>

</body>
</html>