<?php
require 'config.php';

if (isset($_POST['tambah'])) {
    try {
        // Generate SKU otomatis
        $sku = 'PRD-' . strtoupper(substr(uniqid(), -6));
        $kategori_id = !empty($_POST['kategori_id']) ? $_POST['kategori_id'] : null;
        $nama = $_POST['nama_produk'];
        $harga_beli = $_POST['harga_beli'];
        $harga_jual = $_POST['harga_jual'];
        $stok = $_POST['stok_saat_ini'];
        $stok_min = $_POST['stok_minimum'];
        $satuan = $_POST['satuan'];

        $stmt = $pdo->prepare("INSERT INTO produk (sku, kategori_id, nama_produk, harga_beli, harga_jual, stok_saat_ini, stok_minimum, satuan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$sku, $kategori_id, $nama, $harga_beli, $harga_jual, $stok, $stok_min, $satuan]);
        
        header("Location: produk.php");
        exit;
    } catch (PDOException $e) {
        $error = "Gagal menambah data: " . $e->getMessage();
    }
}

if (isset($_POST['edit_stok'])) {
    $id = $_POST['id'];
    $stok = $_POST['stok_saat_ini'];
    
    $stmt = $pdo->prepare("UPDATE produk SET stok_saat_ini = ? WHERE id = ?");
    $stmt->execute([$stok, $id]);
    header("Location: produk.php");
    exit;
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $stmt = $pdo->prepare("DELETE FROM produk WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: produk.php");
    exit;
}

try {
    $kategori_stmt = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
    $data_kategori = $kategori_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $data_kategori = []; 
}

$stmt = $pdo->query("SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori k ON p.kategori_id = k.id ORDER BY p.id DESC");
$data_produk = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    <aside class="w-64 bg-indigo-700 text-white flex flex-col">
        <div class="h-16 flex items-center px-6 font-bold text-xl border-b border-indigo-600">
            <i class="fas fa-robot mr-2"></i> Smart UMKM
        </div>
        <nav class="flex-1 px-4 py-4 space-y-2">
            <a href="index.php" class="flex items-center px-4 py-2 hover:bg-indigo-600 rounded-lg"><i class="fas fa-home w-6"></i> Beranda</a>
            <a href="penjualan.php" class="flex items-center px-4 py-2 hover:bg-indigo-600 rounded-lg"><i class="fas fa-shopping-cart w-6"></i> Penjualan</a>
            <a href="produk.php" class="flex items-center px-4 py-2 bg-indigo-800 rounded-lg"><i class="fas fa-box w-6"></i> Produk & Stok</a>
            <a href="ai_insights.php" class="flex items-center px-4 py-2 hover:bg-indigo-600 rounded-lg"><i class="fas fa-lightbulb w-6"></i> AI Insights</a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col overflow-y-auto">
        <header class="h-16 bg-white shadow-sm flex items-center px-8">
            <h1 class="text-2xl font-semibold text-gray-800">Manajemen Produk & Stok</h1>
        </header>

        <div class="p-8 space-y-6">
            <?php if(isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?= $error ?></span>
                </div>
            <?php endif; ?>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-plus-circle text-indigo-600 mr-2"></i>Tambah Produk Baru</h3>
                <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Produk</label>
                        <input type="text" name="nama_produk" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                        <select name="kategori_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">-- Pilih --</option>
                            <?php foreach($data_kategori as $kat): ?>
                                <option value="<?= $kat['id'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Harga Beli (Rp)</label>
                        <input type="number" name="harga_beli" value="0" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Harga Jual (Rp)</label>
                        <input type="number" name="harga_jual" value="0" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stok</label>
                            <input type="number" name="stok_saat_ini" value="0" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Min. Stok</label>
                            <input type="number" name="stok_minimum" value="5" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Satuan</label>
                            <input type="text" name="satuan" placeholder="pcs/kg" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <button type="submit" name="tambah" class="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 mt-6">
                                Simpan
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU & Nama</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Beli / Jual</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok Saat Ini</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach($data_produk as $row): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900"><?= htmlspecialchars($row['nama_produk']) ?></div>
                                    <div class="text-xs text-gray-500">SKU: <?= htmlspecialchars($row['sku']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($row['nama_kategori'] ?? 'Umum') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-xs text-gray-500">Beli: Rp <?= number_format($row['harga_beli'], 0, ',', '.') ?></div>
                                    <div class="text-sm font-semibold text-green-600">Jual: Rp <?= number_format($row['harga_jual'], 0, ',', '.') ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <form method="POST" action="" class="flex items-center space-x-2">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <input type="number" name="stok_saat_ini" value="<?= $row['stok_saat_ini'] ?>" 
                                            class="w-20 border <?= $row['stok_saat_ini'] <= $row['stok_minimum'] ? 'border-red-500 bg-red-50 text-red-700' : 'border-gray-300' ?> rounded-lg px-2 py-1 text-sm text-center">
                                        <span class="text-xs text-gray-500"><?= htmlspecialchars($row['satuan']) ?></span>
                                        <button type="submit" name="edit_stok" class="text-indigo-600 hover:text-indigo-900"><i class="fas fa-save"></i></button>
                                    </form>
                                    <?php if($row['stok_saat_ini'] <= $row['stok_minimum']): ?>
                                        <div class="text-xs text-red-500 mt-1"><i class="fas fa-exclamation-circle"></i> Stok menipis!</div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <a href="?hapus=<?= $row['id'] ?>" onclick="return confirm('Yakin ingin menghapus produk ini?')" class="text-red-600 hover:text-red-900 bg-red-50 p-2 rounded-lg">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($data_produk)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">Belum ada data produk.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

</body>
</html>