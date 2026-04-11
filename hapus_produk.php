<?php
/**
 * hapus_produk.php
 * CRUD bagian DELETE — cek FK sebelum hapus, hanya terima POST
 */
session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: kelola_produk.php'); exit; }

require_once 'config/db.php';

$id = (int)($_POST['id'] ?? 0);
if (!$id) { header('Location: kelola_produk.php'); exit; }

try {
    // Cek apakah produk masih terkait detail_penjualan
    $cek = $pdo->prepare("SELECT COUNT(*) FROM detail_penjualan WHERE produk_id = ?");
    $cek->execute([$id]);
    if ((int)$cek->fetchColumn() > 0) {
        header('Location: kelola_produk.php?error=delete_fail');
        exit;
    }

    // Cek apakah produk masih ada di ai_insights
    $cek2 = $pdo->prepare("SELECT COUNT(*) FROM ai_insights WHERE produk_id = ?");
    $cek2->execute([$id]);
    if ((int)$cek2->fetchColumn() > 0) {
        // Hapus insights dulu
        $pdo->prepare("DELETE FROM ai_insights WHERE produk_id = ?")->execute([$id]);
    }

    $del = $pdo->prepare("DELETE FROM produk WHERE id = ?");
    $del->execute([$id]);

    header('Location: kelola_produk.php?success=deleted');

} catch (PDOException $e) {
    error_log('[hapus_produk] ' . $e->getMessage());
    header('Location: kelola_produk.php?error=db_error');
}
exit;