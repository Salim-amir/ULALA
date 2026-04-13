<?php

/**
 * proses_lupa_password.php
 * ─────────────────────────────────────────────────────────────────
 * Proses POST dari lupa_password.php (step 1).
 * 1. Cari user berdasarkan email
 * 2. Generate secure token
 * 3. Simpan ke tabel password_resets (berlaku 1 jam)
 * 4. Kirim email berisi link reset
 * 5. Redirect ke lupa_password.php?success=sent
 *
 * SETUP EMAIL: File ini menggunakan PHP mail() bawaan.
 * Untuk produksi, sangat disarankan pakai PHPMailer + SMTP:
 *   composer require phpmailer/phpmailer
 * ─────────────────────────────────────────────────────────────────
 */

// session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: lupa_password.php');
    exit;
}

require_once 'config/db.php';

$email = strtolower(trim($_POST['email'] ?? ''));

// Validasi format email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: lupa_password.php?error=email_not_found&email=' . urlencode($email));
    exit;
}

try {
    // // ── 1. Cek apakah email terdaftar ─────────────────────────────
    //   TODO: sesuaikan nama tabel & kolom dengan skema users Anda
    //   Jika belum ada tabel users, buat dulu atau sesuaikan query.

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) {
        header('Location: lupa_password.php?error=email_not_found&email=' . urlencode($email));
        exit;
    }


    // ── 2. Generate token kriptografis (64 hex char = 32 bytes) ──
    $token     = bin2hex(random_bytes(32));
    $expired_at = date('Y-m-d H:i:s', strtotime('+10 hour'));

    // ── 3. Simpan token ke DB ─────────────────────────────────────
    // Hapus token lama untuk email ini dulu (bersih)

    $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

    $pdo->prepare("
         INSERT INTO password_resets (email, token, expired_at)
          VALUES (?, ?, ?)
     ")->execute([$email, $token, $expired_at]);


    // ── 4. Kirim email ────────────────────────────────────────────
    $app_url   = rtrim(
        (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
            . dirname($_SERVER['PHP_SELF']),
        '/'
    );
    $reset_link = $app_url . '/lupa_password.php?token=' . $token;

    $subject = 'Reset Password – ULALA Smart Assistant';
    $body    = buildEmailBody($email, $reset_link, $expired_at);
    $headers = implode("\r\n", [
        'From: ULALA Smart Assistant <noreply@' . $_SERVER['HTTP_HOST'] . '>',
        'Reply-To: noreply@' . $_SERVER['HTTP_HOST'],
        'Content-Type: text/html; charset=UTF-8',
        'X-Mailer: PHP/' . PHP_VERSION,
    ]);


    //  * Aktifkan setelah DB dan konfigurasi email siap:
    mail($email, $subject, $body, $headers);

    // Untuk pengembangan: log link ke file agar bisa dicoba tanpa SMTP
    error_log('[RESET LINK] email=' . $email . ' | link=' . $reset_link);

    header('Location: lupa_password.php?success=sent&email=' . urlencode($email));
    exit;
} catch (PDOException $e) {
    error_log('[proses_lupa_password] ' . $e->getMessage());
    header('Location: lupa_password.php?error=db_error&email=' . urlencode($email));
    exit;
}

// ── Helper: bangun isi email HTML ─────────────────────────────────
function buildEmailBody(string $email, string $link, string $expired_at): string
{
    $expired_fmt = date('d M Y, H:i', strtotime($expired_at)) . ' WIB';
    return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"></head>
<body style="font-family:'Segoe UI',sans-serif;background:#f0f4f3;margin:0;padding:24px;">
  <div style="max-width:480px;margin:0 auto;background:white;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
    <div style="background:#0d7a6a;padding:28px 32px;text-align:center;">
      <h1 style="color:white;margin:0;font-size:22px;font-weight:800;">ULALA Smart Assistant</h1>
      <p style="color:rgba(255,255,255,0.8);margin:6px 0 0;font-size:13px;">Reset Password</p>
    </div>
    <div style="padding:32px;">
      <p style="color:#1a2e2a;font-size:15px;font-weight:600;margin:0 0 12px;">Halo!</p>
      <p style="color:#4a6360;font-size:14px;line-height:1.7;margin:0 0 20px;">
        Kami menerima permintaan reset password untuk akun dengan email
        <strong style="color:#0d7a6a;">{$email}</strong>.
        Klik tombol di bawah untuk membuat password baru.
      </p>
      <div style="text-align:center;margin:28px 0;">
        <a href="{$link}"
           style="background:#0d7a6a;color:white;padding:13px 32px;border-radius:8px;
                  text-decoration:none;font-weight:700;font-size:15px;display:inline-block;">
          Reset Password Saya
        </a>
      </div>
      <p style="color:#8aa8a3;font-size:12px;line-height:1.6;margin:0 0 12px;">
        Link ini hanya berlaku hingga <strong>{$expired_fmt}</strong>.<br>
        Jika Anda tidak merasa meminta reset password, abaikan email ini.
      </p>
      <div style="background:#f7faf9;border-radius:8px;padding:12px 14px;margin-top:16px;">
        <p style="font-size:11px;color:#8aa8a3;margin:0 0 4px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">
          Atau salin link berikut:
        </p>
        <p style="font-size:12px;color:#0d7a6a;margin:0;word-break:break-all;">{$link}</p>
      </div>
    </div>
    <div style="padding:16px 32px;background:#f7faf9;border-top:1px solid #e2ece9;text-align:center;">
      <p style="font-size:11px;color:#8aa8a3;margin:0;">
        © <?= date('Y') ?> ULALA Smart Assistant. Email ini dikirim otomatis, jangan dibalas.
      </p>
    </div>
  </div>
</body>
</html>
HTML;
}
