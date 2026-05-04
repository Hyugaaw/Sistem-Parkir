<?php
session_start();
include './../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

// Konfigurasi Pagination
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Keyword pencarian
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';

// Query untuk menghitung total data (dengan filter search)
$countQuery = "SELECT COUNT(*) as total FROM tb_log_aktivitas l 
               LEFT JOIN tb_user u ON l.id_user = u.id_user";
if (!empty($search)) {
    $countQuery .= " WHERE l.aktivitas LIKE '%$search%' 
                     OR u.username LIKE '%$search%'
                     OR l.waktu_aktivitas LIKE '%$search%'";
}
$countResult = mysqli_query($koneksi, $countQuery);
$totalData = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalData / $limit);

// Query untuk mengambil data dengan limit dan offset
$query = "SELECT l.*, u.username, u.role FROM tb_log_aktivitas l 
          LEFT JOIN tb_user u ON l.id_user = u.id_user";
if (!empty($search)) {
    $query .= " WHERE l.aktivitas LIKE '%$search%' 
                OR u.username LIKE '%$search%'
                OR l.waktu_aktivitas LIKE '%$search%'";
}
$query .= " ORDER BY l.waktu_aktivitas DESC LIMIT $limit OFFSET $offset";
$result = mysqli_query($koneksi, $query);
$logs = [];

