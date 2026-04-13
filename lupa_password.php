<?php

/**
 * lupa_password.php
 * ─────────────────────────────────────────────────────────────────
 * Alur lupa password 3 langkah:
 *
 *  Step 1 — user masukkan email  → sistem generate token, kirim link
 *  Step 2 — user klik link email → isi password baru + konfirmasi
 *  Step 3 — password berhasil diubah → redirect ke login
 *
 * Query string:
 *   (kosong)        → tampilkan form email  (step 1)
 *   ?token=xxx      → tampilkan form password baru (step 2)
 *   ?success=sent   → pesan "email terkirim"
 *   ?success=reset  → pesan "password berhasil diubah"
 *   ?error=xxx      → pesan error
 *
 * ─────────────────────────────────────────────────────────────────
 * CATATAN IMPLEMENTASI DB:
 * Tabel password_resets dibutuhkan. Jalankan SQL berikut sekali:
 *
 *   CREATE TABLE IF NOT EXISTS public.password_resets (
 *       id         SERIAL PRIMARY KEY,
 *       email      VARCHAR(255) NOT NULL,
 *       token      VARCHAR(64)  NOT NULL UNIQUE,
 *       expired_at TIMESTAMP    NOT NULL,
 *       used       BOOLEAN      DEFAULT FALSE,
 *       dibuat_pada TIMESTAMP   DEFAULT CURRENT_TIMESTAMP
 *   );
 *
 * Tabel users juga dibutuhkan dengan kolom: id, email, password.
 * Sesuaikan nama tabel/kolom dengan skema Anda.
 * ─────────────────────────────────────────────────────────────────
 */
// 1. Pastikan timezone sudah diset di paling atas file
date_default_timezone_set('Asia/Jakarta');

// 2. Ambil waktu sekarang dalam format database
$sekarang = date('Y-m-d H:i:s');

session_start();

require_once 'config/db.php';

// ── Tentukan step yang sedang aktif ───────────────────────────────
$token     = trim($_GET['token']   ?? '');
$success   = trim($_GET['success'] ?? '');
$error_key = trim($_GET['error']   ?? '');

$step = 1; // default: form email
if ($token)   $step = 2; // ada token → form reset password
if ($success) $step = 3; // berhasil

$error_msgs = [
    'email_not_found'  => 'Email tidak terdaftar di sistem kami.',
    'token_invalid'    => 'Link reset tidak valid. Minta link baru.',
    'token_expired'    => 'Link reset sudah kadaluarsa (berlaku 1 jam). Minta link baru.',
    'token_used'       => 'Link ini sudah pernah digunakan. Minta link baru jika perlu.',
    'password_mismatch' => 'Password baru dan konfirmasi tidak cocok.',
    'password_weak'    => 'Password minimal 8 karakter.',
    'reset_failed'     => 'Gagal menyimpan password baru. Coba lagi.',
    'db_error'         => 'Terjadi kesalahan sistem. Coba beberapa saat lagi.',
];

$success_msgs = [
    'sent'  => 'Link reset password telah dikirim ke email Anda. Cek inbox (dan folder spam).',
    'reset' => 'Password berhasil diubah! Silakan login dengan password baru Anda.',
];

$error_msg   = isset($error_msgs[$error_key])     ? $error_msgs[$error_key]     : '';
$success_msg = isset($success_msgs[$success])     ? $success_msgs[$success]     : '';

