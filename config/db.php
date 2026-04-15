<?php
/**
 * config/db.php
 * Koneksi PostgreSQL via PDO (Railway compatible)
 */

var_dump(getenv("DATABASE_URL"));
die();

$url = getenv("DATABASE_URL");

// 🔥 Validasi kalau env belum ada
if (!$url) {
    error_log("DATABASE_URL tidak ditemukan di environment");
    http_response_code(500);
    die("Config database belum tersedia.");
}

// 🔥 Parse DATABASE_URL
$db = parse_url($url);

$host   = $db['host'] ?? '';
$port   = $db['port'] ?? '5432';
$user   = $db['user'] ?? '';
$pass   = $db['pass'] ?? '';
$dbname = isset($db['path']) ? ltrim($db['path'], '/') : '';

// 🔥 DSN PostgreSQL
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Set timezone
    $pdo->exec("SET TIME ZONE 'Asia/Jakarta'");

} catch (PDOException $e) {
    error_log('[DB ERROR] ' . $e->getMessage());
    http_response_code(503);
    die(json_encode([
        'error' => 'Koneksi database gagal. Silakan coba beberapa saat lagi.'
    ]));
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