while ($row = mysqli_fetch_assoc($result)) {
    $logs[] = $row;
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
    <title>Log Aktivitas — ParkAdmin</title>
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

        /* Search & Filter */
        .search-section {
            margin-bottom: 20px;
            animation: fadeUp .4s ease .05s both;
        }

        .search-box {
            display: flex;
            gap: 10px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 4px;
            max-width: 400px;
        }

        .search-input {
            flex: 1;
            padding: 10px 14px;
            border: none;
            background: transparent;
            font-size: 13px;
            font-family: inherit;
            outline: none;
            color: var(--text);
        }

        .search-input::placeholder { color: var(--dim); }

        .search-btn {
            background: var(--blue);
            border: none;
            padding: 0 18px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: background .18s;
            font-family: inherit;
        }

        .search-btn:hover { background: var(--blue-light); }

        .reset-btn {
            background: var(--bg);
            border: 1px solid var(--border);
            padding: 0 18px;
            border-radius: 8px;
            color: var(--text2);
            font-weight: 500;
            font-size: 12px;
            cursor: pointer;
            transition: all .18s;
            font-family: inherit;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .reset-btn:hover { background: var(--bg2); border-color: var(--dim); }

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

        .section-title { font-size: 15.5px; font-weight: 800; letter-spacing: -.3px; }
        .section-sub   { font-size: 12.5px; color: var(--muted); margin-top: 2px; }

        /* Table */
        .table-container {
            border-radius: var(--radius);
        }

        .table-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            box-shadow: var(--shadow-sm);
            animation: fadeUp .45s ease .12s both;
            scrollbar-width: thin;
            scrollbar-color: var(--dim) var(--bg);
        }

        .table-card::-webkit-scrollbar {
            height: 8px;
        }

        .table-card::-webkit-scrollbar-track {
            background: var(--bg);
            border-radius: 99px;
        }

        .table-card::-webkit-scrollbar-thumb {
            background: var(--dim);
            border-radius: 99px;
            border: 2px solid var(--bg);
        }

        .table-card::-webkit-scrollbar-thumb:hover {
            background: var(--muted);
        }

        table { width: 100%; border-collapse: collapse; min-width: 900px; }

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
        }

        tbody tr { border-bottom: 1px solid var(--border); transition: background .15s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #f7f8fd; }

        tbody td { padding: 14px 20px; font-size: 13.5px; vertical-align: middle; }

        /* Badge */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 99px;
        }

        .badge-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }

        .b-login   { background: #e6f9f1; color: #12b76a; }
        .b-login .badge-dot    { background: #12b76a; }
        .b-logout  { background: #fef2f2; color: #ef4444; }
        .b-logout .badge-dot   { background: #ef4444; }
        .b-tambah  { background: #edf0ff; color: #4361ee; }
        .b-tambah .badge-dot   { background: #4361ee; }
        .b-edit    { background: #fff8e6; color: #f59e0b; }
        .b-edit .badge-dot     { background: #f59e0b; }
        .b-hapus   { background: #fef2f2; color: #ef4444; }
        .b-hapus .badge-dot    { background: #ef4444; }
        .b-default { background: #f0f2f8; color: #3d4263; }
        .b-default .badge-dot  { background: #8b92b8; }

        /* Role Badge */
        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 99px;
        }

        .role-admin { background: #edf0ff; color: #4361ee; }
        .role-admin::before { content: '👨‍💼'; margin-right: 4px; }

        .role-petugas { background: #fff8e6; color: #f59e0b; }
        .role-petugas::before { content: '👤'; margin-right: 4px; }

        .role-owner { background: #e6f9f1; color: #12b76a; }
        .role-owner::before { content: '🏢'; margin-right: 4px; }

        /* Waktu */
        .time-text { font-size: 12px; color: var(--muted); font-variant-numeric: tabular-nums; }

        /* Empty state */
        .empty-row td { text-align: center; padding: 40px; color: var(--muted); font-size: 13.5px; }

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

        .modal-content h3 { margin-bottom: 8px; color: var(--text); font-size: 16px; }

        .modal-subtitle {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 16px;
            font-weight: 500;
        }

        .modal-subtitle strong { color: var(--blue); font-weight: 700; }

        .modal-input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 14px;
            margin-bottom: 16px;
            font-family: inherit;
            transition: border-color .2s;
        }

        .modal-input.error {
            border-color: var(--red);
            background: var(--red-bg);
        }

        .modal-error {
            display: none;
            background: var(--red-bg);
            border: 1px solid var(--red);
            color: var(--red);
            padding: 10px 12px;
            border-radius: var(--radius-sm);
            font-size: 12.5px;
            font-weight: 600;
            margin-bottom: 14px;
            text-align: left;
        }

        .modal-error.show { display: block; }

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
            .filter-info { flex-direction: column; align-items: flex-start; }
            .search-box { max-width: 100%; }
            .page-input-group { flex-wrap: wrap; height: auto; padding: 6px 8px; }
            .page-info { width: 100%; text-align: center; margin: 8px 0 0; }
        }

        /* ═══════════════════════════════════
           RESPONSIVE — MOBILE SANGAT KECIL
        ═══════════════════════════════════ */
        @media (max-width: 400px) {
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
        <div class="modal-subtitle">Total halaman: <strong><?php echo $totalPages; ?></strong></div>
        <div id="modalError" class="modal-error">⚠️ <span id="errorMessage"></span></div>
        <input type="number" id="pageNumberInput" class="modal-input" min="1" max="<?php echo $totalPages; ?>" placeholder="Masukkan nomor halaman (1-<?php echo $totalPages; ?>)">
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
        <a href="kendaraan.php" class="sb-link">
            <div class="sb-ico">🚗</div>
            Kelola Kendaraan
        </a>

        <div class="sb-section-label">Laporan</div>
        <a href="log_aktivitas.php" class="sb-link active">
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
                <span class="topbar-page">Log Aktivitas</span>
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
                    <h1>Log Aktivitas 📋</h1>
                    <p>Riwayat semua aktivitas yang terjadi dalam sistem.</p>
                </div>
            </div>
        </div>

        <!-- Search Box -->
        <div class="search-section">
            <form method="GET" action="" class="search-box">
                <input type="text" name="search" class="search-input"
                       placeholder="Cari aktivitas, user, atau waktu..."
                       value="<?php echo safeHtml($search); ?>">
                <button type="submit" class="search-btn">🔍 Cari</button>
                <?php if (!empty($search)): ?>
                    <a href="log_aktivitas.php" class="reset-btn">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Filter Info -->
        <div class="filter-info">
            <div class="filter-info-left">
                📊 Menampilkan <strong><?php echo count($logs); ?></strong> dari <strong><?php echo $totalData; ?></strong> aktivitas
                <?php if (!empty($search)): ?>
                    <span style="margin-left: 12px;">🔍 Pencarian: "<strong><?php echo safeHtml($search); ?></strong>"</span>
                <?php endif; ?>
            </div>
            <div class="filter-info-left">
                🔄 Urutan: <strong>Terbaru → Terlama</strong> | 📄 <strong><?php echo $limit; ?></strong> data per halaman
            </div>
        </div>

        <!-- Section -->
        <div class="section-head">
            <div>
                <div class="section-title">Daftar Log Aktivitas</div>
                <div class="section-sub">Semua aktivitas user tercatat secara otomatis</div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-container">
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>ID Log</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Aktivitas</th>
                            <th>Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($logs) > 0): ?>
                            <?php foreach ($logs as $log):
                                $aktivitas = strtolower(safeHtml($log['aktivitas']));
                                $badgeClass = 'b-default';
                                if (strpos($aktivitas, 'login') !== false) {
                                    $badgeClass = 'b-login';
                                } elseif (strpos($aktivitas, 'logout') !== false) {
                                    $badgeClass = 'b-logout';
                                } elseif (strpos($aktivitas, 'tambah') !== false) {
                                    $badgeClass = 'b-tambah';
                                } elseif (strpos($aktivitas, 'edit') !== false || strpos($aktivitas, 'update') !== false) {
                                    $badgeClass = 'b-edit';
                                } elseif (strpos($aktivitas, 'hapus') !== false || strpos($aktivitas, 'delete') !== false) {
                                    $badgeClass = 'b-hapus';
                                }
                            ?>
                            <tr>
                                <td><?php echo safeHtml($log['id_log']); ?></td>
                                <td><strong><?php echo safeHtml($log['username']); ?></strong></td>
                                <td>
                                    <?php 
                                        $role = strtolower(safeHtml($log['role']));
                                        $roleClass = 'role-admin';
                                        $roleText = 'Admin';
                                        
                                        if ($role === 'petugas') {
                                            $roleClass = 'role-petugas';
                                            $roleText = 'Petugas';
                                        } elseif ($role === 'owner') {
                                            $roleClass = 'role-owner';
                                            $roleText = 'Owner';
                                        }
                                    ?>
                                    <span class="role-badge <?php echo $roleClass; ?>"><?php echo $roleText; ?></span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <span class="badge-dot"></span>
                                        <?php echo safeHtml($log['aktivitas']); ?>
                                    </span>
                                </td>
                                <td class="time-text"><?php echo safeHtml($log['waktu_aktivitas']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="empty-row">
                                <td colspan="5">
                                    <?php if (!empty($search)): ?>
                                        Tidak ada hasil pencarian untuk "<strong><?php echo safeHtml($search); ?></strong>"
                                    <?php else: ?>
                                        Belum ada data log aktivitas.
                                    <?php endif; ?>
                                </td>
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
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="page-link">‹ Sebelumnya</a>
            <?php else: ?>
                <span class="page-link disabled">‹ Sebelumnya</span>
            <?php endif; ?>

            <?php
            $startPage = max(1, $page - 2);
            $endPage   = min($totalPages, $page + 2);

            if ($startPage > 1) {
                echo '<a href="?page=1&search=' . urlencode($search) . '" class="page-link">1</a>';
                if ($startPage > 2) {
                    echo '<span class="page-dots" onclick="openPageModal()">...</span>';
                }
            }

            for ($i = $startPage; $i <= $endPage; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"
                   class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor;

            if ($endPage < $totalPages) {
                if ($endPage < $totalPages - 1) {
                    echo '<span class="page-dots" onclick="openPageModal()">...</span>';
                }
                echo '<a href="?page=' . $totalPages . '&search=' . urlencode($search) . '" class="page-link">' . $totalPages . '</a>';
            }
            ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="page-link">Selanjutnya ›</a>
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
    const searchParam = '<?php echo urlencode($search); ?>';

    function jumpToPage() {
        const input = document.getElementById('jumpToPage');
        let targetPage = parseInt(input.value);
        const maxPage = <?php echo $totalPages; ?>;

        if (isNaN(targetPage)) targetPage = 1;
        if (targetPage < 1) targetPage = 1;
        if (targetPage > maxPage) targetPage = maxPage;

        window.location.href = `?page=${targetPage}&search=${searchParam}`;
    }

    const modal           = document.getElementById('pageModal');
    const pageNumberInput = document.getElementById('pageNumberInput');
    const modalError      = document.getElementById('modalError');
    const errorMessage    = document.getElementById('errorMessage');
    const maxPage = <?php echo $totalPages; ?>;

    function showError(message) {
        errorMessage.textContent = message;
        modalError.classList.add('show');
        pageNumberInput.classList.add('error');
    }

    function hideError() {
        errorMessage.textContent = '';
        modalError.classList.remove('show');
        pageNumberInput.classList.remove('error');
    }

    function openPageModal() {
        if (modal) {
            pageNumberInput.value = '';
            hideError();
            modal.classList.add('show');
            pageNumberInput.focus();
        }
    }

    function closeModal() {
        if (modal) {
            modal.classList.remove('show');
            hideError();
        }
    }

    function goToPage() {
        let targetPage = parseInt(pageNumberInput.value);

        if (isNaN(targetPage) || pageNumberInput.value === '') {
            showError('Masukkan nomor halaman yang valid');
            return;
        }

        if (targetPage < 1) {
            showError(`Halaman tidak boleh kurang dari 1`);
            return;
        }

        if (targetPage > maxPage) {
            showError(`Halaman tidak tersedia (melebihi halaman ${maxPage})`);
            return;
        }

        closeModal();
        window.location.href = `?page=${targetPage}&search=${searchParam}`;
    }

    if (pageNumberInput) {
        pageNumberInput.addEventListener('keypress', e => {
            if (e.key === 'Enter') goToPage();
        });
        pageNumberInput.addEventListener('input', () => {
            hideError();
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