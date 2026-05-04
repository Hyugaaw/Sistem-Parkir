<?php
session_start();
include '../koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];
    
    $query = "SELECT * FROM tb_user WHERE BINARY username = '$username'";
    $result = mysqli_query($koneksi, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        $password_valid = false;
        if (password_verify($password, $user['password'])) {
            $password_valid = true;
        } elseif ($password === $user['password']) {
            $password_valid = true;
        }
        
        $is_active = ($user['status_aktif'] == 1 || $user['status_aktif'] === NULL);
        
        if ($password_valid && $is_active) {
            $_SESSION['id_user']       = $user['id_user'];
            $_SESSION['nama_lengkap']  = !empty($user['nama_lengkap']) ? $user['nama_lengkap'] : $user['username'];
            $_SESSION['username']      = $user['username'];
            $_SESSION['role']          = $user['role'];
            
            $id_user   = $user['id_user'];
            $aktivitas = "Login";
            $waktu     = date('Y-m-d H:i:s');
            $log_query = "INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_aktivitas) VALUES ('$id_user', '$aktivitas', '$waktu')";
            mysqli_query($koneksi, $log_query);
            
            if ($user['role'] == 'admin')        header("Location: ../admin/index.php");
            elseif ($user['role'] == 'petugas')  header("Location: ../petugas/index.php");
            elseif ($user['role'] == 'owner')    header("Location: ../owner/index.php");
            exit();
        } else {
            $error = !$password_valid
                ? "Username atau password salah!"
                : "Akun Anda tidak aktif. Hubungi admin!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — ParkAdmin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <style>
        :root {
            --blue:       #2563EB;
            --blue-dark:  #1741a6;
            --blue-mid:   #3b82f6;
            --white:      #ffffff;
            --off-white:  #f5f7ff;
            --text:       #111827;
            --muted:      #6b7280;
            --border:     #e2e8f0;
            --red:        #ef4444;
        }

        * { margin:0; padding:0; box-sizing:border-box; }

        html, body {
            height: 100%;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--off-white);
            min-height: 100vh;
            padding: 20px;
        }

        /* ── PHONE CARD ── */
        .card {
            width: 360px;
            min-height: 660px;
            background: var(--blue);
            border-radius: 38px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 30px 80px rgba(37,99,235,.35), 0 8px 24px rgba(0,0,0,.12);
            display: flex;
            flex-direction: column;
        }

        /* ── TOP SECTION (white) ── */
        .top-section {
            background: var(--white);
            flex: 0 0 auto;
            padding: 48px 32px 0;
            position: relative;
            z-index: 2;
            border-radius: 0 0 50px 50px;
        }

        .brand {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--blue);
            margin-bottom: 6px;
        }

        .top-headline {
            font-size: 26px;
            font-weight: 800;
            color: var(--text);
            line-height: 1.2;
            letter-spacing: -.5px;
        }

        .top-headline em {
            font-style: italic;
            color: var(--blue);
        }

        /* brush stroke illustration area */
        .illustration-wrap {
            margin: 24px -32px 0;
            position: relative;
            height: 150px;
            overflow: hidden;
        }

        .brush {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 130px;
        }

        .park-icon {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -54%);
            font-size: 70px;
            filter: drop-shadow(0 6px 16px rgba(37,99,235,.25));
            z-index: 3;
        }

        /* ── BOTTOM SECTION (blue) ── */
        .bottom-section {
            flex: 1;
            background: var(--blue);
            padding: 32px 32px 40px;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* blue brush on bottom */
        .bottom-brush {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 120px;
            pointer-events: none;
            z-index: 0;
        }

        .bottom-content { position: relative; z-index: 1; }

        .welcome-text h2 {
            font-size: 24px;
            font-weight: 800;
            color: var(--white);
            letter-spacing: -.4px;
        }

        .welcome-text p {
            font-size: 13.5px;
            color: rgba(255,255,255,.7);
            margin-top: 3px;
        }

        /* ── FORM ── */
        .form { margin-top: 24px; display: flex; flex-direction: column; gap: 12px; }

        .input-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,.97);
            border-radius: 50px;
            padding: 0 18px;
            height: 50px;
            box-shadow: 0 2px 12px rgba(0,0,0,.08);
            transition: box-shadow .2s;
        }

        .input-wrap:focus-within {
            box-shadow: 0 0 0 3px rgba(255,255,255,.4), 0 2px 12px rgba(0,0,0,.08);
        }

        .input-icon { font-size: 17px; flex-shrink: 0; opacity: .6; }

        .input-wrap input {
            flex: 1;
            border: none;
            background: transparent;
            font-family: inherit;
            font-size: 14px;
            font-weight: 500;
            color: var(--text);
            outline: none;
        }

        .input-wrap input::placeholder { color: #aab; }

        /* ── TOGGLE PASSWORD BUTTON ── */
        .toggle-pw {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #aab;
            flex-shrink: 0;
            border-radius: 50%;
            transition: color .2s, background .2s;
            outline: none;
        }

        .toggle-pw:hover {
            color: var(--blue);
            background: rgba(37,99,235,.08);
        }

        .toggle-pw:focus-visible {
            box-shadow: 0 0 0 2px var(--blue);
        }

        .toggle-pw.active {
            color: var(--blue);
        }

        /* error */
        .error-msg {
            display: flex;
            align-items: center;
            gap: 7px;
            background: rgba(239,68,68,.15);
            border: 1px solid rgba(239,68,68,.3);
            color: #fecaca;
            font-size: 12.5px;
            font-weight: 500;
            padding: 9px 14px;
            border-radius: 12px;
            margin-bottom: 4px;
        }

        /* submit row */
        .submit-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 20px;
        }

        .submit-hint {
            font-size: 13px;
            color: rgba(255,255,255,.65);
            font-weight: 500;
            max-width: 160px;
            line-height: 1.4;
        }

        /* ── SUBMIT BUTTON ── */
        .submit-btn {
            width: 54px; height: 54px;
            border-radius: 50%;
            /* Default: gelap (belum memenuhi ketentuan) */
            background: rgba(255,255,255,.18);
            border: 2px solid rgba(255,255,255,.25);
            cursor: not-allowed;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            color: rgba(255,255,255,.45);
            box-shadow: none;
            transition: background .3s, border-color .3s, color .3s, box-shadow .3s, transform .2s;
            flex-shrink: 0;
        }

        /* Aktif: terang saat semua ketentuan terpenuhi */
        .submit-btn.ready {
            background: var(--white);
            border-color: var(--white);
            color: var(--text);
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(255,255,255,.35), 0 2px 8px rgba(0,0,0,.15);
        }

        .submit-btn.ready:hover {
            transform: scale(1.08);
            box-shadow: 0 6px 28px rgba(255,255,255,.45), 0 4px 12px rgba(0,0,0,.2);
        }

        .submit-btn.ready:active { transform: scale(.96); }

        /* Arrow icon inside button */
        .submit-btn svg {
            width: 22px;
            height: 22px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2.5;
            stroke-linecap: round;
            stroke-linejoin: round;
            transition: transform .2s;
        }

        .submit-btn.ready:hover svg {
            transform: translateX(2px);
        }

        /* terms */
        .terms {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
        }

        .terms input[type=checkbox] {
            width: 15px; height: 15px;
            accent-color: white;
            cursor: pointer;
        }

        .terms label {
            font-size: 12px;
            color: rgba(255,255,255,.6);
            cursor: pointer;
        }

        .terms label a { color: rgba(255,255,255,.9); text-decoration: underline; }

        /* ── DECO DOTS ── */
        .dot {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,.08);
        }

        .dot-1 { width:80px; height:80px; top:-20px; right:-20px; }
        .dot-2 { width:48px; height:48px; top:40px;  right:30px;  background:rgba(255,255,255,.05); }

        /* ── RESPONSIVE ── */
        @media (max-width: 420px) {
            .card { width: 100%; min-height: 100dvh; border-radius: 0; }
            body  { padding: 0; align-items: stretch; }
        }
    </style>
