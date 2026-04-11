<?php

/**
 * laporan.php
 * ─────────────────────────────────────────────────────────────────
 * Halaman laporan histori transaksi dengan filter tanggal,
 * metode pembayaran, ringkasan statistik, dan ekspor.
 *
 * Referensi tabel DB:
 *   penjualan       : id, nomor_transaksi, metode_pembayaran,
 *                     subtotal, pajak, total_bayar, dibuat_pada
 *   detail_penjualan: penjualan_id, produk_id, jumlah,
 *                     harga_satuan, subtotal_item
 *   produk          : id, nama_produk
 * ─────────────────────────────────────────────────────────────────
 */
date_default_timezone_set('Asia/Jakarta');

$page_title  = 'Laporan Transaksi';
$active_menu = 'laporan';
include 'layout/header.php';

// ── Parameter filter dari GET ──────────────────────────────────────
$start_date   = $_GET['start_date']          ?? date('Y-m-01');         // Awal bulan ini
$end_date     = $_GET['end_date']            ?? date('Y-m-d');           // Hari ini
$filter_metode = trim($_GET['metode_pembayaran'] ?? '');
$page          = max(1, (int)($_GET['page']  ?? 1));
$per_page      = 10;
$offset        = ($page - 1) * $per_page;

// Validasi format tanggal
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) $start_date = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date))   $end_date   = date('Y-m-d');
if ($end_date < $start_date) [$start_date, $end_date] = [$end_date, $start_date];

// Metode pembayaran yang valid
$metode_valid = ['QRIS', 'Transfer', 'Tunai'];

// ── TODO: Query DB ─────────────────────────────────────────────────
require_once 'config/db.php';

$params = [
  ':start' => $start_date . ' 00:00:00',
  ':end'   => $end_date   . ' 23:59:59',
];
$metode_filter = '';
if ($filter_metode && in_array($filter_metode, $metode_valid)) {
  $metode_filter      = "AND pj.metode_pembayaran = :metode";
  $params[':metode']  = $filter_metode;
}

