<?php
/**
 * config/db.php
 * ─────────────────────────────────────────────────────────────────
 * Koneksi tunggal ke PostgreSQL via PDO.
 * Include file ini di setiap halaman yang butuh DB:
 *   require_once __DIR__ . '/../config/db.php';
 * atau jika sudah di root:
 *   require_once 'config/db.php';
 *
 * Variabel yang tersedia setelah include:
 *   $pdo  – instance PDO siap pakai
 * ─────────────────────────────────────────────────────────────────
 */

// ── Konfigurasi koneksi — sesuaikan dengan environment Anda ───────
define('DB_HOST', getenv('PGHOST') ?: 'localhost');
define('DB_PORT', getenv('PGPORT') ?: '5432');
define('DB_NAME', getenv('PGDATABASE') ?: 'ulala_db');
define('DB_USER', getenv('PGUSER') ?: 'postgres');
define('DB_PASS', getenv('PGPASSWORD') ?: '');

// ── DSN ────────────────────────────────────────────────────────────
$dsn = sprintf(
    'pgsql:host=%s;port=%s;dbname=%s',
    DB_HOST, DB_PORT, DB_NAME
);

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    // Set timezone sesuai server
    $pdo->exec("SET TIME ZONE 'Asia/Jakarta'");
} catch (PDOException $e) {
    // Di produksi: log error, jangan tampilkan detail ke user
    error_log('[DB ERROR] ' . $e->getMessage());
    http_response_code(503);
    die(json_encode(['error' => 'Koneksi database gagal. Silakan coba beberapa saat lagi.']));
}

// ── Helper: generate nomor transaksi unik ──────────────────────────
function generate_nomor_transaksi(PDO $pdo): string {
    $prefix = '#TRX-';
    do {
        $nomor = $prefix . mt_rand(10000, 99999);
        $stmt  = $pdo->prepare("SELECT 1 FROM penjualan WHERE nomor_transaksi = ?");
        $stmt->execute([$nomor]);
    } while ($stmt->fetchColumn());
    return $nomor;
}

// ── Helper: format rupiah ──────────────────────────────────────────
function rp(float $n): string {
    return 'Rp ' . number_format($n, 0, ',', '.');
}