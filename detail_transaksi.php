<?php

/**
 * detail_transaksi.php
 * ─────────────────────────────────────────────────────────────────
 * Halaman detail satu transaksi lengkap.
 * Akses: detail_transaksi.php?id=X
 *
 * Menampilkan:
 *  - Info header transaksi (nomor, tanggal, metode bayar)
 *  - Tabel semua item produk yang dibeli
 *  - Ringkasan: subtotal, pajak, total
 *  - Tombol: cetak / kembali ke laporan
 *
 * Tabel: penjualan, detail_penjualan, produk, kategori
 * ─────────────────────────────────────────────────────────────────
 */

// session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: login.php?error=session_expired'); exit; }

require_once 'config/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: laporan.php');
    exit;
}

$page_title  = 'Detail Transaksi';
$active_menu = 'laporan';
include 'layout/header.php';

// ── Ambil header transaksi ─────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT id, nomor_transaksi, metode_pembayaran,
           subtotal, pajak, total_bayar, dibuat_pada
    FROM penjualan
    WHERE id = ?
");
$stmt->execute([$id]);
$trx = $stmt->fetch();

if (!$trx) {
    header('Location: laporan.php?error=not_found');
    exit;
}

// ── Ambil semua item detail ────────────────────────────────────────
$items = $pdo->prepare("
    SELECT dp.id, dp.jumlah, dp.harga_satuan, dp.subtotal_item,
           p.nama_produk, p.sku,
           COALESCE(k.nama_kategori, '—') AS nama_kategori,
           p.satuan
    FROM detail_penjualan dp
    JOIN produk p ON p.id = dp.produk_id
    LEFT JOIN kategori k ON k.id = p.kategori_id
    WHERE dp.penjualan_id = ?
    ORDER BY dp.id ASC
");
$items->execute([$id]);
$detail_items = $items->fetchAll();

// ── Hitung persen pajak dari data nyata ───────────────────────────
$persen_pajak = $trx['subtotal'] > 0
    ? round(((float)$trx['pajak'] / (float)$trx['subtotal']) * 100, 1)
    : 0;

// ── Metode pembayaran → badge & icon ──────────────────────────────
function metode_info(string $m): array
{
    return match ($m) {
        'QRIS'     => ['badge-teal', 'fa-qrcode',          '#0d7a6a', '#e6f4f1'],
        'Transfer' => ['badge-blue', 'fa-building-columns', '#2b6cb0', '#ebf8ff'],
        'Cash'     => ['badge-green', 'fa-money-bills',      '#38a169', '#f0fff4'],
        default    => ['badge-teal', 'fa-credit-card',      '#0d7a6a', '#e6f4f1'],
    };
}

[$pill_class, $metode_icon, $metode_color, $metode_bg] = metode_info($trx['metode_pembayaran']);

?>

<style>
    /* ── Print styles ─────────────────────────────────────────────── */
    @media print {

        .topbar,
        .sidebar,
        .sidebar-overlay,
        .no-print {
            display: none !important;
        }

        .main-content {
            margin-left: 0 !important;
        }

        .page-body {
            padding: 0 !important;
        }

        .receipt-card {
            box-shadow: none !important;
            border: 1px solid #ccc !important;
        }

        .print-header {
            display: block !important;
        }

        body {
            background: white !important;
        }
    }

    .print-header {
        display: none;
    }

    /* ── Receipt card ─────────────────────────────────────────────── */
    .receipt-card {
        background: var(--surface);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        max-width: 760px;
        margin: 0 auto;
    }

    .receipt-top {
        background: linear-gradient(135deg, var(--primary-dark), var(--primary));
        padding: 28px 32px;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .receipt-top::before {
        content: '';
        position: absolute;
        top: -40px;
        right: -40px;
        width: 160px;
        height: 160px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.06);
    }

    .receipt-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, 0.15);
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.6px;
        text-transform: uppercase;
        margin-bottom: 14px;
    }

    .receipt-nomor {
        font-family: var(--font-mono);
        font-size: 22px;
        font-weight: 800;
        letter-spacing: 1px;
        margin-bottom: 6px;
    }

    .receipt-meta {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        font-size: 13px;
        color: rgba(255, 255, 255, 0.75);
    }

    .receipt-meta span {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    /* ── Metode pembayaran badge big ──────────────────────────────── */
    .metode-badge-big {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: var(--radius-sm);
        font-size: 13px;
        font-weight: 700;
    }

    /* ── Items tabel ──────────────────────────────────────────────── */
    .items-section {
        padding: 0;
    }

    .items-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 18px 24px 14px;
        border-bottom: 1px solid var(--border);
    }

    .items-header h3 {
        font-size: 14px;
        font-weight: 700;
    }

    .item-row {
        display: grid;
        grid-template-columns: 40px 1fr auto auto auto;
        gap: 12px;
        align-items: center;
        padding: 14px 24px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.04);
        transition: var(--transition);
    }

    .item-row:hover {
        background: var(--surface-2);
    }

    .item-row:last-child {
        border-bottom: none;
    }

    .item-no {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--primary-light);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 800;
        flex-shrink: 0;
    }

    .item-name {
        font-weight: 700;
        font-size: 13px;
    }

    .item-sku {
        font-size: 11px;
        color: var(--text-muted);
        font-family: var(--font-mono);
        margin-top: 2px;
    }

    .item-cat {
        font-size: 11px;
        color: var(--text-muted);
        margin-top: 2px;
    }

    .item-qty {
        text-align: center;
        font-size: 13px;
        color: var(--text-secondary);
        white-space: nowrap;
    }

    .item-qty strong {
        font-size: 15px;
        color: var(--text-primary);
    }

    .item-price {
        text-align: right;
        font-family: var(--font-mono);
        font-size: 13px;
        color: var(--text-secondary);
        white-space: nowrap;
    }

    .item-subtotal {
        text-align: right;
        font-family: var(--font-mono);
        font-size: 14px;
        font-weight: 800;
        color: var(--primary);
        white-space: nowrap;
    }

    /* ── Summary ───────────────────────────────────────────────────── */
    .summary-section {
        border-top: 1px solid var(--border);
        background: var(--surface-2);
        padding: 20px 24px;
    }

    .summary-line {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 7px 0;
        font-size: 13px;
    }

    .summary-line:not(:last-child) {
        border-bottom: 1px dashed var(--border);
    }

    .summary-line .lbl {
        color: var(--text-secondary);
    }

    .summary-line .val {
        font-family: var(--font-mono);
        font-weight: 700;
    }

    .total-line {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 0 2px;
    }

    .total-line .lbl {
        font-size: 15px;
        font-weight: 800;
    }

    .total-line .val {
        font-family: var(--font-mono);
        font-size: 22px;
        font-weight: 800;
        color: var(--primary);
    }

    /* ── Footer bar ─────────────────────────────────────────────────── */
    .receipt-footer {
        padding: 16px 24px;
        border-top: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        background: var(--surface);
    }

    /* Dashed divider (mirip struk belanja) */
    .dashed-divider {
        border: none;
        border-top: 2px dashed var(--border);
        margin: 4px 0;
    }

    @media (max-width: 600px) {
        .receipt-top {
            padding: 20px;
        }

        .item-row {
            grid-template-columns: 32px 1fr auto;
            gap: 8px;
        }

        .item-price {
            display: none;
        }

        .receipt-meta {
            gap: 10px;
        }
    }
