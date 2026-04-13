<?php
/**
 * ekspor_csv_detail.php
 */
require_once 'config/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { die("ID Transaksi tidak valid."); }

// 1. Ambil data Header Transaksi
$stmt = $pdo->prepare("SELECT * FROM penjualan WHERE id = ?");
$stmt->execute([$id]);
$trx = $stmt->fetch();
if (!$trx) { die("Transaksi tidak ditemukan."); }

// 2. Ambil data Detail Barang
$items = $pdo->prepare("
    SELECT dp.jumlah, dp.harga_satuan, dp.subtotal_item, p.nama_produk, p.sku
    FROM detail_penjualan dp
    JOIN produk p ON p.id = dp.produk_id
    WHERE dp.penjualan_id = ?
");
$items->execute([$id]);
$detail_items = $items->fetchAll();

// 3. Set Header HTTP agar file didownload, bukan dibuka
$filename = "Struk_" . $trx['nomor_transaksi'] . "_" . date('Ymd') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// 4. Mulai menulis file CSV
$output = fopen('php://output', 'w');

// Baris 1: Header Info Toko / Judul
fputcsv($output, ['DETAIL TRANSAKSI - ULALA SMART ASSISTANT']);
fputcsv($output, []); // Baris kosong

// Baris 2: Info Transaksi
fputcsv($output, ['Nomor Transaksi', $trx['nomor_transaksi']]);
fputcsv($output, ['Tanggal', date('d-m-Y H:i', strtotime($trx['dibuat_pada']))]);
fputcsv($output, ['Metode Bayar', $trx['metode_pembayaran']]);
fputcsv($output, []); // Baris kosong

// Baris 3: Header Tabel Produk
fputcsv($output, ['No', 'Nama Produk', 'SKU', 'Harga Satuan', 'Jumlah', 'Subtotal']);

// Baris 4: Isi Tabel Produk
$no = 1;
foreach ($detail_items as $item) {
    fputcsv($output, [
        $no++,
        $item['nama_produk'],
        $item['sku'],
        (float)$item['harga_satuan'],
        (int)$item['jumlah'],
        (float)$item['subtotal_item']
    ]);
}

fputcsv($output, []); // Baris kosong

// Baris 5: Ringkasan Total
fputcsv($output, ['', '', '', '', 'Subtotal', (float)$trx['subtotal']]);
fputcsv($output, ['', '', '', '', 'Pajak', (float)$trx['pajak']]);
fputcsv($output, ['', '', '', '', 'Total Bayar', (float)$trx['total_bayar']]);

fclose($output);
exit;