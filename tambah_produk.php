<?php
/**
 * tambah_produk.php
 * CRUD bagian CREATE — form tambah produk baru
*/
// session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'config/db.php';

$page_title  = 'Tambah Produk';
$active_menu = 'kelola_produk';
include 'layout/header.php';

$kategori_list = $pdo->query("SELECT id, nama_kategori, sku_prefix FROM kategori ORDER BY nama_kategori")->fetchAll();

// Nilai default form (dikembalikan jika validasi gagal)
$f = [
    'sku'          => $_GET['sku']          ?? '',
    'nama_produk'  => $_GET['nama_produk']  ?? '',
    'kategori_id'  => $_GET['kategori_id']  ?? '',
    'harga_jual'   => $_GET['harga_jual']   ?? '',
    'stok_saat_ini'=> $_GET['stok_saat_ini']?? '0',
    'stok_minimum' => $_GET['stok_minimum'] ?? '5',
    'satuan'       => $_GET['satuan']       ?? 'pcs',
];

$error = $_GET['error'] ?? '';
$error_msgs = [
    'sku_taken'   => 'SKU sudah digunakan produk lain.',
    'required'    => 'Semua field wajib diisi.',
    'db_error'    => 'Gagal menyimpan ke database.',
];

?>
<div class="page-content">

  <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
    <a href="kelola_produk.php" class="btn-secondary" style="width:auto;">
      <i class="fa-solid fa-arrow-left"></i> Kembali
    </a>
    <h2 style="font-size:18px;font-weight:800;">Tambah Produk Baru</h2>
  </div>

  <?php if ($error && isset($error_msgs[$error])): ?>
    <div class="alert alert-danger">
      <i class="fa-solid fa-circle-xmark"></i> <?= $error_msgs[$error] ?>
    </div>
  <?php endif; ?>

  <div class="form-card">
    <div class="form-card-header">
      <div class="fch-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
      <div>
        <h3>Data Produk Baru</h3>
        <p>Isi semua informasi produk yang akan ditambahkan</p>
      </div>
    </div>
    <div class="form-card-body">
      <form method="POST" action="simpan_produk.php">
        <input type="hidden" name="csrf_token" value="<?= session_id() ?>">
        <input type="hidden" name="action" value="tambah">

        <div class="form-row grid-2">
          <div class="form-field">
            <label style="display:flex;align-items:center;justify-content:space-between;">
              SKU
              <span id="sku-status" style="font-size:10px;font-weight:700;color:var(--text-muted);
                padding:2px 8px;border-radius:20px;background:var(--bg);">
                Pilih kategori dulu
              </span>
            </label>
            <!-- SKU sepenuhnya otomatis — tidak bisa diketik.
                 Nilai dikirim via hidden input agar tidak bisa dimanipulasi
                 dari field yang terlihat. Server juga generate ulang di simpan_produk.php. -->
            <div style="position:relative;">
              <div id="sku-display" style="
                padding:10px 42px 10px 14px;
                border:1.5px dashed var(--border);
                border-radius:var(--radius-sm);
                background:var(--bg);
                font-family:var(--font-mono);font-weight:800;font-size:14px;
                color:var(--text-muted);letter-spacing:1px;min-height:42px;
                display:flex;align-items:center;">
                <span id="sku-display-text" style="opacity:0.5;">—</span>
              </div>
              <button type="button" id="btn-regen-sku"
                      style="position:absolute;right:10px;top:50%;transform:translateY(-50%);
                             background:none;border:none;cursor:pointer;color:var(--text-muted);
                             font-size:14px;padding:4px;transition:var(--transition);"
                      title="Refresh nomor SKU"
                      onclick="generateSKU()"
                      onmouseover="this.style.color='var(--primary)'"
                      onmouseout="this.style.color='var(--text-muted)'">
                <i class="fa-solid fa-rotate" id="sku-spin-icon"></i>
              </button>
            </div>
            <!-- Hidden input yang benar-benar dikirim ke server -->
            <input type="hidden" id="sku" name="sku" value="<?= htmlspecialchars($f['sku']) ?>">
            <p style="font-size:11px;color:var(--text-muted);margin-top:5px;">
              <i class="fa-solid fa-circle-info" style="font-size:10px;"></i>
              SKU dibuat otomatis dari prefix kategori. Tidak bisa diubah manual.
            </p>
          </div>
          <div class="form-field">
            <label for="nama_produk">Nama Produk <span style="color:var(--danger);">*</span></label>
            <input type="text" id="nama_produk" name="nama_produk"
                   value="<?= htmlspecialchars($f['nama_produk']) ?>"
                   placeholder="Nama produk lengkap" required>
          </div>
        </div>

        <div class="form-row grid-2">
          <div class="form-field">
            <label for="kategori_id">Kategori <span style="color:var(--danger);">*</span></label>
            <select id="kategori_id" name="kategori_id" required>
              <option value="">-- Pilih Kategori --</option>
              <?php foreach ($kategori_list as $k): ?>
                <option value="<?= $k['id'] ?>"
                        data-prefix="<?= htmlspecialchars($k['sku_prefix'] ?? '') ?>"
                        <?= $f['kategori_id'] == $k['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($k['nama_kategori']) ?>
                  <?= $k['sku_prefix'] ? ' [' . $k['sku_prefix'] . ']' : ' (no prefix)' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-field">
            <label for="satuan">Satuan <span style="color:var(--danger);">*</span></label>
            <select id="satuan" name="satuan" required>
              <?php foreach (['pcs','kg','gram','liter','ml','botol','sachet','lusin','karton'] as $sat): ?>
                <option value="<?= $sat ?>" <?= $f['satuan'] === $sat ? 'selected' : '' ?>><?= $sat ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row grid-3">
          <div class="form-field">
            <label for="harga_jual">Harga Jual (Rp) <span style="color:var(--danger);">*</span></label>
            <input type="number" id="harga_jual" name="harga_jual" value="<?= htmlspecialchars($f['harga_jual']) ?>"
                   placeholder="0" min="0" step="100" required>
          </div>
          <div class="form-field">
            <label for="stok_saat_ini">Stok Awal</label>
            <input type="number" id="stok_saat_ini" name="stok_saat_ini" value="<?= htmlspecialchars($f['stok_saat_ini']) ?>"
                   min="0">
          </div>
          <div class="form-field">
            <label for="stok_minimum">Stok Minimum Alert</label>
            <input type="number" id="stok_minimum" name="stok_minimum" value="<?= htmlspecialchars($f['stok_minimum']) ?>"
                   min="0">
          </div>
        </div>

        <div class="form-actions">
          <a href="kelola_produk.php" class="btn-secondary">
            <i class="fa-solid fa-xmark"></i> Batal
          </a>
          <button type="submit" class="btn-accent">
            <i class="fa-solid fa-floppy-disk"></i> Simpan Produk
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
async function generateSKU() {
    const sel     = document.getElementById('kategori_id');
    const hidden  = document.getElementById('sku');
    const display = document.getElementById('sku-display-text');
    const box     = document.getElementById('sku-display');
    const status  = document.getElementById('sku-status');
    const spinIcon= document.getElementById('sku-spin-icon');

    if (!sel) return;
    const opt    = sel.options[sel.selectedIndex];
    const prefix = opt?.dataset?.prefix || '';

    // Reset jika tidak ada prefix
    if (!prefix) {
        hidden.value   = '';
        display.textContent = '—';
        display.style.opacity = '0.5';
        box.style.borderColor = 'var(--border)';
        box.style.borderStyle = 'dashed';
        status.textContent    = 'Kategori ini tidak punya prefix';
        status.style.color    = 'var(--text-muted)';
        status.style.background = 'var(--bg)';
        return;
    }

    // Loading state
    spinIcon.style.animation = 'spin 0.6s linear infinite';
    display.textContent      = prefix + '-…';
    display.style.opacity    = '0.6';
    status.textContent       = 'Mengambil nomor…';
    box.style.borderColor    = 'var(--border)';
    box.style.borderStyle    = 'dashed';

    try {
        const res  = await fetch(`get_next_sku.php?prefix=${encodeURIComponent(prefix)}`);
        const data = await res.json();
        const sku  = data.sku || (prefix + '-001');

        hidden.value            = sku;
        display.textContent     = sku;
        display.style.opacity   = '1';
        display.style.color     = 'var(--primary)';
        box.style.borderColor   = 'var(--primary)';
        box.style.borderStyle   = 'solid';
        box.style.background    = 'var(--primary-light)';
        status.textContent      = '✓ Otomatis';
        status.style.color      = 'var(--primary)';
        status.style.background = 'var(--primary-light)';

    } catch (e) {
        // Fallback jika server tidak merespons
        const fallback = prefix + '-001';
        hidden.value          = fallback;
        display.textContent   = fallback;
        display.style.opacity = '0.8';
        status.textContent    = 'Fallback (offline)';
        status.style.color    = 'var(--warning)';
    } finally {
        spinIcon.style.animation = '';
    }
}

// Jalankan saat kategori berubah
document.getElementById('kategori_id')?.addEventListener('change', generateSKU);

// Jalankan saat halaman load jika kategori sudah terpilih (dari redirect error)
document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('kategori_id');
    if (sel && sel.value) generateSKU();
});
</script>

<?php include 'layout/footer.php'; ?>