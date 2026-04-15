<?php

/**
 * config/db.php
 * Koneksi PostgreSQL via PDO (Railway ready)
 */

$host   = 'monorail.proxy.rlwy.net';
$port   = '33529';
$user   = 'postgres';
$pass   = 'jtyIlbGTRNfDqOoVUWqkFaxsknbeoroe'; // <-- Jangan lupa ganti ini!
$dbname = 'railway';

// 🔥 DSN PostgreSQL
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // 🔥 Set timezone
    $pdo->exec("SET TIME ZONE 'Asia/Jakarta'");
} catch (PDOException $e) {
    die("ERROR ASLI: " . $e->getMessage());
}

// ── Helper: generate nomor transaksi unik ──────────────────────────
function generate_nomor_transaksi(PDO $pdo): string
{
    $prefix = '#TRX-';
    do {
        $nomor = $prefix . mt_rand(10000, 99999);
        $stmt  = $pdo->prepare("SELECT 1 FROM penjualan WHERE nomor_transaksi = ?");
        $stmt->execute([$nomor]);
    } while ($stmt->fetchColumn());
    return $nomor;
}

// ── Helper: format rupiah ──────────────────────────────────────────
function rp(float $n): string
{
    return 'Rp ' . number_format($n, 0, ',', '.');
}
