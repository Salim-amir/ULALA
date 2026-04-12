<?php

/**
 * input_penjualan.php
 * ─────────────────────────────────────────────────────────────────
 * Halaman form rekap penjualan.
 * Submit POST → proses_penjualan.php
 *
 * Referensi tabel DB:
 *   penjualan       : nomor_transaksi, metode_pembayaran, subtotal,
 *                     pajak, total_bayar, dibuat_pada
 *   detail_penjualan: penjualan_id, produk_id, jumlah,
 *                     harga_satuan, subtotal_item
 * ─────────────────────────────────────────────────────────────────
 */

$page_title  = 'Input Penjualan';
$active_menu = 'input_penjualan';

// ── Flash message dari proses_penjualan.php ────────────────────────
$flash_type = '';
$flash_msg  = '';
if (isset($_GET['success']) && $_GET['success'] === 'saved') {
  $flash_type = 'success';
  $flash_msg  = 'Transaksi berhasil disimpan!';
}
if (isset($_GET['error'])) {
  $flash_type = 'danger';
  $flash_msg  = match ($_GET['error']) {
    'empty_items'    => 'Harap pilih minimal satu produk.',
    'invalid_total'  => 'Total transaksi tidak valid.',
    'db_error'       => 'Terjadi kesalahan saat menyimpan. Coba lagi.',
    default          => 'Melebihi Stok!.',
  };
}

// ── Auto-generate nomor transaksi (placeholder) ────────────────────
// TODO: Ganti dengan query MAX(nomor_transaksi) + 1 dari DB
$nomor_transaksi = 'TRX-' . date('Ymd') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
$tanggal_hari_ini = date('Y-m-d');

// ── Ambil daftar produk dari DB ────────────────────────────────────

