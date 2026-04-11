<?php
require 'config.php';

// ─── TAMBAH PRODUK BARU ──────────────────────────────────────────────────────
if (isset($_POST['tambah'])) {
    try {
        $sku        = 'PRD-' . strtoupper(substr(uniqid(), -6));
        $kategori_id = !empty($_POST['kategori_id']) ? $_POST['kategori_id'] : null;
        $nama       = trim($_POST['nama_produk']);
        $harga_beli = (int)$_POST['harga_beli'];
        $harga_jual = (int)$_POST['harga_jual'];
        $stok       = (int)$_POST['stok_saat_ini'];

        if ($stok < 0) throw new Exception("Stok tidak boleh negatif.");

        $stmt = $pdo->prepare("INSERT INTO produk (sku, kategori_id, nama_produk, harga_beli, harga_jual, stok_saat_ini) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$sku, $kategori_id, $nama, $harga_beli, $harga_jual, $stok]);

        header("Location: produk.php?success=tambah&nama=" . urlencode($nama));
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// ─── RESTOCK (TAMBAH STOK) ───────────────────────────────────────────────────
if (isset($_POST['restock'])) {
    try {
        $id     = (int)$_POST['id'];
        $tambah = (int)$_POST['jumlah_restock'];

        if ($tambah <= 0) throw new Exception("Jumlah restock harus lebih dari 0.");

        $cek = $pdo->prepare("SELECT nama_produk, stok_saat_ini FROM produk WHERE id = ?");
        $cek->execute([$id]);
        $produk = $cek->fetch();
        if (!$produk) throw new Exception("Produk tidak ditemukan.");

        $stok_baru = $produk['stok_saat_ini'] + $tambah;

        $pdo->prepare("UPDATE produk SET stok_saat_ini = ? WHERE id = ?")->execute([$stok_baru, $id]);

        header("Location: produk.php?success=restock&nama=" . urlencode($produk['nama_produk']) . "&tambah=$tambah&stok_baru=$stok_baru");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// ─── KOREKSI STOK (SET MANUAL) ───────────────────────────────────────────────
if (isset($_POST['koreksi_stok'])) {
    try {
        $id         = (int)$_POST['id'];
        $stok_baru  = (int)$_POST['stok_koreksi'];

        if ($stok_baru < 0) throw new Exception("Stok tidak boleh negatif.");

        $cek = $pdo->prepare("SELECT nama_produk FROM produk WHERE id = ?");
        $cek->execute([$id]);
        $produk = $cek->fetch();

        $pdo->prepare("UPDATE produk SET stok_saat_ini = ? WHERE id = ?")->execute([$stok_baru, $id]);

        header("Location: produk.php?success=koreksi&nama=" . urlencode($produk['nama_produk']) . "&stok_baru=$stok_baru");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// ─── EDIT PRODUK ─────────────────────────────────────────────────────────────
if (isset($_POST['edit_produk'])) {
    try {
        $id         = (int)$_POST['id'];
        $nama       = trim($_POST['nama_produk']);
        $harga_beli = (int)$_POST['harga_beli'];
        $harga_jual = (int)$_POST['harga_jual'];
        $kategori_id = !empty($_POST['kategori_id']) ? $_POST['kategori_id'] : null;

        $pdo->prepare("UPDATE produk SET nama_produk=?, harga_beli=?, harga_jual=?, kategori_id=? WHERE id=?")
            ->execute([$nama, $harga_beli, $harga_jual, $kategori_id, $id]);

        header("Location: produk.php?success=edit&nama=" . urlencode($nama));
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// ─── HAPUS PRODUK ────────────────────────────────────────────────────────────
if (isset($_GET['hapus'])) {
    try {
        $id = (int)$_GET['hapus'];
        $cek = $pdo->prepare("SELECT COUNT(*) FROM detail_penjualan WHERE produk_id = ?");
        $cek->execute([$id]);
        if ($cek->fetchColumn() > 0) {
            $error = "Produk tidak bisa dihapus karena sudah punya riwayat penjualan.";
        } else {
            $pdo->prepare("DELETE FROM produk WHERE id = ?")->execute([$id]);
            header("Location: produk.php?success=hapus");
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// ─── FETCH DATA ──────────────────────────────────────────────────────────────
try {
    $data_kategori = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $data_kategori = []; }

$data_produk = $pdo->query("
    SELECT p.*, k.nama_kategori,
           COALESCE((SELECT SUM(dp.jumlah) FROM detail_penjualan dp 
                     JOIN penjualan pj ON pj.id = dp.penjualan_id 
                     WHERE dp.produk_id = p.id AND pj.dibuat_pada >= NOW() - INTERVAL '30 days'), 0) AS terjual_30hari
    FROM produk p
    LEFT JOIN kategori k ON p.kategori_id = k.id
    ORDER BY p.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk & Stok - Smart UMKM Assistant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <aside class="w-64 bg-indigo-700 text-white flex flex-col flex-shrink-0">
        <div class="h-16 flex items-center px-6 font-bold text-xl border-b border-indigo-600">
            <i class="fas fa-robot mr-2"></i> Smart UMKM
        </div>
        <nav class="flex-1 px-4 py-4 space-y-2">
            <a href="index.php"       class="flex items-center px-4 py-2 hover:bg-indigo-600 rounded-lg"><i class="fas fa-home w-6"></i> Beranda</a>
            <a href="penjualan.php"   class="flex items-center px-4 py-2 hover:bg-indigo-600 rounded-lg"><i class="fas fa-shopping-cart w-6"></i> Penjualan</a>
            <a href="produk.php"      class="flex items-center px-4 py-2 bg-indigo-800 rounded-lg"><i class="fas fa-box w-6"></i> Produk & Stok</a>
            <a href="ai_insights.php" class="flex items-center px-4 py-2 hover:bg-indigo-600 rounded-lg"><i class="fas fa-lightbulb w-6"></i> AI Insights</a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col overflow-y-auto">
        <header class="h-16 bg-white shadow-sm flex items-center px-8">
            <h1 class="text-2xl font-semibold text-gray-800"><i class="fas fa-box text-indigo-600 mr-2"></i>Manajemen Produk & Stok</h1>
        </header>

        <div class="p-6 space-y-6">

            <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2">
                <i class="fas fa-check-circle text-green-600"></i>
                <?php
                $s = $_GET['success'];
                $nm = htmlspecialchars($_GET['nama'] ?? '');
                if ($s === 'tambah')  echo "Produk <strong>$nm</strong> berhasil ditambahkan.";
                elseif ($s === 'restock') echo "Restock berhasil! <strong>$nm</strong> +{$_GET['tambah']} → stok sekarang <strong>{$_GET['stok_baru']}</strong>.";
                elseif ($s === 'koreksi') echo "Stok <strong>$nm</strong> dikoreksi menjadi <strong>{$_GET['stok_baru']}</strong>.";
                elseif ($s === 'edit')    echo "Produk <strong>$nm</strong> berhasil diperbarui.";
                elseif ($s === 'hapus')   echo "Produk berhasil dihapus.";
                ?>
            </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg flex items-center gap-2">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-plus-circle text-indigo-600 mr-2"></i>Tambah Produk Baru</h2>
                <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Produk <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_produk" required placeholder="Contoh: Sandal Jepit Polos"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                        <select name="kategori_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">-- Tanpa Kategori --</option>
                            <?php foreach ($data_kategori as $kat): ?>
                            <option value="<?= $kat['id'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Harga Beli (Rp)</label>
                        <input type="number" name="harga_beli" value="0" min="0" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Harga Jual (Rp) <span class="text-red-500">*</span></label>
                        <input type="number" name="harga_jual" value="0" min="0" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stok Awal <span class="text-red-500">*</span></label>
                        <input type="number" name="stok_saat_ini" value="0" min="0" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    
                    <div class="md:col-span-3 flex justify-end mt-2">
                        <button type="submit" name="tambah"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-2.5 rounded-lg font-semibold text-sm flex items-center gap-2 transition">
                            <i class="fas fa-plus"></i> Simpan Produk
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-bold text-gray-800"><i class="fas fa-list text-indigo-600 mr-2"></i>Daftar Produk</h2>
                    <span class="text-xs text-gray-400"><?= count($data_produk) ?> produk</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Produk</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kategori</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Harga Beli / Jual</th>
                                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Stok Tersisa</th>
                                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Terjual 30hr</th>
                                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                        <?php foreach ($data_produk as $row):
                            $stok_habis  = $row['stok_saat_ini'] == 0;
                        ?>
                        <tr class="hover:bg-gray-50 transition <?= $stok_habis ? 'bg-red-50' : '' ?>">
                            <td class="px-5 py-3">
                                <div class="font-semibold text-gray-800"><?= htmlspecialchars($row['nama_produk']) ?></div>
                                <div class="text-xs text-gray-400">SKU: <?= htmlspecialchars($row['sku']) ?></div>
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-500"><?= htmlspecialchars($row['nama_kategori'] ?? 'Umum') ?></td>
                            <td class="px-5 py-3">
                                <div class="text-xs text-gray-400">Beli: Rp <?= number_format($row['harga_beli'], 0, ',', '.') ?></div>
                                <div class="text-sm font-semibold text-green-600">Jual: Rp <?= number_format($row['harga_jual'], 0, ',', '.') ?></div>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <?php if ($stok_habis): ?>
                                    <span class="bg-red-100 text-red-700 font-bold text-sm px-3 py-1 rounded-full">HABIS</span>
                                <?php else: ?>
                                    <span class="text-gray-800 font-bold text-base"><?= $row['stok_saat_ini'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3 text-center text-sm font-semibold text-indigo-600">
                                <?= $row['terjual_30hari'] ?>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button onclick="bukaRestock(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nama_produk'])) ?>', <?= $row['stok_saat_ini'] ?>)"
                                        class="bg-green-100 hover:bg-green-200 text-green-700 px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1 transition" title="Tambah Stok">
                                        <i class="fas fa-plus"></i> Restock
                                    </button>
                                    <button onclick="bukaEdit(<?= htmlspecialchars(json_encode($row)) ?>)"
                                        class="bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1 transition" title="Edit Produk">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <a href="?hapus=<?= $row['id'] ?>" onclick="return confirm('Hapus produk <?= htmlspecialchars(addslashes($row['nama_produk'])) ?>?')"
                                        class="bg-red-100 hover:bg-red-200 text-red-600 px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1 transition" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($data_produk)): ?>
                        <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">Belum ada produk. Tambah produk pertama kamu di atas!</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div id="modal_restock" class="hidden fixed inset-0 bg-black bg-opacity-40 z-50 flex items-center justify-center">
        <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-sm mx-4">
            <h3 class="font-bold text-gray-800 text-lg mb-1"><i class="fas fa-boxes-stacking text-green-600 mr-2"></i>Tambah Stok (Restock)</h3>
            <p class="text-sm text-gray-500 mb-4">Masukkan <strong>jumlah yang mau ditambahkan</strong>.</p>

            <div class="bg-gray-50 rounded-lg px-4 py-2 mb-4 text-sm">
                <span class="text-gray-500">Produk:</span> <span id="restock_nama" class="font-semibold text-gray-800"></span><br>
                <span class="text-gray-500">Stok sekarang:</span> <span id="restock_stok_kini" class="font-bold text-indigo-600"></span>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="id" id="restock_id">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah yang ditambahkan</label>
                    <input type="number" name="jumlah_restock" id="jumlah_restock" min="1" value="1" required
                        class="w-full border-2 border-green-400 rounded-lg px-3 py-2 text-lg font-bold text-center focus:ring-green-500 focus:border-green-500">
                    <p class="text-xs text-gray-400 mt-1 text-center" id="restock_preview">Stok baru: —</p>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="tutupModal('modal_restock')"
                        class="flex-1 border border-gray-300 text-gray-600 py-2 rounded-lg font-semibold text-sm hover:bg-gray-50">Batal</button>
                    <button type="submit" name="restock"
                        class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg font-semibold text-sm flex items-center justify-center gap-2">
                        <i class="fas fa-plus"></i> Tambah Stok
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="modal_edit" class="hidden fixed inset-0 bg-black bg-opacity-40 z-50 flex items-center justify-center">
        <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-lg mx-4">
            <h3 class="font-bold text-gray-800 text-lg mb-4"><i class="fas fa-pen text-blue-600 mr-2"></i>Edit Produk</h3>
            <form method="POST" action="" class="space-y-3">
                <input type="hidden" name="id" id="edit_id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Produk</label>
                    <input type="text" name="nama_produk" id="edit_nama" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Harga Beli</label>
                        <input type="number" name="harga_beli" id="edit_harga_beli" min="0"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Harga Jual</label>
                        <input type="number" name="harga_jual" id="edit_harga_jual" min="0"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                    <select name="kategori_id" id="edit_kategori" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">-- Tanpa Kategori --</option>
                        <?php foreach ($data_kategori as $kat): ?>
                        <option value="<?= $kat['id'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mt-4">
                    <p class="text-xs font-semibold text-yellow-700 mb-2">
                        <i class="fas fa-triangle-exclamation mr-1"></i>Koreksi Stok Manual
                        <span class="font-normal text-yellow-600">(gunakan hanya untuk penyesuaian fisik)</span>
                    </p>
                    <div class="flex gap-2 items-center">
                        <input type="number" name="stok_koreksi" id="edit_stok_koreksi" min="0" placeholder="Isi angka stok benar"
                            class="flex-1 border border-yellow-300 rounded-lg px-3 py-1.5 text-sm">
                        <button type="submit" name="koreksi_stok"
                            class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap">
                            Set Stok
                        </button>
                    </div>
                </div>

                <div class="flex gap-3 pt-3 border-t">
                    <button type="button" onclick="tutupModal('modal_edit')"
                        class="flex-1 border border-gray-300 text-gray-600 py-2 rounded-lg font-semibold text-sm hover:bg-gray-50">Batal</button>
                    <button type="submit" name="edit_produk"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg font-semibold text-sm">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

<script>
function bukaRestock(id, nama, stokKini) {
    document.getElementById('restock_id').value    = id;
    document.getElementById('restock_nama').textContent      = nama;
    document.getElementById('restock_stok_kini').textContent = stokKini;
    document.getElementById('jumlah_restock').value = 1;
    document.getElementById('restock_preview').textContent   = 'Stok baru: ' + (stokKini + 1);
    document.getElementById('modal_restock').classList.remove('hidden');

    document.getElementById('jumlah_restock').oninput = function() {
        const tambah = parseInt(this.value) || 0;
        document.getElementById('restock_preview').textContent = tambah > 0
            ? 'Stok baru: ' + (stokKini + tambah)
            : 'Masukkan jumlah yang valid';
    };
}

function bukaEdit(data) {
    document.getElementById('edit_id').value          = data.id;
    document.getElementById('edit_nama').value        = data.nama_produk;
    document.getElementById('edit_harga_beli').value  = data.harga_beli;
    document.getElementById('edit_harga_jual').value  = data.harga_jual;
    document.getElementById('edit_stok_koreksi').value = '';
    
    const sel = document.getElementById('edit_kategori');
    for (let opt of sel.options) opt.selected = (opt.value == data.kategori_id);
    document.getElementById('modal_edit').classList.remove('hidden');
}

function tutupModal(id) {
    document.getElementById(id).classList.add('hidden');
}

['modal_restock','modal_edit'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) tutupModal(id);
    });
});
</script>

</body>
</html>