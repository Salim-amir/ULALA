<?php
require 'config.php'; // Pastikan file koneksi database sudah benar

// Handle Create    
if (isset($_POST['tambah'])) {
    try {
        $nomor_transaksi = 'TRX-' . strtoupper(uniqid());
        $tanggal = $_POST['tanggal_penjualan'];
        $metode = $_POST['metode_pembayaran'];
        $total = $_POST['total_bayar'];
        $status = $_POST['status'];

        // Menambahkan subtotal dan pajak (diisi 0 sementara) sesuai struktur ERD agar tidak kena constraint NOT NULL
        $stmt = $pdo->prepare("INSERT INTO penjualan (nomor_transaksi, tanggal_penjualan, metode_pembayaran, subtotal, pajak, total_bayar, status) VALUES (?, ?, ?, 0, 0, ?, ?)");
        $stmt->execute([$nomor_transaksi, $tanggal, $metode, $total, $status]);
        
        header("Location: penjualan.php");
        exit;
    } catch (PDOException $e) {
        die("Gagal menambah data: " . $e->getMessage()); // Akan memunculkan penyebab asli gagalnya insert
    }
}

// Handle Update
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE penjualan SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    header("Location: penjualan.php");
    exit;
}

// Handle Delete
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $stmt = $pdo->prepare("DELETE FROM penjualan WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: penjualan.php");
    exit;
}

// Fetch Read
$stmt = $pdo->query("SELECT * FROM penjualan ORDER BY tanggal_penjualan DESC, id DESC");
$data_penjualan = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Penjualan - Smart UMKM Assistant</title>
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
            <a href="penjualan.php" class="flex items-center px-4 py-2 bg-indigo-800 rounded-lg"><i class="fas fa-shopping-cart w-6"></i> Penjualan</a>
            <a href="produk.php" class="flex items-center px-4 py-2 hover:bg-indigo-600 rounded-lg"><i class="fas fa-box w-6"></i> Produk & Stok</a>
            <a href="ai_insights.php" class="flex items-center px-4 py-2 hover:bg-indigo-600 rounded-lg"><i class="fas fa-lightbulb w-6"></i> AI Insights</a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col overflow-y-auto">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8">
            <h1 class="text-2xl font-semibold text-gray-800">Data Penjualan</h1>
        </header>

        <div class="p-8 space-y-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-plus-circle text-indigo-600 mr-2"></i>Tambah Penjualan Baru</h3>
                <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                        <input type="date" name="tanggal_penjualan" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Metode</label>
                        <select name="metode_pembayaran" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="Tunai">Tunai</option>
                            <option value="QRIS">QRIS</option>
                            <option value="Transfer">Transfer Bank</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Total (Rp)</label>
                        <input type="number" name="total_bayar" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="selesai">Selesai</option>
                            <option value="pending">Pending</option>
                            <option value="batal">Batal</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" name="tambah" class="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700">
                            Simpan Data
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Transaksi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metode</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach($data_penjualan as $row): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($row['nomor_transaksi']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($row['tanggal_penjualan']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($row['metode_pembayaran']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-800">Rp <?= number_format($row['total_bayar'], 0, ',', '.') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <form method="POST" action="">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <select name="status" onchange="this.form.submit()" class="text-sm rounded-full px-3 py-1 font-semibold
                                        <?= $row['status'] == 'selesai' ? 'bg-green-100 text-green-800' : ($row['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                        <option value="selesai" <?= $row['status'] == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                        <option value="pending" <?= $row['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="batal" <?= $row['status'] == 'batal' ? 'selected' : '' ?>>Batal</option>
                                    </select>
                                    <input type="hidden" name="edit" value="1">
                                </form>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <a href="?hapus=<?= $row['id'] ?>" onclick="return confirm('Yakin ingin menghapus data ini?')" class="text-red-600 hover:text-red-900 bg-red-50 p-2 rounded-lg">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($data_penjualan)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">Belum ada data penjualan.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>