</head>
<body>

<div class="card">

    <!-- TOP WHITE SECTION -->
    <div class="top-section">
        <div class="brand">🅿 ParkAdmin</div>
        <div class="top-headline">
            Selamat Datang di<br><em>Sistem Parkir</em>
        </div>

        <div class="illustration-wrap">
            <!-- brush stroke SVG -->
            <svg class="brush" viewBox="0 0 360 130" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M-10,90 C40,50 80,115 140,80 C200,45 240,105 300,70 C330,55 355,75 370,65 L370,140 L-10,140 Z"
                      fill="#2563EB" opacity=".12"/>
                <path d="M-10,105 C50,65 100,120 170,90 C220,68 270,115 340,85 L370,80 L370,140 L-10,140 Z"
                      fill="#2563EB" opacity=".18"/>
                <path d="M-10,118 C60,88 120,130 200,108 C260,90 310,125 370,100 L370,140 L-10,140 Z"
                      fill="#2563EB" opacity=".28"/>
            </svg>
            <div class="park-icon">🚗</div>
        </div>
    </div>

    <!-- BOTTOM BLUE SECTION -->
    <div class="bottom-section">

        <!-- decorative dots -->
        <div class="dot dot-1"></div>
        <div class="dot dot-2"></div>

        <!-- white brush on blue bg -->
        <svg class="bottom-brush" viewBox="0 0 360 120" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M-10,80 C60,40 130,100 210,60 C270,30 320,80 370,55 L370,120 L-10,120 Z"
                  fill="rgba(255,255,255,.05)"/>
            <path d="M-10,95 C70,65 150,105 240,78 C300,58 340,90 370,72 L370,120 L-10,120 Z"
                  fill="rgba(255,255,255,.04)"/>
        </svg>

        <div class="bottom-content">
            <div class="welcome-text">
                <h2>Masuk Akun!</h2>
                <p>Silakan login untuk melanjutkan</p>
            </div>

            <?php if ($error): ?>
            <div class="error-msg">⚠️ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" class="form" id="login-form">
                <div class="input-wrap">
                    <span class="input-icon">👤</span>
                    <input type="text" name="username" id="username-field" placeholder="Username"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                           required autocomplete="username">
                </div>

                <div class="input-wrap">
                    <span class="input-icon">🔒</span>
                    <input type="password" name="password" id="password-field" placeholder="Password"
                           required autocomplete="current-password">
                    <!-- Toggle show/hide password -->
                    <button type="button" class="toggle-pw" id="toggle-pw" title="Tampilkan password" aria-label="Tampilkan password">
                        <!-- Eye icon (password hidden) -->
                        <svg id="icon-eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <!-- Eye-off icon (password visible) — hidden by default -->
                        <svg id="icon-eye-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                            <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                            <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                    </button>
                </div>

                <div class="submit-row">
                    <div class="submit-hint">Masuk untuk kelola sistem parkir</div>
                    <button type="submit" class="submit-btn" id="submit-btn" title="Login" disabled>
                        <!-- Arrow right icon -->
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </button>
                </div>
            </form>

            <div class="terms">
                <input type="checkbox" id="agree">
                <label for="agree">Pencet kotak di bawah ini untuk melanjutkan</label>
            </div>
        </div>

    </div>
