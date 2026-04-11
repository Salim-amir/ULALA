<?php
/**
 * proses_penjualan.php
 * ─────────────────────────────────────────────────────────────────
 * Proses POST dari input_penjualan.php.
 * Menyimpan ke tabel penjualan + detail_penjualan dalam satu transaksi DB.
 * Trigger trg_kurangi_stok di DB akan otomatis mengurangi stok setelah
 * INSERT ke detail_penjualan.
 * ─────────────────────────────────────────────────────────────────
 */

session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: input_penjualan.php');
    exit;
}

require_once 'config/db.php';

// ── Ambil & sanitasi input ─────────────────────────────────────────
$metode_bayar = trim($_POST['metode_pembayaran'] ?? '');
$pajak_persen = max(0, min(100, (float)($_POST['pajak_persen'] ?? 11)));
$catatan      = trim($_POST['catatan'] ?? '');

$produk_ids    = $_POST['produk_id']    ?? [];
$jumlahs       = $_POST['jumlah']       ?? [];
$harga_satuans = $_POST['harga_satuan'] ?? [];

$metode_valid = ['QRIS', 'Transfer', 'Tunai'];

// ── Validasi dasar ────────────────────────────────────────────────
$errors = [];

if (!in_array($metode_bayar, $metode_valid)) {
    $errors[] = 'Metode pembayaran tidak valid.';
}

// Bersihkan baris kosong & validasi setiap item
$items = [];
foreach ($produk_ids as $i => $pid) {
    $pid    = (int)$pid;
    $jumlah = (int)($jumlahs[$i]       ?? 0);
    $harga  = (float)($harga_satuans[$i] ?? 0);

    if ($pid <= 0 || $jumlah <= 0 || $harga <= 0) continue; // skip baris kosong

    $items[] = [
        'produk_id'   => $pid,
        'jumlah'      => $jumlah,
        'harga_satuan'=> $harga,
        'subtotal_item' => $jumlah * $harga,
    ];
}

if (empty($items)) {
    header('Location: input_penjualan.php?error=empty_items');
    exit;
}

if (!empty($errors)) {
    header('Location: input_penjualan.php?error=' . urlencode($errors[0]));
    exit;
}

// ── Hitung total ──────────────────────────────────────────────────
$subtotal   = array_sum(array_column($items, 'subtotal_item'));
$pajak      = round($subtotal * ($pajak_persen / 100), 2);
$total_bayar = $subtotal + $pajak;

// ── Cek stok tersedia sebelum menyimpan ───────────────────────────
foreach ($items as $item) {
    $s = $pdo->prepare("SELECT nama_produk, stok_saat_ini FROM produk WHERE id = ?");
    $s->execute([$item['produk_id']]);
    $produk = $s->fetch();

    if (!$produk) {
        header('Location: input_penjualan.php?error=produk_not_found');
        exit;
    }
    if ((int)$produk['stok_saat_ini'] < $item['jumlah']) {
        $msg = urlencode('Stok ' . $produk['nama_produk'] . ' tidak mencukupi (tersisa: ' . $produk['stok_saat_ini'] . ')');
        header("Location: input_penjualan.php?error=$msg");
        exit;
    }
}

// ── Simpan dalam transaksi DB ─────────────────────────────────────
try {
    $pdo->beginTransaction();

    // 1. Generate nomor transaksi unik
    $nomor = generate_nomor_transaksi($pdo);

    // 2. INSERT ke tabel penjualan
    $stmt_pjl = $pdo->prepare("
        INSERT INTO penjualan
            (nomor_transaksi, metode_pembayaran, subtotal, pajak, total_bayar, dibuat_pada)
        VALUES (?, ?, ?, ?, ?, NOW())
        RETURNING id
    ");
    $stmt_pjl->execute([$nomor, $metode_bayar, $subtotal, $pajak, $total_bayar]);
    $penjualan_id = (int)$stmt_pjl->fetchColumn();

    // 3. INSERT detail_penjualan (trigger trg_kurangi_stok akan jalan otomatis)
    $stmt_det = $pdo->prepare("
        INSERT INTO detail_penjualan
            (penjualan_id, produk_id, jumlah, harga_satuan, subtotal_item)
        VALUES (?, ?, ?, ?, ?)
    ");
    foreach ($items as $item) {
        $stmt_det->execute([
            $penjualan_id,
            $item['produk_id'],
            $item['jumlah'],
            $item['harga_satuan'],
            $item['subtotal_item'],
        ]);
    }

    $pdo->commit();

    // Simpan nomor transaksi ke session agar bisa ditampilkan di flash
    $_SESSION['last_trx'] = $nomor;
    $_SESSION['last_total'] = $total_bayar;

    header('Location: input_penjualan.php?success=saved&trx=' . urlencode($nomor));
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('[proses_penjualan] ' . $e->getMessage());
    header('Location: input_penjualan.php?error=db_error');
    exit;
}