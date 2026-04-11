<?php
/**
 * login.php
 * ─────────────────────────────────────────────────────────────────
 * Halaman otentikasi: Login & Registrasi.
 * Form login → POST ke proses_login.php
 * Form register → POST ke proses_register.php
 *
 * Untuk mengaktifkan sesi PHP setelah DB terhubung, uncomment
 * blok session di bagian atas dan redirect logic di bawah.
 * ─────────────────────────────────────────────────────────────────
 */

// session_start();
// if (isset($_SESSION['user_id'])) {
//     header('Location: dashboard.php');
//     exit;
// }

// ── Tentukan tab aktif: 'login' (default) atau 'register' ─────────
// Jika ada query string ?tab=register, langsung tampilkan form register.
$active_tab = (isset($_GET['tab']) && $_GET['tab'] === 'register') ? 'register' : 'login';

// ── Flash message dari proses_login.php / proses_register.php ─────
// Contoh: header('Location: login.php?error=invalid_credentials')
$error_map = [
    'invalid_credentials' => 'Username atau password salah.',
    'user_not_found'      => 'Akun tidak ditemukan.',
    'email_taken'         => 'Email sudah digunakan. Coba email lain.',
    'username_taken'      => 'Username sudah digunakan. Coba username lain.',
    'password_mismatch'   => 'Password dan konfirmasi password tidak cocok.',
    'register_failed'     => 'Pendaftaran gagal. Silakan coba lagi.',
    'session_expired'     => 'Sesi Anda telah berakhir. Silakan login kembali.',
];
$success_map = [
    'register_success' => 'Akun berhasil dibuat! Silakan login.',
    'logout_success'   => 'Anda telah berhasil logout.',
];

$error_msg   = isset($_GET['error'])   ? ($error_map[$_GET['error']]   ?? 'Terjadi kesalahan.') : '';
$success_msg = isset($_GET['success']) ? ($success_map[$_GET['success']] ?? '') : '';

