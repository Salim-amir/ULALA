<?php

/**
 * proses_settings.php
 * Proses semua POST dari settings.php
 * Action: profil | password | tambah_kategori | hapus_kategori
 */
session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

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

        // TODO: UPDATE tabel users SET nama_lengkap=?, username=?, email=? WHERE id=?
        // $pdo->prepare("UPDATE users SET nama_lengkap=?,username=?,email=?,diperbarui_pada=NOW() WHERE id=?")
        //     ->execute([$nama_lengkap, $username, $email, $_SESSION['user_id']]);
        $pdo->prepare("UPDATE users SET nama_lengkap=?, username=?, email=? WHERE id=?")
            ->execute([$nama_lengkap, $username, $email, $_SESSION['user_id']]);

        // Update session
        $_SESSION['nama_lengkap'] = $nama_lengkap;
        $_SESSION['username']     = $username;
        $_SESSION['email']        = $email;

        header('Location: settings.php?tab=profil&success=profil');
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

        // TODO: Verifikasi password_lama dengan hash di DB
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $hash = $stmt->fetchColumn();
        if (!password_verify($password_lama, $hash)) {
            header('Location: settings.php?tab=password&error=wrong_password');
            exit;
        }
        $new_hash = password_hash($password_baru, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$new_hash, $_SESSION['user_id']]);

        header('Location: settings.php?tab=password&success=password');
        break;

    // ── Tambah Kategori (dengan SKU Prefix) ──────────────────────────
    case 'tambah_kategori':
        $nama_kategori = trim($_POST['nama_kategori'] ?? '');
        $sku_prefix    = strtoupper(trim($_POST['sku_prefix'] ?? ''));
        $deskripsi     = trim($_POST['deskripsi']     ?? '') ?: null;

        if (!$nama_kategori || !$sku_prefix) {
            header('Location: settings.php?tab=kategori&error=required');
            exit;
        }

        // Validasi: hanya huruf kapital, 2–6 karakter
        if (!preg_match('/^[A-Z]{2,6}$/', $sku_prefix)) {
            header('Location: settings.php?tab=kategori&error=prefix_invalid');
            exit;
        }

        try {
            // Cek apakah prefix sudah dipakai kategori lain
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
            error_log('[proses_settings/tambah_kategori] ' . $e->getMessage());
            header('Location: settings.php?tab=kategori&error=db_error');
        }
        break;

    // ── Edit SKU Prefix saja (tanpa ganti nama/deskripsi) ────────────
    case 'edit_prefix':
        $id         = (int)($_POST['id'] ?? 0);
        $sku_prefix = strtoupper(trim($_POST['sku_prefix'] ?? ''));

        if (!$id || !$sku_prefix) {
            header('Location: settings.php?tab=kategori&error=required');
            exit;
        }

        if (!preg_match('/^[A-Z]{2,6}$/', $sku_prefix)) {
            header('Location: settings.php?tab=kategori&error=prefix_invalid');
            exit;
        }

        try {
            // Cek apakah prefix sudah dipakai kategori LAIN
            $cek = $pdo->prepare("SELECT 1 FROM kategori WHERE sku_prefix = ? AND id <> ?");
            $cek->execute([$sku_prefix, $id]);
            if ($cek->fetchColumn()) {
                header('Location: settings.php?tab=kategori&error=prefix_taken');
                exit;
            }

            $pdo->prepare("UPDATE kategori SET sku_prefix = ? WHERE id = ?")
                ->execute([$sku_prefix, $id]);
            header('Location: settings.php?tab=kategori&success=kat_added');
        } catch (PDOException $e) {
            error_log('[proses_settings/edit_prefix] ' . $e->getMessage());
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
            // Cek apakah masih digunakan produk
            $cek = $pdo->prepare("SELECT COUNT(*) FROM produk WHERE kategori_id = ?");
            $cek->execute([$id]);
            if ((int)$cek->fetchColumn() > 0) {
                header('Location: settings.php?tab=kategori&error=kat_in_use');
                exit;
            }

            $pdo->prepare("DELETE FROM kategori WHERE id = ?")->execute([$id]);
            header('Location: settings.php?tab=kategori&success=kat_deleted');
        } catch (PDOException $e) {
            error_log('[proses_settings/hapus_kategori] ' . $e->getMessage());
            header('Location: settings.php?tab=kategori&error=db_error');
        }
        break;

    default:
        header('Location: settings.php');
}
exit;
