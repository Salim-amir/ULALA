<?php
/**
 * edit_produk.php
 * CRUD bagian UPDATE — form edit produk, data prefilled dari DB
 */
// session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'config/db.php';

include 'layout/header.php';
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: kelola_produk.php'); exit; }

// Ambil data produk dari DB
$produk = $pdo->prepare("
SELECT p.*, k.nama_kategori
FROM produk p
    LEFT JOIN kategori k ON k.id = p.kategori_id
    WHERE p.id = ?
");
$produk->execute([$id]);
$p = $produk->fetch();

if (!$p) {
    header('Location: kelola_produk.php?error=not_found');
    exit;
}

$page_title  = 'Edit Produk';
$active_menu = 'kelola_produk';

$kategori_list = $pdo->query("SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori")->fetchAll();

$error = $_GET['error'] ?? '';
$error_msgs = [
    'sku_taken' => 'SKU sudah digunakan produk lain.',
    'required'  => 'Semua field wajib diisi.',
    'db_error'  => 'Gagal menyimpan ke database.',
];

?>
<div class="page-content">

  <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
    <a href="kelola_produk.php" class="btn-secondary" style="width:auto;">
      <i class="fa-solid fa-arrow-left"></i> Kembali
    </a>
    <h2 style="font-size:18px;font-weight:800;">Edit Produk</h2>
    <span style="font-family:var(--font-mono);font-size:12px;color:var(--text-muted);background:var(--bg);padding:3px 8px;border-radius:6px;">
      ID: <?= $id ?>
    </span>
  </div>

  <?php if ($error && isset($error_msgs[$error])): ?>
    <div class="alert alert-danger">
      <i class="fa-solid fa-circle-xmark"></i> <?= $error_msgs[$error] ?>
    </div>
  <?php endif; ?>

  <div class="form-card">
    <div class="form-card-header">
      <div class="fch-icon"><i class="fa-solid fa-pen-to-square"></i></div>
      <div>
        <h3>Edit Data Produk</h3>
        <p>Perbarui informasi produk <strong><?= htmlspecialchars($p['nama_produk']) ?></strong></p>
      </div>
    </div>
    <div class="form-card-body">
      <form method="POST" action="simpan_produk.php">
        <input type="hidden" name="csrf_token" value="<?= session_id() ?>">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="form-row grid-2">
          <div class="form-field">
            <label for="sku">SKU <span style="color:var(--danger);">*</span></label>
            <input type="text" id="sku" name="sku"
                   value="<?= htmlspecialchars($p['sku']) ?>" required>
          </div>
          <div class="form-field">
            <label for="nama_produk">Nama Produk <span style="color:var(--danger);">*</span></label>
            <input type="text" id="nama_produk" name="nama_produk"
                   value="<?= htmlspecialchars($p['nama_produk']) ?>" required>
          </div>
        </div>

        <div class="form-row grid-2">
          <div class="form-field">
            <label for="kategori_id">Kategori <span style="color:var(--danger);">*</span></label>
            <select id="kategori_id" name="kategori_id" required>
              <option value="">-- Pilih Kategori --</option>
              <?php foreach ($kategori_list as $k): ?>
                <option value="<?= $k['id'] ?>" <?= $p['kategori_id'] == $k['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($k['nama_kategori']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-field">
            <label for="satuan">Satuan <span style="color:var(--danger);">*</span></label>
            <select id="satuan" name="satuan" required>
              <?php foreach (['pcs','kg','gram','liter','ml','botol','sachet','lusin','karton'] as $sat): ?>
                <option value="<?= $sat ?>" <?= $p['satuan'] === $sat ? 'selected' : '' ?>>
                  <?= $sat ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row grid-3">
          <div class="form-field">
            <label for="harga_jual">Harga Jual (Rp) <span style="color:var(--danger);">*</span></label>
            <input type="number" id="harga_jual" name="harga_jual"
                   value="<?= (float)$p['harga_jual'] ?>" min="0" step="100" required>
          </div>
          <div class="form-field">
            <label for="stok_saat_ini">Stok Saat Ini</label>
            <input type="number" id="stok_saat_ini" name="stok_saat_ini"
                   value="<?= (int)$p['stok_saat_ini'] ?>" min="0">
          </div>
          <div class="form-field">
            <label for="stok_minimum">Stok Minimum Alert</label>
            <input type="number" id="stok_minimum" name="stok_minimum"
                   value="<?= (int)$p['stok_minimum'] ?>" min="0">
          </div>
        </div>

        <!-- Info readonly -->
        <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px 16px;margin-bottom:16px;font-size:12px;color:var(--text-muted);display:flex;gap:24px;flex-wrap:wrap;">
          <span><i class="fa-regular fa-clock" style="margin-right:4px;"></i>
            Terakhir diperbarui: <?= date('d M Y H:i', strtotime($p['diperbarui_pada'])) ?>
          </span>
        </div>

        <div class="form-actions">
          <a href="kelola_produk.php" class="btn-secondary">
            <i class="fa-solid fa-xmark"></i> Batal
          </a>
          <button type="submit" class="btn-accent">
            <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php include 'layout/footer.php'; ?>