</div>

<script>
    // ── Toggle Show/Hide Password ──
    const toggleBtn   = document.getElementById('toggle-pw');
    const pwField     = document.getElementById('password-field');
    const iconEye     = document.getElementById('icon-eye');
    const iconEyeOff  = document.getElementById('icon-eye-off');

    toggleBtn.addEventListener('click', function () {
        const isHidden = pwField.type === 'password';
        pwField.type = isHidden ? 'text' : 'password';

        iconEye.style.display    = isHidden ? 'none'  : 'block';
        iconEyeOff.style.display = isHidden ? 'block' : 'none';

        toggleBtn.classList.toggle('active', isHidden);
        toggleBtn.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Tampilkan password');
        toggleBtn.setAttribute('title',       isHidden ? 'Sembunyikan password' : 'Tampilkan password');
    });

    // ── Tombol Panah: Terang jika semua ketentuan terpenuhi ──
    // Ketentuan: username tidak kosong + password tidak kosong + checkbox dicentang
    const usernameField = document.getElementById('username-field');
    const agreeCheck    = document.getElementById('agree');
    const submitBtn     = document.getElementById('submit-btn');

    function checkFormReady() {
        const usernameOk = usernameField.value.trim().length > 0;
        const passwordOk = pwField.value.length > 0;
        const agreeOk    = agreeCheck.checked;

        const isReady = usernameOk && passwordOk && agreeOk;

        submitBtn.classList.toggle('ready', isReady);
        submitBtn.disabled = !isReady;
    }

    usernameField.addEventListener('input',  checkFormReady);

    // ── Enter di field Username → pindah ke Password ──
    usernameField.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            pwField.focus();
        }
    });
    pwField.addEventListener('input',        checkFormReady);
    agreeCheck.addEventListener('change',    checkFormReady);

    // Jalankan sekali saat load (misal jika ada autofill dari browser)
    checkFormReady();
</script>

</body>
</html>