<?php
/**
 * ekspor_laporan.php
 * Ekspor histori transaksi ke file CSV.
 * Menerima POST dari laporan.php dengan filter: start_date, end_date, metode_pembayaran
 */
date_default_timezone_set('Asia/Jakarta');
session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: laporan.php'); exit; }

require_once 'config/db.php';

// ── Ambil parameter filter ─────────────────────────────────────────
$start_date = $_POST['start_date'] ?? date('Y-m-01');
$end_date   = $_POST['end_date']   ?? date('Y-m-d');
$metode     = trim($_POST['metode_pembayaran'] ?? '');

// Validasi tanggal
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) $start_date = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date))   $end_date   = date('Y-m-d');
if ($end_date < $start_date) [$start_date, $end_date] = [$end_date, $start_date];

$metode_valid = ['QRIS', 'Transfer', 'Cash'];

// ── Bangun query ──────────────────────────────────────────────────
$params = [
    $start_date . ' 00:00:00',
    $end_date   . ' 23:59:59',
];

$metode_sql = '';
if ($metode && in_array($metode, $metode_valid)) {
    $metode_sql = 'AND pj.metode_pembayaran = ?';
    $params[]   = $metode;
}

$stmt = $pdo->prepare("
    SELECT
        pj.nomor_transaksi,
        TO_CHAR(pj.dibuat_pada, 'DD/MM/YYYY HH24:MI:SS') AS tanggal,
        STRING_AGG(
            p.nama_produk || ' (x' || dp.jumlah || ' @ Rp ' || TO_CHAR(dp.harga_satuan, 'FM999,999,999') || ')',
            ' | '
        ) AS produk_detail,
        pj.subtotal,
        pj.pajak,
        pj.total_bayar,
        pj.metode_pembayaran
    FROM penjualan pj
    LEFT JOIN detail_penjualan dp ON dp.penjualan_id = pj.id
    LEFT JOIN produk p ON p.id = dp.produk_id
    WHERE pj.dibuat_pada BETWEEN ? AND ?
    $metode_sql
    GROUP BY pj.id
    ORDER BY pj.dibuat_pada DESC
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// ── Set header untuk download CSV ─────────────────────────────────
$filename = 'laporan_transaksi_' . $start_date . '_sd_' . $end_date . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM untuk UTF-8 agar Excel bisa baca karakter khusus
echo "\xEF\xBB\xBF";

// ── Buka output buffer sebagai CSV ────────────────────────────────
$output = fopen('php://output', 'w');

// Header baris pertama: info filter
fputcsv($output, ['LAPORAN TRANSAKSI ULALA SMART'], ';');
fputcsv($output, ['Periode:', $start_date . ' s/d ' . $end_date], ';');
fputcsv($output, ['Metode Bayar:', $metode ?: 'Semua'], ';');
fputcsv($output, ['Diekspor pada:', date('d/m/Y H:i:s')], ';');
fputcsv($output, [], ';'); // baris kosong

// Header kolom
fputcsv($output, [
    'No. Transaksi',
    'Tanggal & Waktu',
    'Detail Produk',
    'Subtotal (Rp)',
    'Pajak (Rp)',
    'Total Bayar (Rp)',
    'Metode Pembayaran',
], ';');

// Baris data
$grand_total = 0;
$grand_pajak = 0;
foreach ($rows as $row) {
    fputcsv($output, [
        $row['nomor_transaksi'],
        $row['tanggal'],
        $row['produk_detail'] ?? '',
        number_format((float)$row['subtotal'],   0, ',', '.'),
        number_format((float)$row['pajak'],      0, ',', '.'),
        number_format((float)$row['total_bayar'],0, ',', '.'),
        $row['metode_pembayaran'],
    ], ';');
    $grand_total += (float)$row['total_bayar'];
    $grand_pajak += (float)$row['pajak'];
}

// Baris total
fputcsv($output, [], ';');
fputcsv($output, [
    'TOTAL',
    '',
    count($rows) . ' transaksi',
    '',
    number_format($grand_pajak, 0, ',', '.'),
    number_format($grand_total, 0, ',', '.'),
    '',
], ';');

fclose($output);
exit;