<?php
require 'config.php';

// Generate Smart AI Insights (Rule-Based)
if (isset($_POST['generate_ai'])) {
    try {
        // Bersihkan data insight lama (untuk demo/refresh)
        $pdo->query("DELETE FROM ai_insights");

        // Rule 1: Restock (Stok menipis <= batas minimum)
        $stmt = $pdo->query("SELECT id, nama_produk, stok_saat_ini, stok_minimum FROM produk WHERE stok_saat_ini <= stok_minimum");
        while ($row = $stmt->fetch()) {
            $pesan = "Peringatan Stok! " . $row['nama_produk'] . " tersisa " . $row['stok_saat_ini'] . ". Segera restock untuk mengamankan potensi penjualan hari esok.";
            $insert = $pdo->prepare("INSERT INTO ai_insights (produk_id, tipe_insight, pesan_rekomendasi, skor_presisi, status) VALUES (?, 'Restock', ?, 95.50, '0')");
            $insert->execute([$row['id'], $pesan]);
        }

        // Rule 2: Promo (Stok menumpuk, lambat terjual - asumsikan stok > 3x batas minimum)
        $stmt = $pdo->query("SELECT id, nama_produk, stok_saat_ini, stok_minimum FROM produk WHERE stok_saat_ini > (stok_minimum * 3) AND stok_minimum > 0");
        while ($row = $stmt->fetch()) {
            $pesan = "Analisis lambat: " . $row['nama_produk'] . " memiliki stok tinggi (" . $row['stok_saat_ini'] . "). Rekomendasi: Buat paket bundling atau diskon 15% akhir pekan ini.";
            $insert = $pdo->prepare("INSERT INTO ai_insights (produk_id, tipe_insight, pesan_rekomendasi, skor_presisi, status) VALUES (?, 'Promo', ?, 82.00, '0')");
            $insert->execute([$row['id'], $pesan]);
        }

        header("Location: ai_insights.php?success=1");
        exit;
    } catch (PDOException $e) {
        $error = "Gagal memproses AI: " . $e->getMessage();
    }
}

// Fetch Data Insights
try {
    $stmt = $pdo->query("SELECT a.*, p.nama_produk FROM ai_insights a LEFT JOIN produk p ON a.produk_id = p.id ORDER BY a.skor_presisi DESC, a.dibuat_pada DESC");
    $data_insights = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $data_insights = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Insights - Smart UMKM Assistant</title>
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
            <a href="produk.php" class="flex items-center px-4 py-2 hover:bg-indigo-600 rounded-lg"><i class="fas fa-box w-6"></i> Produk & Stok</a>
            <a href="ai_insights.php" class="flex items-center px-4 py-2 bg-indigo-800 rounded-lg"><i class="fas fa-lightbulb w-6"></i> AI Insights</a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col overflow-y-auto">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8">
            <h1 class="text-2xl font-semibold text-gray-800">Smart AI Insights</h1>
            <form method="POST" action="">
                <button type="submit" name="generate_ai" class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-5 py-2 rounded-lg text-sm font-bold shadow-md hover:from-purple-700 hover:to-indigo-700 transition flex items-center">
                    <i class="fas fa-magic mr-2"></i> Analisis Data Sekarang
                </button>
            </form>
        </header>

        <div class="p-8 space-y-6">
            <?php if(isset($_GET['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><i class="fas fa-check-circle mr-2"></i>Analisis AI berhasil diperbarui berdasarkan data terbaru.</span>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach($data_insights as $row): ?>
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-1 
                            <?= $row['tipe_insight'] == 'Restock' ? 'bg-red-500' : ($row['tipe_insight'] == 'Promo' ? 'bg-blue-500' : 'bg-green-500') ?>">
                        </div>
                        
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider px-2 py-1 rounded-md 
                                    <?= $row['tipe_insight'] == 'Restock' ? 'bg-red-100 text-red-700' : ($row['tipe_insight'] == 'Promo' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700') ?>">
                                    <?= htmlspecialchars($row['tipe_insight']) ?>
                                </span>
                            </div>
                            <div class="text-xs font-semibold text-gray-400 flex items-center">
                                <i class="fas fa-bullseye mr-1"></i> Skor: <?= floatval($row['skor_presisi']) ?>%
                            </div>
                        </div>

                        <h3 class="font-bold text-gray-800 text-lg mb-2"><?= htmlspecialchars($row['nama_produk'] ?? 'Insight Sistem') ?></h3>
                        <p class="text-gray-600 text-sm flex-1 leading-relaxed">
                            <?= htmlspecialchars($row['pesan_rekomendasi']) ?>
                        </p>
                        
                        <div class="mt-5 pt-4 border-t border-gray-100 flex justify-between items-center">
                            <span class="text-xs text-gray-400"><i class="far fa-clock mr-1"></i> <?= date('d M Y H:i', strtotime($row['dibuat_pada'] ?? 'now')) ?></span>
                            <button class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Terapkan <i class="fas fa-arrow-right ml-1"></i></button>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if(empty($data_insights)): ?>
                    <div class="col-span-full bg-white p-10 rounded-xl shadow-sm border border-gray-100 text-center">
                        <div class="text-gray-400 mb-4"><i class="fas fa-robot fa-4x opacity-50"></i></div>
                        <h3 class="text-lg font-bold text-gray-700">Belum ada insight.</h3>
                        <p class="text-gray-500">Klik tombol "Analisis Data Sekarang" di pojok kanan atas untuk membiarkan AI menganalisis performa toko Anda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

</body>
</html>