require_once 'config/db.php';
$stmt = $pdo->query("
    SELECT id, nama_produk, harga_jual, stok_saat_ini, satuan
    FROM produk
    WHERE stok_saat_ini > 0
    ORDER BY nama_produk ASC
");
$daftar_produk = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'layout/header.php';
?>

<div class="page-content">

  <?php if ($flash_msg): ?>
    <div id="php-flash" class="alert alert-<?= $flash_type ?>">
      <i class="fa-solid <?= $flash_type === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
      <?= htmlspecialchars($flash_msg) ?>
    </div>
  <?php endif; ?>

  <div class="form-card">
    <div class="form-card-header">
      <div class="fch-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
      <div>
        <h3>Form Rekap Penjualan</h3>
        <p>Catat transaksi penjualan Anda di sini</p>
      </div>
    </div>

    <div class="form-card-body">
      <!--
        ACTION  → proses_penjualan.php
        METHOD  → POST
        Fields  → nomor_transaksi, tanggal_transaksi, metode_pembayaran,
                  pajak_persen, catatan, subtotal, pajak, total_bayar
                  produk_id[] , jumlah[] , harga_satuan[]   (arrays)
      -->
      <form id="form-penjualan" method="POST" action="proses_penjualan.php">
        <input type="hidden" name="csrf_token" value="<?= /* TODO: csrf_token() */ 'placeholder_token' ?>">

        <!-- ── Header Transaksi ──────────────────────────────────── -->
        <div class="form-row grid-2">
          <div class="form-field">
            <label for="nomor_transaksi">Nomor Transaksi</label>
            <input
              type="text"
              id="nomor_transaksi"
              name="nomor_transaksi"
              value="<?= htmlspecialchars($nomor_transaksi) ?>"
              readonly
              style="background:var(--bg);color:var(--text-muted);">
          </div>
          <div class="form-field">
            <label for="tanggal_transaksi">Tanggal Transaksi</label>
            <input
              type="date"
              id="tanggal_transaksi"
              name="tanggal_transaksi"
              value="<?= $tanggal_hari_ini ?>"
              required>
          </div>
        </div>

        <!-- ── Detail Produk ─────────────────────────────────────── -->
        <div class="divider-label"><span>Detail Produk</span></div>

        <div id="detail-items">
          <!-- Baris pertama produk (statis, baris tambahan di-generate JS addItem()) -->
          <div class="detail-item" data-index="0">
            <div class="form-row grid-4" style="margin-bottom:10px;">
              <div class="form-field" style="grid-column:span 2;">
  <label>Produk</label>
  <select name="produk_id[]" id="produk_id_0" onchange="updateHarga(0)" required>
    <option value="">-- Pilih Produk --</option>
    <?php foreach ($daftar_produk as $p): ?>
      <option
        value="<?= $p['id'] ?>"
        data-harga="<?= $p['harga_jual'] ?>"
        data-stok="<?= $p['stok_saat_ini'] ?>" 
        <?= $p['stok_saat_ini'] <= 0 ? 'disabled' : '' ?>>
        <?= htmlspecialchars($p['nama_produk']) ?>
        — Rp <?= number_format($p['harga_jual'], 0, ',', '.') ?>
      </option>
    <?php endforeach; ?>
  </select>
  <small id="stok_info_0" style="display:block; margin-top:4px; color:var(--text-muted); font-weight:600;">
    Stok tersedia: -
  </small>
</div>
              <div class="form-field">
                <label>Jumlah</label>
                <div class="qty-control">
                  <button type="button" class="btn-qty" onclick="stepQty(this, -1)">−</button>
                  <input
                    type="number"
                    name="jumlah[]"
                    id="jumlah_0"
                    class="input-qty"
                    min="1"
                    value="1"
                    oninput="recalcTotal()"
                    required>
                  <button type="button" class="btn-qty" onclick="stepQty(this, 1)">+</button>
                </div>
              </div>
              <div class="form-field">
                <label>Harga Satuan (Rp)</label>
                <input
                  type="number"
                  name="harga_satuan[]"
                  id="harga_satuan_0"
                  placeholder="0"
                  min="0"
                  oninput="recalcTotal()"
                  required>
              </div>
            </div>
          </div>
        </div><!-- /#detail-items -->

        <button type="button" class="btn-sm" style="margin-bottom:16px;" onclick="addItem()">
          <i class="fa-solid fa-plus"></i> Tambah Baris Produk
        </button>

        <!-- ── Metode Pembayaran ─────────────────────────────────── -->
        <div class="divider-label"><span>Metode Pembayaran</span></div>

        <div class="metode-grid">
          <div class="metode-option">
            <input type="radio" name="metode_pembayaran" id="mp_qris" value="QRIS" checked required>
            <label class="metode-label" for="mp_qris">
              <i class="fa-solid fa-qrcode"></i>
              <span>QRIS</span>
            </label>
          </div>
          <div class="metode-option">
            <input type="radio" name="metode_pembayaran" id="mp_transfer" value="Transfer">
            <label class="metode-label" for="mp_transfer">
              <i class="fa-solid fa-building-columns"></i>
              <span>Transfer</span>
            </label>
          </div>
          <div class="metode-option">
            <input type="radio" name="metode_pembayaran" id="mp_cash" value="Cash">
            <label class="metode-label" for="mp_cash">
              <i class="fa-solid fa-money-bills"></i>
              <span>Cash</span>
            </label>
          </div>
        </div>

        <!-- ── Pajak & Catatan ────────────────────────────────────── -->
        <div class="divider-label"><span>Ringkasan</span></div>

        <div class="form-row grid-2">
          <div class="form-field">
            <label for="pajak_persen">Pajak (%)</label>
            <input
              type="number"
              id="pajak_persen"
              name="pajak_persen"
              value="11"
              min="0"
              max="100"
              step="0.1"
              oninput="recalcTotal()">
          </div>
          <div class="form-field">
            <label for="catatan">Catatan (Opsional)</label>
            <input
              type="text"
              id="catatan"
              name="catatan"
              placeholder="Catatan tambahan untuk transaksi ini...">
          </div>
        </div>

        <!-- ── Summary Box ────────────────────────────────────────── -->
        <div class="summary-box">
          <div class="summary-row">
            <span class="label">Subtotal</span>
            <span class="value" id="disp-subtotal">Rp 0</span>
          </div>
          <div class="summary-row">
            <span class="label">Pajak</span>
            <span class="value" id="disp-pajak">Rp 0 (11%)</span>
          </div>
          <div class="summary-row total">
            <span class="label">Total Bayar</span>
            <span class="value" id="disp-total">Rp 0</span>
          </div>
          <!-- Hidden computed fields — nilai diisi oleh recalcTotal() di JS -->
          <input type="hidden" id="subtotal" name="subtotal" value="0">
          <input type="hidden" id="pajak" name="pajak" value="0">
          <input type="hidden" id="total_bayar" name="total_bayar" value="0">
        </div>

        <!-- ── Aksi ───────────────────────────────────────────────── -->
        <div class="form-actions">
          <button type="button" class="btn-secondary" onclick="resetPenjualan()">
            <i class="fa-solid fa-rotate-left"></i> Reset
          </button>
          <button type="submit" class="btn-accent" id="btn-submit-penjualan">
            <i class="fa-solid fa-floppy-disk"></i> Simpan Transaksi
          </button>
        </div>

      </form>
    </div><!-- /form-card-body -->
  </div><!-- /form-card -->

</div><!-- /page-content -->

<script>
  /* ── Inject data produk ke JS agar addItem() bisa membangun <select> dinamis ── */
  const PRODUK_LIST = <?= json_encode(array_map(fn($p) => [
                        'id'    => $p['id'],
                        'nama'  => $p['nama_produk'],
                        'harga' => $p['harga_jual'],
                        'stok'  => $p['stok_saat_ini'],
                      ], $daftar_produk), JSON_UNESCAPED_UNICODE) ?>;

  /* Validasi client-side sebelum submit */
  document.getElementById('form-penjualan').addEventListener('submit', function(e) {
    const total = parseFloat(document.getElementById('total_bayar').value) || 0;
    if (total <= 0) {
      e.preventDefault();
      showFlash('danger', 'Harap pilih produk dan isi jumlah terlebih dahulu!');
      return;
    }
    /* Disable tombol agar tidak double-submit */
    document.getElementById('btn-submit-penjualan').disabled = true;
    document.getElementById('btn-submit-penjualan').innerHTML =
      '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';
  });

  /* Hitung ulang saat halaman dimuat (jika ada nilai tersisa) */
  document.addEventListener('DOMContentLoaded', recalcTotal);
</script>

<?php include 'layout/footer.php'; ?>