// Statistik ringkasan
$stmt_sum = $pdo->prepare("
    SELECT
        COALESCE(SUM(pj.total_bayar), 0)   AS total_pendapatan,
        COUNT(pj.id)                        AS jumlah_transaksi,
        COALESCE(AVG(pj.total_bayar), 0)   AS rata_rata,
        COALESCE(SUM(pj.pajak), 0)         AS total_pajak
    FROM penjualan pj
    WHERE pj.dibuat_pada BETWEEN :start AND :end
    $metode_filter
");
$stmt_sum->execute($params);
$summary = $stmt_sum->fetch(PDO::FETCH_ASSOC);

// Total rows untuk pagination
$stmt_count = $pdo->prepare("
    SELECT COUNT(pj.id)
    FROM penjualan pj
    WHERE pj.dibuat_pada BETWEEN :start AND :end
    $metode_filter
");
$stmt_count->execute($params);
$total_rows  = (int)$stmt_count->fetchColumn();
$total_pages = (int)ceil($total_rows / $per_page);

// Data tabel
$stmt_data = $pdo->prepare("
    SELECT
        pj.id, pj.nomor_transaksi, pj.metode_pembayaran,
        pj.subtotal, pj.pajak, pj.total_bayar, pj.dibuat_pada,
        STRING_AGG(p.nama_produk || ' x' || dp.jumlah, ', ') AS produk_detail
    FROM penjualan pj
    LEFT JOIN detail_penjualan dp ON dp.penjualan_id = pj.id
    LEFT JOIN produk p ON p.id = dp.produk_id
    WHERE pj.dibuat_pada BETWEEN :start AND :end
    $metode_filter
    GROUP BY pj.id
    ORDER BY pj.dibuat_pada DESC
    LIMIT :limit OFFSET :offset
");
$stmt_data->execute(array_merge($params, [':limit' => $per_page, ':offset' => $offset]));
$transaksi_list = $stmt_data->fetchAll(PDO::FETCH_ASSOC);


// ── Helper: pill class untuk metode pembayaran ─────────────────────
function metode_pill(string $m): string
{
  return match ($m) {
    'QRIS'     => 'badge-teal',
    'Transfer' => 'badge-blue',
    'Tunai'     => 'badge-green',
    default    => 'badge-teal',
  };
}

// ── Helper: URL paginasi dengan semua filter dipertahankan ─────────
function laporan_url(int $p): string
{
  $q = $_GET;
  $q['page'] = $p;
  return '?' . http_build_query($q);
}

?>

<div class="page-content">

  <div class="form-card">

    <!-- ── Filter Bar ────────────────────────────────────────────── -->
    <!--
      Filter di-submit via GET agar URL-nya shareable.
      Ekspor via POST ke ekspor_laporan.php membawa parameter yang sama.
    -->
    <div class="filter-bar">

      <form method="GET" action="laporan.php" id="filter-form" style="display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap; flex:1;">

        <div class="filter-field">
          <label for="filter_start_date">Tanggal Mulai</label>
          <input type="date" id="filter_start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" max="<?= date('Y-m-d') ?>">
        </div>

        <div class="filter-field">
          <label for="filter_end_date">Tanggal Akhir</label>
          <input type="date" id="filter_end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" max="<?= date('Y-m-d') ?>">
        </div>

        <div class="filter-field">
          <label for="filter_metode">Metode Bayar</label>
          <select id="filter_metode" name="metode_pembayaran">
            <option value="">Semua Metode</option>
            <?php foreach ($metode_valid as $m): ?>
              <option value="<?= $m ?>" <?= $filter_metode === $m ? 'selected' : '' ?>><?= $m ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <button type="submit" class="btn-filter">
          <i class="fa-solid fa-filter"></i> Terapkan Filter
        </button>

        <?php if ($filter_metode || $start_date !== date('Y-m-01') || $end_date !== date('Y-m-d')): ?>
          <a href="laporan.php" class="btn-secondary" style="margin:0;">
            <i class="fa-solid fa-xmark"></i> Reset
          </a>
        <?php endif; ?>
      </form>
      <form method="POST" action="ekspor_laporan.php" style="margin-left:auto;">
        <input type="hidden" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
        <input type="hidden" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
        <input type="hidden" name="metode_pembayaran" value="<?= htmlspecialchars($filter_metode) ?>">
        <input type="hidden" name="csrf_token" value="<?= session_id() ?>">
        <button type="submit" class="btn-secondary">
          <i class="fa-solid fa-file-export"></i> Ekspor CSV
        </button>
      </form>
    </div>

    <!-- ── Summary Statistik ─────────────────────────────────────── -->
    <div class="laporan-summary">
      <div class="ls-item">
        <div class="ls-label">Total Pendapatan</div>
        <div class="ls-value">
          Rp <?= number_format((float)$summary['total_pendapatan'], 0, ',', '.') ?>
        </div>
      </div>
      <div class="ls-item">
        <div class="ls-label">Jumlah Transaksi</div>
        <div class="ls-value">
          <?= number_format((int)$summary['jumlah_transaksi'], 0, ',', '.') ?>
        </div>
      </div>
      <div class="ls-item">
        <div class="ls-label">Rata-rata Transaksi</div>
        <div class="ls-value">
          Rp <?= number_format((float)$summary['rata_rata'], 0, ',', '.') ?>
        </div>
      </div>
      <div class="ls-item">
        <div class="ls-label">Total Pajak</div>
        <div class="ls-value">
          Rp <?= number_format((float)$summary['total_pajak'], 0, ',', '.') ?>
        </div>
      </div>
    </div><!-- /laporan-summary -->

    <!-- ── Tabel Transaksi ────────────────────────────────────────── -->
    <div class="table-wrap">
      <!--
        Kolom referensi:
          penjualan.nomor_transaksi, penjualan.dibuat_pada,
          produk.nama_produk + detail_penjualan.jumlah  (aggregated),
          penjualan.subtotal, penjualan.pajak,
          penjualan.total_bayar, penjualan.metode_pembayaran
      -->
      <table>
        <thead>
          <tr>
            <th>No. Transaksi</th>
            <th>Tanggal & Waktu</th>
            <th>Produk</th>
            <th>Subtotal</th>
            <th>Pajak</th>
            <th>Total Bayar</th>
            <th>Metode</th>
            <th>Detail</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($transaksi_list)): ?>
            <tr>
              <td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted);">
                <i class="fa-solid fa-receipt" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                Tidak ada transaksi untuk periode ini.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($transaksi_list as $trx): ?>
              <tr>
                <td>
                  <span style="font-family:var(--font-mono);font-size:12px;color:var(--primary);">
                    <?= htmlspecialchars($trx['nomor_transaksi']) ?>
                  </span>
                </td>
                <td style="font-size:12px;color:var(--text-muted);white-space:nowrap;">
                  <?= date('d M Y, H:i', strtotime($trx['dibuat_pada'])) ?>
                </td>
                <td style="font-size:12px;color:var(--text-secondary);max-width:220px;">
                  <?= htmlspecialchars($trx['produk_detail'] ?? '—') ?>
                </td>
                <td style="font-family:var(--font-mono);">
                  Rp <?= number_format((float)$trx['subtotal'], 0, ',', '.') ?>
                </td>
                <td style="font-family:var(--font-mono);">
                  Rp <?= number_format((float)$trx['pajak'], 0, ',', '.') ?>
                </td>
                <td>
                  <strong style="font-family:var(--font-mono);color:var(--primary);">
                    Rp <?= number_format((float)$trx['total_bayar'], 0, ',', '.') ?>
                  </strong>
                </td>
                <td>
                  <span class="status-pill <?= metode_pill($trx['metode_pembayaran']) ?>">
                    <?= htmlspecialchars($trx['metode_pembayaran']) ?>
                  </span>
                </td>
                <td>
                  <!-- TODO: href ke detail_transaksi.php?id=X -->
                  <a href="detail_transaksi.php?id=<?= $trx['id'] ?>" class="btn-sm" title="Lihat detail">
                    <i class="fa-solid fa-eye"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div><!-- /table-wrap -->

    <!-- ── Pagination ──────────────────────────────────────────────── -->
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-top:1px solid var(--border);background:var(--surface-2);flex-wrap:wrap;gap:8px;">
      <span style="font-size:12px;color:var(--text-muted);">
        Menampilkan <?= count($transaksi_list) ?> dari <?= number_format($total_rows) ?> transaksi
        (<?= htmlspecialchars($start_date) ?> s/d <?= htmlspecialchars($end_date) ?>)
      </span>

      <?php if ($total_pages > 1): ?>
        <div style="display:flex;gap:4px;flex-wrap:wrap;">

          <?php if ($page > 1): ?>
            <a href="<?= laporan_url($page - 1) ?>" class="btn-sm">
              <i class="fa-solid fa-chevron-left"></i>
            </a>
          <?php endif; ?>

          <?php
          $range = 2;
          for ($i = 1; $i <= $total_pages; $i++):
            if ($i === 1 || $i === $total_pages || ($i >= $page - $range && $i <= $page + $range)):
          ?>
              <?php if ($i > 1 && $i < $page - $range): ?>
                <span style="font-size:12px;padding:0 4px;align-self:center;color:var(--text-muted);">…</span>
              <?php endif; ?>
              <a href="<?= laporan_url($i) ?>"
                class="btn-sm"
                style="<?= $i === $page ? 'background:var(--primary);color:white;border-color:var(--primary);' : '' ?>">
                <?= $i ?>
              </a>
              <?php if ($i < $total_pages && $i > $page + $range): ?>
                <span style="font-size:12px;padding:0 4px;align-self:center;color:var(--text-muted);">…</span>
              <?php endif; ?>
          <?php endif;
          endfor; ?>

          <?php if ($page < $total_pages): ?>
            <a href="<?= laporan_url($page + 1) ?>" class="btn-sm">
              <i class="fa-solid fa-chevron-right"></i>
            </a>
          <?php endif; ?>

        </div>
      <?php endif; ?>
    </div><!-- /pagination -->

  </div><!-- /form-card -->

</div><!-- /page-content -->

<?php include 'layout/footer.php'; ?>