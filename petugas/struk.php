<?php
session_start();
include './../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../../auth/login.php");
    exit();
}

$nama    = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Petugas');
$inisial = strtoupper(substr($_SESSION['nama_lengkap'] ?? 'P', 0, 1));

$query_transaksi = "SELECT t.id_parkir, k.plat_nomor, t.waktu_masuk, t.waktu_keluar, t.biaya_total, t.durasi_jam, tf.tarif_per_jam, a.nama_area
                    FROM tb_transaksi t
                    LEFT JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
                    LEFT JOIN tb_tarif tf ON t.id_tarif = tf.id_tarif
                    LEFT JOIN tb_area_parkir a ON t.id_area = a.id_area
                    WHERE t.status = 'keluar'
                    ORDER BY t.waktu_masuk DESC";
$result_transaksi = mysqli_query($koneksi, $query_transaksi);
$transaksis = [];
while ($row = mysqli_fetch_assoc($result_transaksi)) {
    $transaksis[] = $row;
}

function safeHtml($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Struk — ParkPetugas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>
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

        /* ── SIDEBAR ── */
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

        .sb-nav { padding: 14px 12px; flex: 1; overflow-y: auto; overflow-x: hidden; }
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

        .sb-link.active { color: var(--green); background: var(--green-bg); font-weight: 600; }
        .sb-link.active .sb-ico { background: rgba(18,183,106,.10); }
        .sb-link.active::before {
            content: ''; position: absolute;
            right: 0; top: 50%; transform: translateY(-50%);
            width: 3px; height: 20px;
            background: var(--green); border-radius: 99px 0 0 99px;
        }

        .sb-footer { padding: 12px; border-top: 1px solid var(--border); flex-shrink: 0; }

        .sb-user {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 10px; border-radius: 12px;
            background: var(--bg); border: 1px solid var(--border);
            transition: background .18s;
        }
        .sb-user:hover { background: var(--bg2); }

        .sb-avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: linear-gradient(135deg, var(--green), var(--teal));
            color: white; font-size: 13px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; box-shadow: 0 2px 8px rgba(18,183,106,.3);
        }

        .sb-uname { font-size: 12.5px; font-weight: 700; color: var(--text); line-height: 1.2; }
        .sb-urole { font-size: 10.5px; color: var(--muted); font-weight: 500; }

        .sb-logout {
            display: flex; align-items: center; gap: 9px;
            padding: 9px 10px; margin-top: 6px; border-radius: 11px;
            color: var(--red); text-decoration: none;
            font-size: 13px; font-weight: 600; transition: background .18s;
        }
        .sb-logout:hover { background: var(--red-bg); }

        /* ── MAIN ── */
        .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

        /* ── TOPBAR ── */
        .topbar {
            background: var(--white); border-bottom: 1px solid var(--border);
            height: var(--topbar-h); padding: 0 28px;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 200; box-shadow: var(--shadow-sm);
        }

        .topbar-left  { display:flex; align-items:center; gap:14px; min-width: 0; }
        .topbar-right { display:flex; align-items:center; gap:10px; flex-shrink: 0; }

        .burger {
            display: none; width: 38px; height: 38px;
            background: var(--bg); border: 1px solid var(--border);
            border-radius: 10px; cursor: pointer;
            align-items: center; justify-content: center;
            flex-direction: column; gap: 5px;
            transition: background .18s, border-color .18s; flex-shrink: 0;
        }
        .burger:hover { background: var(--bg2); border-color: var(--dim); }
        .burger span { display: block; width: 18px; height: 2px; background: var(--text2); border-radius: 2px; transition: all .3s cubic-bezier(.4,0,.2,1); }
        .burger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
        .burger.open span:nth-child(2) { opacity:0; transform: scaleX(0); }
        .burger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

        .topbar-breadcrumb { display: flex; align-items: center; gap: 7px; min-width: 0; }
        .breadcrumb-home   { font-size: 13px; color: var(--muted); font-weight: 500; white-space: nowrap; }
        .breadcrumb-sep    { color: var(--dim); font-size: 12px; flex-shrink: 0; }
        .topbar-page       { font-size: 14.5px; font-weight: 700; color: var(--text); white-space: nowrap; }

        .tb-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, var(--green), var(--teal));
            color: white; font-size: 13px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 8px rgba(18,183,106,.28); cursor: pointer;
            flex-shrink: 0;
        }

        /* ── CONTENT ── */
        .content {
            padding: 28px; flex: 1;
            display: flex; justify-content: center; align-items: flex-start;
        }

        .struk-wrapper { max-width: 400px; width: 100%; }

        /* ── SELECTOR CARD ── */
        .selector-card {
            background: var(--white); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 20px;
            margin-bottom: 20px; animation: fadeUp .35s ease both;
        }

        .selector-label { font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 10px; display: block; }

        .selector-select {
            width: 100%; padding: 12px 16px;
            border: 1px solid var(--border); border-radius: var(--radius-sm);
            font-family: inherit; font-size: 14px;
            background: var(--white); cursor: pointer; transition: border-color .2s;
        }
        .selector-select:focus { outline: none; border-color: var(--green); }

        /* ── STRUK PAPER ── */
        .struk-paper {
            background: white; border-radius: var(--radius-sm);
            box-shadow: var(--shadow-lg); overflow: hidden;
            animation: fadeUp .4s ease .1s both;
            max-width: 350px; margin: 0 auto;
        }

        @page { margin: 0; }

        @media print {
            body * { visibility: hidden; }
            .struk-paper, .struk-paper * { visibility: visible; }
            .struk-paper {
                position: absolute; top: 0; left: 0;
                width: 100%; margin: 0; padding: 0;
                box-shadow: none; border-radius: 0;
            }
            .no-print { display: none !important; }
            .btn-print, .selector-card, .sidebar, .topbar, nav { display: none !important; }
        }

        .struk-header {
            background: linear-gradient(135deg, var(--green), var(--teal));
            color: white; padding: 15px; text-align: center;
        }
        .struk-header h2 { font-size: 18px; font-weight: 800; letter-spacing: 2px; margin-bottom: 3px; }
        .struk-header p  { font-size: 9px; opacity: 0.9; }

        .struk-body { padding: 12px; }

        .struk-info-row {
            display: flex; justify-content: space-between;
            margin-bottom: 8px; font-size: 11px; line-height: 1.4;
        }

        .struk-label { color: var(--muted); font-weight: 500; }
        .struk-value { color: var(--text); font-weight: 600; text-align: right; }

        .struk-divider { border: none; border-top: 1px dashed var(--border); margin: 10px 0; }

        .struk-total {
            background: var(--green-bg); padding: 10px;
            border-radius: var(--radius-sm); margin: 10px 0;
        }
        .struk-total .struk-info-row { margin-bottom: 0; }
        .struk-total .struk-value    { font-size: 16px; color: var(--green); }

        .struk-footer {
            text-align: center; padding: 10px;
            border-top: 1px solid var(--border);
            font-size: 9px; color: var(--muted);
        }

        .barcode-container {
            text-align: center; padding: 10px 8px 6px;
            background: var(--white); margin: 5px 0;
            border-radius: var(--radius-sm);
        }

        #barcodeDisplay { width: 100%; height: auto; max-height: 55px; }

        .barcode-number {
            font-family: 'Courier New', monospace;
            font-size: 10px; letter-spacing: 2px;
            margin-top: 4px; color: var(--text); font-weight: 600;
        }

        /* ── PRINT BUTTON ── */
        .btn-print {
            background: var(--green); color: white;
            padding: 14px 20px; border: none;
            border-radius: var(--radius-sm); font-weight: 700;
            font-size: 14px; cursor: pointer; width: 100%;
            margin-top: 16px; transition: all .2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-print:hover { background: var(--teal); transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .btn-print:active { transform: translateY(0); }

        .empty-state { text-align: center; padding: 40px; color: var(--muted); }

        @keyframes fadeUp {
            from { opacity:0; transform:translateY(14px); }
            to   { opacity:1; transform:translateY(0); }
        }

        /* ── OVERLAY ── */
        .overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(10,12,28,.25);
            backdrop-filter: blur(3px); z-index: 290;
            opacity: 0; transition: opacity .3s;
        }
        .overlay.active { display: block; opacity: 1; }

        /* ── RESPONSIVE MOBILE ── */
        @media (max-width: 768px) {
            /* Sidebar */
            .sidebar { transform: translateX(-100%); box-shadow: none; }
            .sidebar.open { transform: translateX(0); box-shadow: var(--shadow-lg); }
            .sb-close-btn { display: flex; }

            /* Main & topbar */
            .main        { margin-left: 0; }
            .burger      { display: flex; }
            .topbar      { padding: 0 16px; height: 56px; }
            .topbar-left { gap: 10px; }
            .breadcrumb-home { display: none; }  /* hemat ruang, cukup nama halaman */
            .topbar-page { font-size: 14px; }

            /* Content */
            .content { padding: 14px 12px 24px; }

            /* Selector card */
            .selector-card  { padding: 16px; border-radius: 12px; margin-bottom: 14px; }
            .selector-label { font-size: 12.5px; }
            .selector-select {
                font-size: 13px; padding: 11px 12px;
            }

            /* Struk paper — full width, tak terpotong */
            .struk-paper {
                max-width: 100%;
                border-radius: 12px;
            }

            .struk-header h2 { font-size: 16px; }
            .struk-header p  { font-size: 8.5px; }

            .struk-body { padding: 14px; }

            .struk-info-row {
                font-size: 12px;
                margin-bottom: 9px;
            }

            .struk-total .struk-value { font-size: 17px; }

            /* Barcode */
            #barcodeDisplay { max-height: 50px; }
            .barcode-number { font-size: 9.5px; }

            /* Print button */
            .btn-print {
                font-size: 14px; padding: 15px 20px;
                border-radius: 12px; margin-top: 14px;
                /* pastikan mudah di-tap */
                min-height: 50px;
            }
        }

        @media (max-width: 380px) {
            .content { padding: 10px 10px 20px; }
            .struk-info-row { font-size: 11px; }
            .selector-card  { padding: 13px; }
        }
    </style>
</head>
<body>

<div class="overlay" id="overlay"></div>

<!-- SIDEBAR -->
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
        <a href="transaksi.php" class="sb-link">
            <div class="sb-ico">💰</div>
            Transaksi Parkir
        </a>
        <a href="struk.php" class="sb-link active">
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

<!-- MAIN -->
<div class="main">

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="topbar-left">
            <button class="burger" id="burgerBtn" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
            <div class="topbar-breadcrumb">
                <span class="breadcrumb-home">ParkPetugas</span>
                <span class="breadcrumb-sep">›</span>
                <span class="topbar-page">Cetak Struk</span>
            </div>
        </div>
        <div class="topbar-right">
            <div class="tb-avatar" title="<?php echo $nama; ?>"><?php echo $inisial; ?></div>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="content">
        <div class="struk-wrapper">

            <!-- Selector Transaksi -->
            <div class="selector-card no-print">
                <label class="selector-label">Pilih Transaksi:</label>
                <select id="transaksiSelect" class="selector-select">
                    <option value="">-- Pilih Transaksi --</option>
                    <?php foreach ($transaksis as $t): ?>
                    <option value="<?php echo $t['id_parkir']; ?>"
                            data-plat="<?php echo safeHtml($t['plat_nomor']); ?>"
                            data-masuk="<?php echo safeHtml($t['waktu_masuk']); ?>"
                            data-keluar="<?php echo safeHtml($t['waktu_keluar']); ?>"
                            data-durasi="<?php echo $t['durasi_jam']; ?>"
                            data-tarif="<?php echo $t['tarif_per_jam']; ?>"
                            data-biaya="<?php echo $t['biaya_total']; ?>"
                            data-area="<?php echo safeHtml($t['nama_area']); ?>">
                        <?php echo $t['id_parkir'] . ' - ' . safeHtml($t['plat_nomor']) . ' - ' . date('d/m/Y H:i', strtotime($t['waktu_masuk'])); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Struk Paper -->
            <div class="struk-paper" id="strukPaper">
                <div class="struk-header">
                    <h2>PARKIR</h2>
                    <p>Kwitansi Pembayaran Parkir</p>
                </div>

                <div class="struk-body" id="strukBody">
                    <div class="struk-info-row">
                        <span class="struk-label">No. Transaksi</span>
                        <span class="struk-value" id="strukId">-</span>
                    </div>
                    <div class="struk-info-row">
                        <span class="struk-label">Tanggal</span>
                        <span class="struk-value" id="strukTanggal">-</span>
                    </div>
                    <div class="struk-info-row">
                        <span class="struk-label">Area Parkir</span>
                        <span class="struk-value" id="strukArea">-</span>
                    </div>

                    <hr class="struk-divider">

                    <div class="struk-info-row">
                        <span class="struk-label">Plat Nomor</span>
                        <span class="struk-value" id="strukPlat">-</span>
                    </div>
                    <div class="struk-info-row">
                        <span class="struk-label">Waktu Masuk</span>
                        <span class="struk-value" id="strukMasuk">-</span>
                    </div>
                    <div class="struk-info-row">
                        <span class="struk-label">Waktu Keluar</span>
                        <span class="struk-value" id="strukKeluar">-</span>
                    </div>
                    <div class="struk-info-row">
                        <span class="struk-label">Durasi Parkir</span>
                        <span class="struk-value" id="strukDurasi">-</span>
                    </div>

                    <hr class="struk-divider">

                    <div class="struk-info-row">
                        <span class="struk-label">Tarif per Jam</span>
                        <span class="struk-value" id="strukTarif">-</span>
                    </div>

                    <div class="struk-total">
                        <div class="struk-info-row">
                            <span class="struk-label" style="font-weight: 800;">TOTAL BAYAR</span>
                            <span class="struk-value" style="font-size: 16px;" id="strukTotal">-</span>
                        </div>
                    </div>

                    <!-- Barcode (Code128) -->
                    <div class="barcode-container" id="barcodeContainer">
                        <svg id="barcodeDisplay"></svg>
                        <div class="barcode-number" id="barcodeNumber">-</div>
                    </div>
                </div>

                <div class="struk-footer">
                    <p>Terima Kasih</p>
                    <p style="font-size: 8px; margin-top: 3px;">Simpan struk ini sebagai bukti pembayaran</p>
                </div>
            </div>

            <!-- Tombol Print -->
            <button class="btn-print no-print" onclick="window.print()">
                🖨️ Print Struk
            </button>

            <?php if (empty($transaksis)): ?>
            <div class="empty-state no-print">
                <p>Belum ada transaksi selesai.</p>
                <p style="font-size: 12px; margin-top: 8px;">Silakan lakukan transaksi parkir terlebih dahulu.</p>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
    const transaksiData = {};
    <?php foreach ($transaksis as $t): ?>
    transaksiData[<?php echo $t['id_parkir']; ?>] = {
        id: '<?php echo $t['id_parkir']; ?>',
        plat: '<?php echo safeHtml($t['plat_nomor']); ?>',
        masuk: '<?php echo safeHtml($t['waktu_masuk']); ?>',
        keluar: '<?php echo safeHtml($t['waktu_keluar']); ?>',
        durasi: <?php echo intval($t['durasi_jam'] ?? 0); ?>,
        tarif: '<?php echo $t['tarif_per_jam'] ?? 0; ?>',
        biaya: '<?php echo $t['biaya_total'] ?? 0; ?>',
        area: '<?php echo safeHtml($t['nama_area']); ?>'
    };
    <?php endforeach; ?>

    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(angka);
    }

    function hitungDurasi(masuk, keluar) {
        if (!masuk || !keluar) return '-';
        const tMasuk   = new Date(masuk);
        const tKeluar  = new Date(keluar);
        const selisihMs = tKeluar - tMasuk;
        if (selisihMs <= 0) return '0 menit';
        const totalMenit = Math.floor(selisihMs / 60000);
        const jam   = Math.floor(totalMenit / 60);
        const menit = totalMenit % 60;
        if (jam > 0 && menit > 0) return jam + ' jam ' + menit + ' menit';
        if (jam > 0)              return jam + ' jam';
        return menit + ' menit';
    }

    function updateStruk(selectElement) {
        const transaksiId = selectElement.value;

        if (!transaksiId || !transaksiData[transaksiId]) {
            ['strukId','strukTanggal','strukArea','strukPlat','strukMasuk',
             'strukKeluar','strukDurasi','strukTarif','strukTotal','barcodeNumber']
                .forEach(id => document.getElementById(id).textContent = '-');
            document.getElementById('barcodeDisplay').innerHTML = '';
            return;
        }

        const data = transaksiData[transaksiId];

        const tanggal = data.masuk ? new Date(data.masuk).toLocaleDateString('id-ID', {
            year: 'numeric', month: 'short', day: 'numeric'
        }) : '-';

        const waktuMasukFormatted = data.masuk ? new Date(data.masuk).toLocaleString('id-ID', {
            hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: 'numeric'
        }) : '-';

        const waktuKeluarFormatted = data.keluar ? new Date(data.keluar).toLocaleString('id-ID', {
            hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: 'numeric'
        }) : '-';

        const durasi        = hitungDurasi(data.masuk, data.keluar);
        const barcodeNumber = 'PRK' + String(transaksiId).padStart(6, '0');

        document.getElementById('strukId').textContent      = transaksiId;
        document.getElementById('strukTanggal').textContent = tanggal;
        document.getElementById('strukArea').textContent    = data.area || '-';
        document.getElementById('strukPlat').textContent    = data.plat || '-';
        document.getElementById('strukMasuk').textContent   = waktuMasukFormatted;
        document.getElementById('strukKeluar').textContent  = waktuKeluarFormatted;
        document.getElementById('strukDurasi').textContent  = durasi;
        document.getElementById('strukTarif').textContent   = formatRupiah(data.tarif);
        document.getElementById('strukTotal').textContent   = formatRupiah(data.biaya);
        document.getElementById('barcodeNumber').textContent = barcodeNumber;

        JsBarcode("#barcodeDisplay", barcodeNumber, {
            format: "CODE128",
            width: 1.8,
            height: 50,
            displayValue: false,
            margin: 0,
            background: "#ffffff",
            lineColor: "#000000"
        });
    }

    const transaksiSelect = document.getElementById('transaksiSelect');
    if (transaksiSelect) {
        transaksiSelect.addEventListener('change', function() { updateStruk(this); });
        if (transaksiSelect.options.length > 1) {
            transaksiSelect.selectedIndex = 1;
            updateStruk(transaksiSelect);
        }
    }

    /* ── Sidebar toggle ── */
    const sidebar    = document.getElementById('sidebar');
    const burgerBtn  = document.getElementById('burgerBtn');
    const sbCloseBtn = document.getElementById('sbCloseBtn');
    const overlay    = document.getElementById('overlay');

    function openSidebar()  {
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
</script>

</body>
</html>