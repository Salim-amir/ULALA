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

$kategori_list = $pdo->query("SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori")->fetchAll();

// Nilai default form (dikembalikan jika validasi gagal)
$f = [
    'sku'          => $_GET['sku']          ?? '',
    'nama_produk'  => $_GET['nama_produk']  ?? '',
    'kategori_id'  => $_GET['kategori_id']  ?? '',
    'harga_jual'   => $_GET['harga_jual']   ?? '',
    'stok_saat_ini' => $_GET['stok_saat_ini'] ?? '0',
    'stok_minimum' => $_GET['stok_minimum'] ?? '10',
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
                        <label for="sku">SKU <span style="color:var(--danger);">*</span></label>
                        <input type="text" id="sku" name="sku" value="<?= htmlspecialchars($f['sku']) ?>"
                            placeholder="contoh: PRD-001" required>
                    </div>
                    <div class="form-field">
                        <label for="nama_produk">Nama Produk <span style="color:var(--danger);">*</span></label>
                        <input type="text" id="nama_produk" name="nama_produk" value="<?= htmlspecialchars($f['nama_produk']) ?>"
                            placeholder="Nama produk lengkap" required>
                    </div>
                </div>

                <div class="form-row grid-2">
                    <div class="form-field">
                        <label for="kategori_id">Kategori <span style="color:var(--danger);">*</span></label>
                        <select id="kategori_id" name="kategori_id" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($kategori_list as $k): ?>
                                <option value="<?= $k['id'] ?>" <?= $f['kategori_id'] == $k['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($k['nama_kategori']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="satuan">Satuan <span style="color:var(--danger);">*</span></label>
                        <select id="satuan" name="satuan" required>
                            <?php foreach (['pcs', 'kg', 'gram', 'liter', 'ml', 'botol', 'sachet', 'lusin', 'karton'] as $sat): ?>
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
<?php include 'layout/footer.php'; ?>