// ── Pertahankan nilai input setelah error (UX) ─────────────────────
$old_username = htmlspecialchars($_GET['username'] ?? '');
$old_email    = htmlspecialchars($_GET['email']    ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login – Smart UMKM Assistant</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ── CSS Variables ───────────────────────────────────────────────── */
:root {
  --primary: #0d7a6a;
  --primary-dark: #095f52;
  --primary-light: #e6f4f1;
  --primary-mid: #1a9e8a;
  --accent: #f0a500;
  --danger: #e53e3e;
  --danger-light: #fff5f5;
  --success: #38a169;
  --success-light: #f0fff4;
  --bg: #f0f4f3;
  --surface: #ffffff;
  --surface-2: #f7faf9;
  --border: #e2ece9;
  --text-primary: #1a2e2a;
  --text-secondary: #4a6360;
  --text-muted: #8aa8a3;
  --shadow-lg: 0 8px 32px rgba(13,122,106,0.15), 0 4px 8px rgba(0,0,0,0.06);
  --radius-sm: 8px;
  --radius-md: 12px;
  --radius-xl: 24px;
  --font-main: 'Plus Jakarta Sans', sans-serif;
  --transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body { font-family: var(--font-main); background: var(--bg); color: var(--text-primary); min-height: 100vh; font-size: 14px; line-height: 1.6; -webkit-font-smoothing: antialiased; }

/* ── Auth Layout ─────────────────────────────────────────────────── */
.auth-wrap {
  min-height: 100vh;
  background: linear-gradient(135deg, #e8f5f2 0%, #f0f4f3 40%, #e6f4f1 100%);
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: 24px; position: relative; overflow: hidden;
}
.auth-wrap::before {
  content: ''; position: absolute; top: -120px; right: -120px;
  width: 400px; height: 400px; border-radius: 50%;
  background: radial-gradient(circle, rgba(13,122,106,0.08) 0%, transparent 70%);
  pointer-events: none;
}
.auth-wrap::after {
  content: ''; position: absolute; bottom: -80px; left: -80px;
  width: 300px; height: 300px; border-radius: 50%;
  background: radial-gradient(circle, rgba(240,165,0,0.06) 0%, transparent 70%);
  pointer-events: none;
}

/* ── Brand ───────────────────────────────────────────────────────── */
.auth-brand { text-align: center; margin-bottom: 28px; animation: fadeInDown 0.5s ease; }
.brand-icon {
  width: 56px; height: 56px; background: var(--primary); border-radius: var(--radius-md);
  display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;
  box-shadow: 0 8px 24px rgba(13,122,106,0.3);
}
.brand-icon i { color: white; font-size: 24px; }
.auth-brand h1 { font-size: 26px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.5px; }
.auth-brand p  { font-size: 13px; color: var(--text-secondary); margin-top: 4px; }

/* ── Card ────────────────────────────────────────────────────────── */
.auth-card {
  background: var(--surface); border-radius: var(--radius-xl);
  padding: 36px; width: 100%; max-width: 420px;
  box-shadow: var(--shadow-lg); border: 1px solid rgba(13,122,106,0.08);
  animation: fadeInUp 0.5s ease;
}
.auth-card h2      { font-size: 20px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px; }
.auth-card .auth-subtitle { font-size: 13px; color: var(--text-secondary); margin-bottom: 28px; }

/* ── Form elements ───────────────────────────────────────────────── */
.form-group  { margin-bottom: 18px; }
.form-label  { display: block; font-size: 11px; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 8px; }
.label-row   { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }

.input-wrap  { position: relative; }
.input-wrap i.input-icon {
  position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
  color: var(--text-muted); font-size: 14px; pointer-events: none;
}
.input-wrap input {
  width: 100%; padding: 12px 14px 12px 40px;
  border: 1.5px solid var(--border); border-radius: var(--radius-sm);
  background: var(--surface-2); font-family: var(--font-main); font-size: 14px;
  color: var(--text-primary); transition: var(--transition); outline: none;
}
.input-wrap input:focus { border-color: var(--primary); background: white; box-shadow: 0 0 0 3px rgba(13,122,106,0.1); }
.input-wrap input::placeholder { color: var(--text-muted); }
.input-wrap input.is-invalid { border-color: var(--danger); }

.input-toggle {
  position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
  background: none; border: none; cursor: pointer; color: var(--text-muted);
  padding: 4px; transition: var(--transition);
}
.input-toggle:hover { color: var(--primary); }

.auth-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.checkbox-wrap { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-secondary); cursor: pointer; }
.checkbox-wrap input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--primary); cursor: pointer; }

.link-text { font-size: 13px; color: var(--primary); text-decoration: none; font-weight: 600; transition: var(--transition); }
.link-text:hover { color: var(--primary-dark); }

.btn-primary {
  width: 100%; padding: 13px; background: var(--primary); color: white;
  border: none; border-radius: var(--radius-sm); font-family: var(--font-main);
  font-size: 14px; font-weight: 700; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  transition: var(--transition); letter-spacing: 0.3px;
}
.btn-primary:hover  { background: var(--primary-dark); box-shadow: 0 4px 16px rgba(13,122,106,0.3); transform: translateY(-1px); }
.btn-primary:active { transform: translateY(0); }

.auth-switch { text-align: center; margin-top: 20px; font-size: 13px; color: var(--text-secondary); }