</style>

<div class="page-content">

    <!-- Breadcrumb & Actions -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;"
        class="no-print">
        <div style="display:flex;align-items:center;gap:10px;">
            <a href="laporan.php" class="btn-secondary" style="width:auto;">
                <i class="fa-solid fa-arrow-left"></i> Laporan
            </a>
            <span style="color:var(--text-muted);">/</span>
            <span style="font-size:14px;font-weight:700;">Detail Transaksi</span>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="ekspor_csv_detail.php?id=<?= $trx['id'] ?>" class="btn-secondary" style="background: #1a9e8a; color: white; border: none;">
                <i class="fa-solid fa-file-csv"></i> Ekspor CSV
            </a>
            <button onclick="window.print()" class="btn-secondary">
                <i class="fa-solid fa-print"></i> Cetak
            </button>
        </div>
    </div>

    <!-- Print header (only visible when printing) -->
    <div class="print-header" style="text-align:center;margin-bottom:20px;">
        <h2 style="font-size:18px;font-weight:800;">ULALA Smart Assistant</h2>
        <p style="font-size:12px;color:#666;">Struk Transaksi</p>
    </div>

    <!-- ── RECEIPT CARD ──────────────────────────────────────────── -->
    <div class="receipt-card">

        <!-- Header gradient -->
        <div class="receipt-top">
            <div class="receipt-badge">
                <i class="fa-solid fa-receipt" style="font-size:10px;"></i>
                Transaksi
            </div>
            <div class="receipt-nomor"><?= htmlspecialchars($trx['nomor_transaksi']) ?></div>
            <div class="receipt-meta">
                <span>
                    <i class="fa-regular fa-calendar"></i>
                    <?= date('d F Y', strtotime($trx['dibuat_pada'])) ?>
                </span>
                <span>
                    <i class="fa-regular fa-clock"></i>
                    <?= date('H:i', strtotime($trx['dibuat_pada'])) ?> WIB
                </span>
                <span>
                    <i class="fa-solid fa-hashtag" style="font-size:10px;"></i>
                    ID: <?= $trx['id'] ?>
                </span>
            </div>
        </div>

        <!-- Metode Pembayaran (info strip) -->
        <div style="padding:14px 24px;background:<?= $metode_bg ?>;
                border-bottom:1px solid var(--border);
                display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
            <div style="display:flex;align-items:center;gap:8px;">
                <i class="fa-solid <?= $metode_icon ?>" style="color:<?= $metode_color ?>;font-size:16px;"></i>
                <span style="font-size:13px;font-weight:700;color:<?= $metode_color ?>;">
                    Dibayar dengan <?= htmlspecialchars($trx['metode_pembayaran']) ?>
                </span>
            </div>
            <span style="font-size:11px;color:var(--text-muted);">
                <?= count($detail_items) ?> item produk
            </span>
        </div>

        <!-- ── Item List ────────────────────────────────────────────── -->
        <div class="items-section">
            <div class="items-header">
                <div class="card-icon ai"><i class="fa-solid fa-list-check"></i></div>
                <h3>Detail Produk yang Dibeli</h3>
            </div>

            <?php if (empty($detail_items)): ?>
                <div style="padding:32px;text-align:center;color:var(--text-muted);">
                    <i class="fa-solid fa-box-open" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                    Tidak ada detail item untuk transaksi ini.
                </div>
            <?php else: ?>
                <?php foreach ($detail_items as $i => $item): ?>
                    <div class="item-row">
                        <div class="item-no"><?= $i + 1 ?></div>
                        <div>
                            <div class="item-name"><?= htmlspecialchars($item['nama_produk']) ?></div>
                            <div class="item-sku"><?= htmlspecialchars($item['sku'] ?? '—') ?></div>
                            <div class="item-cat"><?= htmlspecialchars($item['nama_kategori']) ?></div>
                        </div>
                        <div class="item-qty">
                            <strong><?= (int)$item['jumlah'] ?></strong>
                            <span style="font-size:11px;"> <?= htmlspecialchars($item['satuan']) ?></span>
                        </div>
                        <div class="item-price">
                            <?= rp((float)$item['harga_satuan']) ?><br>
                            <span style="font-size:11px;color:var(--text-muted);">/<?= htmlspecialchars($item['satuan']) ?></span>
                        </div>
                        <div class="item-subtotal"><?= rp((float)$item['subtotal_item']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- ── Summary ──────────────────────────────────────────────── -->
        <div class="summary-section">
            <hr class="dashed-divider" style="margin-bottom:12px;">

            <div class="summary-line">
                <span class="lbl">Subtotal (<?= count($detail_items) ?> item)</span>
                <span class="val"><?= rp((float)$trx['subtotal']) ?></span>
            </div>
            <div class="summary-line">
                <span class="lbl">
                    Pajak
                    <?php if ($persen_pajak > 0): ?>
                        <span style="font-size:11px;background:var(--border);padding:1px 6px;border-radius:4px;
                         font-family:var(--font-mono);margin-left:4px;"><?= $persen_pajak ?>%</span>
                    <?php endif; ?>
                </span>
                <span class="val"><?= rp((float)$trx['pajak']) ?></span>
            </div>

            <hr class="dashed-divider" style="margin:8px 0;">

            <div class="total-line">
                <span class="lbl">Total Bayar</span>
                <span class="val"><?= rp((float)$trx['total_bayar']) ?></span>
            </div>
        </div>

        <!-- ── Footer ───────────────────────────────────────────────── -->
        <div class="receipt-footer">
            <div style="font-size:12px;color:var(--text-muted);">
                <i class="fa-solid fa-shield-halved" style="color:var(--primary);margin-right:5px;"></i>
                Transaksi diproses oleh ULALA Smart Assistant
            </div>
            <div style="display:flex;gap:8px;align-items:center;" class="no-print">
                <a href="laporan.php" class="btn-secondary" style="width:auto;font-size:12px;">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </a>
                <button onclick="window.print()" class="btn-accent" style="font-size:12px;">
                    <i class="fa-solid fa-print"></i> Cetak Struk
                </button>
            </div>
        </div>

    </div><!-- /receipt-card -->

</div><!-- /page-content -->

<?php include 'layout/footer.php'; ?>