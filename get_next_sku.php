<?php
/**
 * get_next_sku.php
 * ─────────────────────────────────────────────────────────────────
 * AJAX endpoint — dipanggil JS di tambah_produk.php via fetch().
 * Query: GET /get_next_sku.php?prefix=MKN
 * Response: JSON { "sku": "MKN-003" }
 *
 * Logika:
 *   1. Ambil semua SKU di tabel produk yang diawali prefix tersebut
 *   2. Cari nomor urut MAX + 1
 *   3. Format jadi PREFIX-NNN (3 digit, zero-padded)
 * ─────────────────────────────────────────────────────────────────
 */

session_start();
// if (!isset($_SESSION['user_id'])) { http_response_code(401); die('{}'); }

header('Content-Type: application/json; charset=utf-8');

require_once 'config/db.php';

$prefix = strtoupper(trim($_GET['prefix'] ?? ''));

// Validasi prefix: hanya huruf kapital 2–6 karakter
if (!preg_match('/^[A-Z]{2,6}$/', $prefix)) {
    echo json_encode(['error' => 'Prefix tidak valid', 'sku' => null]);
    exit;
}

try {
    /*
     * Cari SKU yang cocok dengan pola PREFIX-NNN (atau PREFIX-NNN...)
     * Contoh: MKN-001, MKN-002, MKN-10 → ambil angka terbesar
     */
    $stmt = $pdo->prepare("
        SELECT sku FROM produk
        WHERE sku ~ ?
        ORDER BY sku DESC
    ");
    // Pattern regex PostgreSQL: '^MKN-[0-9]+'
    $stmt->execute(['^' . $prefix . '-[0-9]+']);
    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $max_num = 0;
    foreach ($existing as $sku) {
        // Ekstrak angka setelah PREFIX-
        if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)$/', $sku, $m)) {
            $num = (int)$m[1];
            if ($num > $max_num) $max_num = $num;
        }
    }

    $next_num = $max_num + 1;
    // Format: PREFIX-001 (minimal 3 digit, tapi ikuti panjang jika sudah > 999)
    $next_sku = $prefix . '-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);

    // Pastikan SKU yang dihasilkan belum dipakai (race condition guard)
    $cek = $pdo->prepare("SELECT 1 FROM produk WHERE sku = ?");
    $cek->execute([$next_sku]);
    if ($cek->fetchColumn()) {
        // Coba increment sampai dapat yang kosong
        $attempts = 0;
        do {
            $next_num++;
            $next_sku = $prefix . '-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
            $cek->execute([$next_sku]);
            $attempts++;
        } while ($cek->fetchColumn() && $attempts < 100);
    }

    echo json_encode([
        'sku'     => $next_sku,
        'prefix'  => $prefix,
        'next_num'=> $next_num,
    ]);

} catch (PDOException $e) {
    error_log('[get_next_sku] ' . $e->getMessage());
    echo json_encode(['error' => 'DB error', 'sku' => $prefix . '-001']);
}