// ── Validasi token jika step 2 ─────────────────────────────────────
// ── Validasi token jika step 2 ─────────────────────────────────────
$token_data = null;
if ($step === 2 && $token) {
    // 1. Siapkan Query (Gunakan NOW() langsung di SQL)
    $stmt = $pdo->prepare("
        SELECT * FROM password_resets
        WHERE token = ? 
        AND used = FALSE 
        AND expired_at > NOW()
    ");

    // 2. Eksekusi HANYA dengan 1 parameter yaitu $token
    // Karena NOW() tidak butuh kiriman data dari PHP
    $stmt->execute([$token]);
    $token_data = $stmt->fetch();

    // 3. Jika token tidak ditemukan/kadaluarsa
    if (!$token_data) {
        header('Location: lupa_password.php?error=token_expired');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password – ULALA Smart Assistant</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #0d7a6a;
            --primary-dark: #095f52;
            --primary-light: #e6f4f1;
            --primary-mid: #1a9e8a;
            --danger: #e53e3e;
            --danger-light: #fff5f5;
            --success: #38a169;
            --success-light: #f0fff4;
            --warning: #dd6b20;
            --bg: #f0f4f3;
            --surface: #ffffff;
            --surface-2: #f7faf9;
            --border: #e2ece9;
            --text-primary: #1a2e2a;
            --text-secondary: #4a6360;
            --text-muted: #8aa8a3;
            --shadow-lg: 0 8px 32px rgba(13, 122, 106, 0.15), 0 4px 8px rgba(0, 0, 0, 0.06);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-xl: 24px;
            --font-main: 'Plus Jakarta Sans', sans-serif;
            --font-mono: 'DM Mono', monospace;
            --transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-main);
            background: var(--bg);
            min-height: 100vh;
            color: var(--text-primary);
            font-size: 14px;
            -webkit-font-smoothing: antialiased;
        }

        .auth-wrap {
            min-height: 100vh;
            background: linear-gradient(135deg, #e8f5f2 0%, #f0f4f3 40%, #e6f4f1 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
            overflow: hidden;
        }

        .auth-wrap::before {
            content: '';
            position: absolute;
            top: -120px;
            right: -120px;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(13, 122, 106, 0.08) 0%, transparent 70%);
            pointer-events: none;
        }

        .auth-wrap::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: -80px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(240, 165, 0, 0.06) 0%, transparent 70%);
            pointer-events: none;
        }

        .auth-brand {
            text-align: center;
            margin-bottom: 24px;
            animation: fadeInDown 0.4s ease;
        }

        .brand-icon {
            width: 52px;
            height: 52px;
            background: var(--primary);
            border-radius: var(--radius-md);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            box-shadow: 0 8px 24px rgba(13, 122, 106, 0.3);
        }

        .brand-icon i {
            color: white;
            font-size: 22px;
        }

        .auth-brand h1 {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .auth-brand p {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 3px;
        }

        .auth-card {
            background: var(--surface);
            border-radius: var(--radius-xl);
            padding: 36px;
            width: 100%;
            max-width: 420px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(13, 122, 106, 0.08);
            animation: fadeInUp 0.4s ease;
        }

        /* Step indicator */
        .steps {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            margin-bottom: 28px;
        }

        .step-dot {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 800;
            flex-shrink: 0;
            transition: var(--transition);
        }

        .step-dot.done {
            background: var(--primary);
            color: white;
        }

        .step-dot.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 0 0 4px var(--primary-light);
        }

        .step-dot.pending {
            background: var(--border);
            color: var(--text-muted);
        }

        .step-line {
            flex: 1;
            height: 2px;
            background: var(--border);
            max-width: 40px;
        }

        .step-line.done {
            background: var(--primary);
        }

        .step-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
            margin-top: 5px;
        }

        .steps-wrap {
            display: flex;
            gap: 0;
            align-items: flex-start;
            justify-content: center;
            margin-bottom: 28px;
        }

        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }

        .step-connector {
            width: 48px;
            height: 2px;
            background: var(--border);
            margin-top: 15px;
        }

        .step-connector.done {
            background: var(--primary);
        }

        .card-title {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .card-subtitle {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap i.input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 14px;
            pointer-events: none;
        }

        .input-wrap input {
            width: 100%;
            padding: 12px 14px 12px 40px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--surface-2);
            font-family: var(--font-main);
            font-size: 14px;
            color: var(--text-primary);
            transition: var(--transition);
            outline: none;
        }

        .input-wrap input:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(13, 122, 106, 0.1);
        }

        .input-wrap input::placeholder {
            color: var(--text-muted);
        }

        .input-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            padding: 4px;
            transition: var(--transition);
        }

        .input-toggle:hover {
            color: var(--primary);
        }

        /* Password strength bar */
        .strength-bar {
            height: 4px;
            border-radius: 2px;
            background: var(--border);
            margin-top: 8px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            border-radius: 2px;
            width: 0;
            transition: width 0.3s ease, background 0.3s ease;
        }

        .strength-text {
            font-size: 11px;
            margin-top: 4px;
            font-weight: 600;
        }

        .btn-primary {
            width: 100%;
            padding: 13px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-family: var(--font-main);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: var(--transition);
            letter-spacing: 0.3px;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            box-shadow: 0 4px 16px rgba(13, 122, 106, 0.3);
            transform: translateY(-1px);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.5;
        }

        .alert-danger {
            background: var(--danger-light);
            color: var(--danger);
            border: 1px solid #fed7d7;
        }

        .alert-success {
            background: var(--success-light);
            color: var(--success);
            border: 1px solid #c6f6d5;
        }

        .alert i {
            flex-shrink: 0;
            margin-top: 1px;
        }

        .back-link {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            justify-content: center;
            transition: var(--transition);
        }

        .back-link:hover {
            color: var(--primary-dark);
        }

        /* Success state */
        .success-icon-wrap {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: var(--success-light);
            border: 3px solid var(--success);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .success-icon-wrap i {
            color: var(--success);
            font-size: 28px;
        }

        /* Email preview box */
        .email-preview {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 14px 16px;
            margin: 16px 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .email-preview i {
            color: var(--primary);
            font-size: 20px;
            flex-shrink: 0;
        }

        .email-preview .ep-addr {
            font-weight: 700;
            font-size: 14px;
        }

        .email-preview .ep-note {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* Countdown timer */
        #resend-timer {
            font-size: 13px;
            color: var(--text-muted);
            text-align: center;
            margin-top: 14px;
        }

        #resend-btn {
            display: none;
            font-size: 13px;
            color: var(--primary);
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-family: var(--font-main);
            padding: 0;
            text-align: center;
            width: 100%;
            margin-top: 14px;
        }

        #resend-btn:hover {
            color: var(--primary-dark);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width:480px) {
            .auth-card {
                padding: 24px 18px;
            }
        }
    </style>
