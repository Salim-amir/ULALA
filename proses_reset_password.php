<?php

/**
 * proses_reset_password.php
 * Proses POST dari lupa_password.php (step 2 — form password baru).
 * 1. Validasi token masih valid & belum dipakai
 * 2. Validasi password cocok & cukup kuat
 * 3. Hash password baru dengan bcrypt
 * 4. UPDATE tabel users
 * 5. Tandai token sebagai used
 * 6. Redirect ke lupa_password.php?success=reset
 */

// session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: lupa_password.php');
    exit;
}

require_once 'config/db.php';

$token              = trim($_POST['token']              ?? '');
$password_baru      = $_POST['password_baru']           ?? '';
$konfirmasi         = $_POST['konfirmasi_password']      ?? '';

// ── Validasi input dasar ──────────────────────────────────────────
if (!$token) {
    header('Location: lupa_password.php?error=token_invalid');
    exit;
}
if (strlen($password_baru) < 8) {
    header('Location: lupa_password.php?token=' . urlencode($token) . '&error=password_weak');
    exit;
}
if ($password_baru !== $konfirmasi) {
    header('Location: lupa_password.php?token=' . urlencode($token) . '&error=password_mismatch');
    exit;
}

try {
    // 1. Verifikasi token ulang (pastikan masih valid)
    $stmt = $pdo->prepare("
        SELECT email FROM password_resets
        WHERE token = ? AND used = FALSE AND expired_at > NOW()
    ");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if (!$reset) {
        header('Location: lupa_password.php?error=token_expired');
        exit;
    }
    
    $email = $reset['email']; // Ambil email asli dari hasil query

    // 2. Hash password baru
    $new_hash = password_hash($password_baru, PASSWORD_BCRYPT, ['cost' => 12]);

    // 3. UPDATE password (Gunakan password_hash dan hapus diperbarui_pada)
    $upd = $pdo->prepare("
        UPDATE users 
        SET password_hash = ? 
        WHERE email = ?
    ");
    $upd->execute([$new_hash, $email]);

    if ($upd->rowCount() === 0) {
        header('Location: lupa_password.php?token=' . urlencode($token) . '&error=reset_failed');
        exit;
    }

    // 4. Tandai token sudah dipakai
    $pdo->prepare("UPDATE password_resets SET used = TRUE WHERE token = ?")->execute([$token]);

    // 5. Redirect sukses
    header('Location: lupa_password.php?success=reset');
    exit;

} catch (PDOException $e) {
    // Debug: Jika masih gagal, aktifkan baris di bawah ini untuk melihat error aslinya:
    // die("Error DB: " . $e->getMessage());
    error_log('[proses_reset_password] ' . $e->getMessage());
    header('Location: lupa_password.php?token=' . urlencode($token) . '&error=db_error');
    exit;
}