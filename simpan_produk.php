<?php
/**
 * simpan_produk.php
 * Proses POST untuk tambah (action=tambah) dan edit (action=edit) produk.
 */
session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: kelola_produk.php'); exit; }

require_once 'config/db.php';

$action        = $_POST['action'] ?? '';
$id            = (int)($_POST['id'] ?? 0);
$sku           = trim($_POST['sku']           ?? '');
$nama_produk   = trim($_POST['nama_produk']   ?? '');
$kategori_id   = (int)($_POST['kategori_id']  ?? 0);
$harga_jual    = (float)($_POST['harga_jual'] ?? 0);
$stok_saat_ini = max(0, (int)($_POST['stok_saat_ini'] ?? 0));
$stok_minimum  = max(0, (int)($_POST['stok_minimum']  ?? 5));
$satuan        = trim($_POST['satuan']        ?? 'pcs');

// Validasi wajib
if (!$sku || !$nama_produk || !$kategori_id || $harga_jual <= 0) {
    $redirect = $action === 'edit' ? "edit_produk.php?id=$id" : 'tambah_produk.php';
    header("Location: $redirect?error=required");
    exit;
}

try {
    if ($action === 'tambah') {
        // Cek SKU unik
        $chk = $pdo->prepare("SELECT 1 FROM produk WHERE sku = ?");
        $chk->execute([$sku]);
        if ($chk->fetchColumn()) {
            header("Location: tambah_produk.php?error=sku_taken&sku=".urlencode($sku)."&nama_produk=".urlencode($nama_produk));
            exit;
        }

        $pdo->prepare("
            INSERT INTO produk (sku, kategori_id, nama_produk, harga_jual, stok_saat_ini, stok_minimum, satuan, diperbarui_pada)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ")->execute([$sku, $kategori_id, $nama_produk, $harga_jual, $stok_saat_ini, $stok_minimum, $satuan]);

        header('Location: kelola_produk.php?success=added');

    } elseif ($action === 'edit' && $id > 0) {
        // Cek SKU unik (boleh sama dengan milik sendiri)
        $chk = $pdo->prepare("SELECT 1 FROM produk WHERE sku = ? AND id <> ?");
        $chk->execute([$sku, $id]);
        if ($chk->fetchColumn()) {
            header("Location: edit_produk.php?id=$id&error=sku_taken");
            exit;
        }

        $pdo->prepare("
            UPDATE produk
            SET sku = ?, kategori_id = ?, nama_produk = ?, harga_jual = ?,
                stok_saat_ini = ?, stok_minimum = ?, satuan = ?, diperbarui_pada = NOW()
            WHERE id = ?
        ")->execute([$sku, $kategori_id, $nama_produk, $harga_jual, $stok_saat_ini, $stok_minimum, $satuan, $id]);

        header('Location: kelola_produk.php?success=updated');

    } else {
        header('Location: kelola_produk.php');
    }
    exit;

} catch (PDOException $e) {
    error_log('[simpan_produk] ' . $e->getMessage());
    $redirect = $action === 'edit' ? "edit_produk.php?id=$id" : 'tambah_produk.php';
    header("Location: $redirect?error=db_error");
    exit;
}