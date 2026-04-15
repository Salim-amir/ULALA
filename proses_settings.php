<?php
/**
 * proses_settings.php
 * Proses semua POST dari settings.php
 * Action: profil | password | tambah_kategori | hapus_kategori
 */
session_start();

// Pastikan user sudah login dan user_id tersedia di session
if (!isset($_SESSION['user_id'])) { 
    header('Location: login.php'); 
    exit; 
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: settings.php');
    exit;
}

require_once 'config/db.php';

$action = trim($_POST['action'] ?? '');

switch ($action) {

    // ── Update Profil ─────────────────────────────────────────────
    case 'profil':
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $username     = trim($_POST['username']     ?? '');
        $email        = trim($_POST['email']        ?? '');

        if (!$nama_lengkap || !$username || !$email) {
            header('Location: settings.php?tab=profil&error=required');
            exit;
        }

        try {
            // PERBAIKAN: Gunakan $_SESSION['user_id'] (Integer) untuk WHERE id
            $sql = "UPDATE users SET nama_lengkap = ?, username = ?, email = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nama_lengkap, $username, $email, $_SESSION['user_id']]);

            // Update session agar tampilan di navbar/header langsung berubah
            $_SESSION['nama_lengkap'] = $nama_lengkap;
            $_SESSION['username']     = $username;
            $_SESSION['email']        = $email;

            header('Location: settings.php?tab=profil&success=profil');
        } catch (PDOException $e) {
            error_log('[proses_settings/profil] ' . $e->getMessage());
            header('Location: settings.php?tab=profil&error=db_error');
        }
        break;

    // ── Ubah Password ─────────────────────────────────────────────
    case 'password':
        $password_lama      = $_POST['password_lama']       ?? '';
        $password_baru      = $_POST['password_baru']       ?? '';
        $konfirmasi         = $_POST['konfirmasi_password']  ?? '';

        if (!$password_lama || !$password_baru || !$konfirmasi) {
            header('Location: settings.php?tab=password&error=required');
            exit;
        }
        
        if ($password_baru !== $konfirmasi) {
            header('Location: settings.php?tab=password&error=password_mismatch');
            exit;
        }

        try {
            // PERBAIKAN: Cari user berdasarkan id, bukan username
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $hash = $stmt->fetchColumn();

            if (!$hash || !password_verify($password_lama, $hash)) {
                header('Location: settings.php?tab=password&error=wrong_password');
                exit;
            }

            $new_hash = password_hash($password_baru, PASSWORD_BCRYPT);
            
            // PERBAIKAN: Update berdasarkan id
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
                ->execute([$new_hash, $_SESSION['user_id']]);

            header('Location: settings.php?tab=password&success=password');
        } catch (PDOException $e) {
            error_log('[proses_settings/password] ' . $e->getMessage());
            header('Location: settings.php?tab=password&error=db_error');
        }
        break;

    // ── Tambah Kategori ──────────────────────────────────────────
    case 'tambah_kategori':
        $nama_kategori = trim($_POST['nama_kategori'] ?? '');
        $sku_prefix    = strtoupper(trim($_POST['sku_prefix'] ?? ''));
        $deskripsi     = trim($_POST['deskripsi']     ?? '') ?: null;

        if (!$nama_kategori || !$sku_prefix) {
            header('Location: settings.php?tab=kategori&error=required');
            exit;
        }

        if (!preg_match('/^[A-Z]{2,6}$/', $sku_prefix)) {
            header('Location: settings.php?tab=kategori&error=prefix_invalid');
            exit;
        }

        try {
            $cek = $pdo->prepare("SELECT 1 FROM kategori WHERE sku_prefix = ?");
            $cek->execute([$sku_prefix]);
            if ($cek->fetchColumn()) {
                header('Location: settings.php?tab=kategori&error=prefix_taken');
                exit;
            }

            $pdo->prepare("INSERT INTO kategori (nama_kategori, deskripsi, sku_prefix) VALUES (?, ?, ?)")
                ->execute([$nama_kategori, $deskripsi, $sku_prefix]);
            header('Location: settings.php?tab=kategori&success=kat_added');
        } catch (PDOException $e) {
            header('Location: settings.php?tab=kategori&error=db_error');
        }
        break;

    // ── Hapus Kategori ────────────────────────────────────────────
    case 'hapus_kategori':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            header('Location: settings.php?tab=kategori');
            exit;
        }

        try {
            $cek = $pdo->prepare("SELECT COUNT(*) FROM produk WHERE kategori_id = ?");
            $cek->execute([$id]);
            if ((int)$cek->fetchColumn() > 0) {
                header('Location: settings.php?tab=kategori&error=kat_in_use');
                exit;
            }

            $pdo->prepare("DELETE FROM kategori WHERE id = ?")->execute([$id]);
            header('Location: settings.php?tab=kategori&success=kat_deleted');
        } catch (PDOException $e) {
            header('Location: settings.php?tab=kategori&error=db_error');
        }
        break;

    default:
        header('Location: settings.php');
}
exit;