/* ── Alert / flash ───────────────────────────────────────────────── */
.alert { padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
.alert-danger  { background: var(--danger-light);  color: var(--danger);  border: 1px solid #fed7d7; }
.alert-success { background: var(--success-light); color: var(--success); border: 1px solid #c6f6d5; }

/* ── Footer ──────────────────────────────────────────────────────── */
.auth-footer { text-align: center; margin-top: 24px; animation: fadeIn 0.8s ease; }
.auth-badges { display: flex; align-items: center; justify-content: center; gap: 20px; margin-bottom: 12px; }
.auth-badge  { display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
.auth-badge i { color: var(--primary); font-size: 13px; }
.auth-terms  { font-size: 11px; color: var(--text-muted); max-width: 340px; margin: 0 auto; line-height: 1.7; }

/* ── Animations ──────────────────────────────────────────────────── */
@keyframes fadeInUp   { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
@keyframes fadeInDown { from { opacity: 0; transform: translateY(-16px); } to { opacity: 1; transform: translateY(0); } }
@keyframes fadeIn     { from { opacity: 0; } to { opacity: 1; } }

/* ── Responsive ──────────────────────────────────────────────────── */
@media (max-width: 480px) {
  .auth-card { padding: 24px 20px; }
  .auth-badges { flex-direction: column; gap: 10px; }
}
</style>
</head>
<body>

<div class="auth-wrap">

  <div class="auth-brand">
    <div class="brand-icon"><i class="fa-solid fa-robot"></i></div>
    <h1>Smart UMKM</h1>
    <p>Asisten Cerdas untuk Pertumbuhan Bisnis Anda</p>
  </div>

  <!-- ─── FORM LOGIN ─────────────────────────────────────────────── -->
  <div id="login-panel" class="auth-card" <?= $active_tab === 'register' ? 'style="display:none;"' : '' ?>>
    <h2>Masuk ke akun Anda</h2>
    <p class="auth-subtitle">Kelola operasional bisnis Anda dengan kecerdasan buatan.</p>

    <?php if ($error_msg && $active_tab === 'login'): ?>
      <div class="alert alert-danger">
        <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error_msg) ?>
      </div>
    <?php endif; ?>

    <?php if ($success_msg): ?>
      <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success_msg) ?>
      </div>
    <?php endif; ?>

    <!--
      ACTION → proses_login.php
      Method POST – Field: username (email atau username), password, ingat_saya
    -->
    <form id="form-login" method="POST" action="proses_login.php">
      <input type="hidden" name="csrf_token" value="<?= /* TODO: generate_csrf_token() */ 'placeholder_token' ?>">

      <div class="form-group">
        <label class="form-label">Nama Pengguna atau Email</label>
        <div class="input-wrap">
          <i class="fa-regular fa-user input-icon"></i>
          <input
            type="text"
            id="login_identifier"
            name="username"
            placeholder="admin@smartumkm.id"
            value="<?= $old_username ?>"
            autocomplete="username"
            required>
        </div>
      </div>

      <div class="form-group">
        <label class="label-row">
          <span class="form-label" style="margin-bottom:0;">Kata Sandi</span>
          <a href="lupa_password.php" class="link-text" style="font-size:11px;text-transform:none;letter-spacing:0;">Lupa kata sandi?</a>
        </label>
        <div class="input-wrap">
          <i class="fa-solid fa-lock input-icon"></i>
          <input
            type="password"
            id="login_password"
            name="password"
            placeholder="••••••••"
            autocomplete="current-password"
            required>
          <button type="button" class="input-toggle" onclick="togglePass('login_password', this)" tabindex="-1">
            <i class="fa-regular fa-eye"></i>
          </button>
        </div>
      </div>

      <div class="auth-row">
        <label class="checkbox-wrap">
          <input type="checkbox" name="ingat_saya" value="1"> Ingat saya
        </label>
      </div>

      <button type="submit" class="btn-primary">
        Masuk <i class="fa-solid fa-arrow-right"></i>
      </button>
    </form>

    <p class="auth-switch">
      Belum punya akun?
      <a href="#" class="link-text" onclick="switchTab('register'); return false;">Daftar di sini</a>
    </p>
  </div>


  <!-- ─── FORM REGISTRASI ─────────────────────────────────────────── -->
  <div id="register-panel" class="auth-card" <?= $active_tab === 'login' ? 'style="display:none;"' : '' ?>>
    <h2>Buat Akun Baru</h2>
    <p class="auth-subtitle">Silakan lengkapi data di bawah ini untuk memulai.</p>

    <?php if ($error_msg && $active_tab === 'register'): ?>
      <div class="alert alert-danger">
        <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error_msg) ?>
      </div>
    <?php endif; ?>

    <!--
      ACTION → proses_register.php
      Method POST – Fields referensi tabel `users`:
        nama_lengkap, username, email, password, confirm_password
    -->
    <form id="form-register" method="POST" action="proses_register.php">
      <input type="hidden" name="csrf_token" value="<?= /* TODO: generate_csrf_token() */ 'placeholder_token' ?>">

      <div class="form-group">
        <label class="form-label">Nama Lengkap</label>
        <div class="input-wrap">
          <i class="fa-regular fa-user input-icon"></i>
          <input
            type="text"
            id="reg_nama_lengkap"
            name="nama_lengkap"
            placeholder="John Doe"
            autocomplete="name"
            required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Username</label>
        <div class="input-wrap">
          <i class="fa-solid fa-at input-icon"></i>
          <input
            type="text"
            id="reg_username"
            name="username"
            placeholder="john_doe"
            autocomplete="username"
            required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Email</label>
        <div class="input-wrap">
          <i class="fa-regular fa-envelope input-icon"></i>
          <input
            type="email"
            id="reg_email"
            name="email"
            placeholder="nama@email.com"
            value="<?= $old_email ?>"
            autocomplete="email"
            required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <div class="input-wrap">
          <i class="fa-solid fa-lock input-icon"></i>
          <input
            type="password"
            id="reg_password"
            name="password"
            placeholder="••••••••"
            autocomplete="new-password"
            minlength="8"
            required>
          <button type="button" class="input-toggle" onclick="togglePass('reg_password', this)" tabindex="-1">
            <i class="fa-regular fa-eye"></i>
          </button>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Konfirmasi Password</label>
        <div class="input-wrap">
          <i class="fa-solid fa-shield-halved input-icon"></i>
          <input
            type="password"
            id="reg_confirm_password"
            name="confirm_password"
            placeholder="••••••••"
            autocomplete="new-password"
            required>
          <button type="button" class="input-toggle" onclick="togglePass('reg_confirm_password', this)" tabindex="-1">
            <i class="fa-regular fa-eye"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-primary">
        Daftar Sekarang <i class="fa-solid fa-arrow-right"></i>
      </button>
    </form>

    <p class="auth-switch">
      Sudah punya akun?
      <a href="#" class="link-text" onclick="switchTab('login'); return false;">Login di sini</a>
    </p>
  </div>


  <!-- ─── FOOTER ─────────────────────────────────────────────────── -->
  <div class="auth-footer">
    <div class="auth-badges">
      <span class="auth-badge"><i class="fa-solid fa-shield-halved"></i> Enkripsi AES-256</span>
      <span class="auth-badge"><i class="fa-solid fa-cloud"></i> Cloud Storage</span>
    </div>
    <p class="auth-terms">
      Dengan mendaftar, Anda menyetujui Ketentuan Layanan dan Kebijakan Privasi Smart UMKM Assistant.
      Data Anda aman dan terlindungi sepenuhnya.
    </p>
  </div>

</div><!-- /auth-wrap -->

<script>
/* ── Toggle password visibility ──────────────────────────────────── */
function togglePass(inputId, btn) {
  const input = document.getElementById(inputId);
  if (!input) return;
  const icon = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'fa-regular fa-eye-slash';
  } else {
    input.type = 'password';
    icon.className = 'fa-regular fa-eye';
  }
}

/* ── Switch antara panel Login & Register tanpa reload ───────────── */
function switchTab(tab) {
  const loginPanel    = document.getElementById('login-panel');
  const registerPanel = document.getElementById('register-panel');

  if (tab === 'register') {
    loginPanel.style.display    = 'none';
    registerPanel.style.display = '';
    registerPanel.style.animation = 'fadeInUp 0.3s ease';
    history.replaceState(null, '', '?tab=register');
  } else {
    registerPanel.style.display = 'none';
    loginPanel.style.display    = '';
    loginPanel.style.animation  = 'fadeInUp 0.3s ease';
    history.replaceState(null, '', '?tab=login');
  }
}

/* ── Client-side validasi register (UX cepat, bukan pengganti server) */
document.getElementById('form-register')?.addEventListener('submit', function(e) {
  const pass    = document.getElementById('reg_password').value;
  const confirm = document.getElementById('reg_confirm_password').value;
  if (pass !== confirm) {
    e.preventDefault();
    alert('Password dan konfirmasi password tidak cocok!');
    document.getElementById('reg_confirm_password').classList.add('is-invalid');
    document.getElementById('reg_confirm_password').focus();
  }
});
</script>
</body>
</html>