</head>

<body>

    <div class="auth-wrap">

        <!-- Brand -->
        <div class="auth-brand">
            <div class="brand-icon"><i class="fa-solid fa-robot"></i></div>
            <h1>ULALA Smart</h1>
            <p>Asisten Cerdas untuk Pertumbuhan Bisnis Anda</p>
        </div>

        <div class="auth-card">

            <!-- Step Indicator -->
            <div style="display:flex;align-items:flex-start;justify-content:center;gap:0;margin-bottom:28px;">
                <?php
                $steps_info = [
                    1 => ['label' => 'Email',     'icon' => 'fa-envelope'],
                    2 => ['label' => 'Reset',     'icon' => 'fa-key'],
                    3 => ['label' => 'Selesai',   'icon' => 'fa-circle-check'],
                ];
                $step_count = count($steps_info);
                $i = 0;
                foreach ($steps_info as $num => $info):
                    $i++;
                    $state = $num < $step ? 'done' : ($num === $step ? 'active' : 'pending');
                ?>
                    <div style="display:flex;flex-direction:column;align-items:center;gap:5px;">
                        <div style="
            width:34px;height:34px;border-radius:50%;
            display:flex;align-items:center;justify-content:center;
            font-size:<?= $state === 'done' ? '14' : '12' ?>px;font-weight:800;
            flex-shrink:0;transition:var(--transition);
            background:<?= in_array($state, ['done', 'active']) ? 'var(--primary)' : 'var(--border)' ?>;
            color:<?= in_array($state, ['done', 'active']) ? 'white' : 'var(--text-muted)' ?>;
            <?= $state === 'active' ? 'box-shadow:0 0 0 4px var(--primary-light);' : '' ?>">
                            <?php if ($state === 'done'): ?>
                                <i class="fa-solid fa-check" style="font-size:13px;"></i>
                            <?php else: ?>
                                <?= $num ?>
                            <?php endif; ?>
                        </div>
                        <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;
            color:<?= in_array($state, ['done', 'active']) ? 'var(--primary)' : 'var(--text-muted)' ?>;">
                            <?= $info['label'] ?>
                        </span>
                    </div>
                    <?php if ($i < $step_count): ?>
                        <div style="width:44px;height:2px;margin-top:17px;flex-shrink:0;
            background:<?= $num < $step ? 'var(--primary)' : 'var(--border)' ?>;"></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- ══════════════════════════════════════
         STEP 1: Form Masukkan Email
    ══════════════════════════════════════ -->
            <?php if ($step === 1): ?>

                <h2 class="card-title">Lupa Password?</h2>
                <p class="card-subtitle">
                    Masukkan email yang terdaftar. Kami akan mengirimkan link untuk mereset password Anda.
                </p>

                <?php if ($error_msg): ?>
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error_msg) ?>
                    </div>
                <?php endif; ?>

                <!--
        ACTION → proses_lupa_password.php
        Akan: cari user by email, generate token 64 char, simpan ke password_resets,
              kirim email berisi link ?token=xxx
      -->
                <form method="POST" action="proses_lupa_password.php" id="form-lupa">
                    <div class="form-group">
                        <label class="form-label">Alamat Email</label>
                        <div class="input-wrap">
                            <i class="fa-regular fa-envelope input-icon"></i>
                            <input type="email" name="email" id="email-input"
                                placeholder="Email yang terdaftar"
                                value="<?= htmlspecialchars($_GET['email'] ?? '') ?>"
                                autocomplete="email" required autofocus>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" id="btn-kirim">
                        <i class="fa-solid fa-paper-plane"></i> Kirim Link Reset
                    </button>
                </form>


                <!-- ══════════════════════════════════════
         STEP 2: Form Reset Password Baru
    ══════════════════════════════════════ -->
            <?php elseif ($step === 2 && $token_data): ?>

                <h2 class="card-title">Buat Password Baru</h2>
                <p class="card-subtitle">
                    Masukkan password baru untuk akun
                    <strong><?= htmlspecialchars($token_data['email']) ?></strong>.
                </p>

                <?php if ($error_msg): ?>
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error_msg) ?>
                    </div>
                <?php endif; ?>

                <!--
        ACTION → proses_reset_password.php
        Akan: verifikasi token masih valid, hash password baru,
              UPDATE users SET password=? WHERE email=token_data.email,
              UPDATE password_resets SET used=TRUE WHERE token=?
      -->
                <form method="POST" action="proses_reset_password.php" id="form-reset">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <div class="form-group">
                        <label class="form-label">Password Baru</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input type="password" name="password_baru" id="pass-baru"
                                placeholder="Minimal 8 karakter"
                                minlength="8" required autocomplete="new-password"
                                oninput="checkStrength(this.value)">
                            <button type="button" class="input-toggle" onclick="togglePass('pass-baru',this)">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                        <!-- Password strength indicator -->
                        <div class="strength-bar">
                            <div class="strength-fill" id="strength-fill"></div>
                        </div>
                        <div class="strength-text" id="strength-text" style="color:var(--text-muted);"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-shield-halved input-icon"></i>
                            <input type="password" name="konfirmasi_password" id="pass-konfirm"
                                placeholder="Ulangi password baru"
                                minlength="8" required autocomplete="new-password"
                                oninput="checkMatch()">
                            <button type="button" class="input-toggle" onclick="togglePass('pass-konfirm',this)">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                        <div id="match-msg" style="font-size:11px;margin-top:4px;font-weight:600;"></div>
                    </div>

                    <button type="submit" class="btn-primary" id="btn-reset" disabled>
                        <i class="fa-solid fa-key"></i> Simpan Password Baru
                    </button>
                </form>


                <!-- ══════════════════════════════════════
         STEP 3: Email Terkirim / Berhasil Reset
    ══════════════════════════════════════ -->
            <?php elseif ($step === 3): ?>

                <?php if ($success === 'sent'): ?>
                    <div style="text-align:center;">
                        <div style="width:72px;height:72px;border-radius:50%;background:var(--primary-light);
            border:3px solid var(--primary);display:flex;align-items:center;justify-content:center;
            margin:0 auto 18px;">
                            <i class="fa-solid fa-paper-plane" style="color:var(--primary);font-size:26px;"></i>
                        </div>
                        <h2 class="card-title" style="margin-bottom:10px;">Email Terkirim!</h2>
                        <p class="card-subtitle" style="margin-bottom:0;">
                            Link reset password telah dikirim. Cek inbox (dan folder <strong>spam/junk</strong>).
                        </p>

                        <?php $masked_email = $_GET['email'] ?? 'email Anda'; ?>
                        <div class="email-preview" style="margin-top:20px;">
                            <i class="fa-solid fa-envelope-circle-check"></i>
                            <div>
                                <div class="ep-addr"><?= htmlspecialchars($masked_email) ?></div>
                                <div class="ep-note">Link berlaku selama <strong>1 jam</strong></div>
                            </div>
                        </div>

                        <div id="resend-timer" style="margin-top:16px;">
                            Belum dapat email? Kirim ulang dalam <strong id="countdown">60</strong> detik.
                        </div>
                        <button id="resend-btn" onclick="kirimUlang()">
                            <i class="fa-solid fa-rotate"></i> Kirim Ulang Email
                        </button>
                    </div>

                <?php elseif ($success === 'reset'): ?>
                    <div style="text-align:center;">
                        <div style="width:72px;height:72px;border-radius:50%;background:var(--success-light);
            border:3px solid var(--success);display:flex;align-items:center;justify-content:center;
            margin:0 auto 18px;">
                            <i class="fa-solid fa-circle-check" style="color:var(--success);font-size:28px;"></i>
                        </div>
                        <h2 class="card-title" style="margin-bottom:10px;">Password Berhasil Diubah!</h2>
                        <p class="card-subtitle" style="margin-bottom:20px;">
                            Password Anda telah berhasil diperbarui. Silakan login dengan password baru.
                        </p>
                        <a href="login.php" class="btn-primary" style="text-decoration:none;">
                            <i class="fa-solid fa-right-to-bracket"></i> Masuk Sekarang
                        </a>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

            <!-- Tombol kembali (semua step kecuali success reset) -->
            <?php if (!($step === 3 && $success === 'reset')): ?>
                <a href="login.php" class="back-link">
                    <i class="fa-solid fa-arrow-left"></i> Kembali ke halaman login
                </a>
            <?php endif; ?>

        </div><!-- /auth-card -->
    </div><!-- /auth-wrap -->

    <script>
        /* ── Toggle show/hide password ─────────────────────────────────── */
        function togglePass(id, btn) {
            const el = document.getElementById(id);
            const icon = btn.querySelector('i');
            if (!el) return;
            if (el.type === 'password') {
                el.type = 'text';
                icon.className = 'fa-regular fa-eye-slash';
            } else {
                el.type = 'password';
                icon.className = 'fa-regular fa-eye';
            }
        }

        /* ── Password strength checker ─────────────────────────────────── */
        function checkStrength(val) {
            const fill = document.getElementById('strength-fill');
            const text = document.getElementById('strength-text');
            if (!fill || !text) return;

            let score = 0;
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const levels = [{
                    pct: '25%',
                    color: '#e53e3e',
                    label: 'Lemah'
                },
                {
                    pct: '50%',
                    color: '#dd6b20',
                    label: 'Cukup'
                },
                {
                    pct: '75%',
                    color: '#d69e2e',
                    label: 'Baik'
                },
                {
                    pct: '100%',
                    color: '#38a169',
                    label: 'Kuat'
                },
            ];
            const lvl = levels[Math.max(0, score - 1)] || levels[0];

            if (val.length === 0) {
                fill.style.width = '0';
                text.textContent = '';
            } else {
                fill.style.width = lvl.pct;
                fill.style.background = lvl.color;
                text.textContent = 'Kekuatan password: ' + lvl.label;
                text.style.color = lvl.color;
            }
            checkMatch();
        }

        /* ── Password match checker ────────────────────────────────────── */
        function checkMatch() {
            const p1 = document.getElementById('pass-baru')?.value || '';
            const p2 = document.getElementById('pass-konfirm')?.value || '';
            const msg = document.getElementById('match-msg');
            const btn = document.getElementById('btn-reset');
            if (!msg) return;

            if (!p2) {
                msg.textContent = '';
                if (btn) btn.disabled = true;
                return;
            }
            if (p1 === p2 && p1.length >= 8) {
                msg.textContent = '✓ Password cocok';
                msg.style.color = 'var(--success)';
                if (btn) btn.disabled = false;
            } else {
                msg.textContent = p1 !== p2 ? '✗ Password tidak cocok' : '✗ Minimal 8 karakter';
                msg.style.color = 'var(--danger)';
                if (btn) btn.disabled = true;
            }
        }

        /* ── Countdown & resend ─────────────────────────────────────────── */
        let countdown = 60;
        const timerEl = document.getElementById('countdown');
        const timerWrap = document.getElementById('resend-timer');
        const resendBtn = document.getElementById('resend-btn');

        if (timerEl) {
            const timer = setInterval(() => {
                countdown--;
                if (timerEl) timerEl.textContent = countdown;
                if (countdown <= 0) {
                    clearInterval(timer);
                    if (timerWrap) timerWrap.style.display = 'none';
                    if (resendBtn) resendBtn.style.display = 'block';
                }
            }, 1000);
        }

        function kirimUlang() {
            window.location.href = 'lupa_password.php';
        }

        /* ── Submit loading state ────────────────────────────────────────── */
        document.getElementById('form-lupa')?.addEventListener('submit', function() {
            const btn = document.getElementById('btn-kirim');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mengirim…';
            }
        });
        document.getElementById('form-reset')?.addEventListener('submit', function(e) {
            const p1 = document.getElementById('pass-baru')?.value || '';
            const p2 = document.getElementById('pass-konfirm')?.value || '';
            if (p1 !== p2) {
                e.preventDefault();
                return;
            }
            const btn = document.getElementById('btn-reset');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan…';
            }
        });
    </script>
</body>

</html>