<?php
require 'config.php';

// ─── OMZET HARI INI ──────────────────────────────────────────────────────────
$omzet_hari_ini = $pdo->query("
    SELECT COALESCE(SUM(total_bayar), 0)
    FROM penjualan
    WHERE DATE(dibuat_pada) = CURRENT_DATE
")->fetchColumn();
// ─── LABA HARI INI ──────────────────────────────────────────────────────────
$laba_hari_ini = $pdo->query("
    SELECT COALESCE(SUM(dp.jumlah * (p.harga_jual - p.harga_beli)), 0)
    FROM detail_penjualan dp
    JOIN penjualan pj ON pj.id = dp.penjualan_id
    JOIN produk p ON p.id = dp.produk_id
    WHERE DATE(pj.dibuat_pada) = CURRENT_DATE
")->fetchColumn();

// ─── TOTAL TRANSAKSI HARI INI ────────────────────────────────────────────────
$transaksi_hari_ini = $pdo->query("
    SELECT COUNT(*)
    FROM penjualan
    WHERE DATE(dibuat_pada) = CURRENT_DATE
")->fetchColumn();

// ─── OMZET KEMARIN (untuk persentase naik/turun) ─────────────────────────────
$omzet_kemarin = $pdo->query("
    SELECT COALESCE(SUM(total_bayar), 0)
    FROM penjualan
    WHERE DATE(dibuat_pada) = CURRENT_DATE - INTERVAL '1 day'
")->fetchColumn();

$persen_omzet = $omzet_kemarin > 0
    ? round((($omzet_hari_ini - $omzet_kemarin) / $omzet_kemarin) * 100, 1)
    : ($omzet_hari_ini > 0 ? 100 : 0);

// ─── STOK MENIPIS ────────────────────────────────────────────────────────────
$stok_menipis = $pdo->query("
    SELECT COUNT(*) FROM produk WHERE stok_saat_ini <= stok_minimum
")->fetchColumn();

// ─── OMZET 7 HARI TERAKHIR (untuk grafik) ────────────────────────────────────
$grafik_raw = $pdo->query("
    SELECT 
        TO_CHAR(DATE(dibuat_pada), 'DD/MM') AS tanggal,
        COALESCE(SUM(total_bayar), 0) AS omzet,
        COUNT(*) AS jumlah_trx
    FROM penjualan
    WHERE dibuat_pada >= CURRENT_DATE - INTERVAL '6 days'
    GROUP BY DATE(dibuat_pada)
    ORDER BY DATE(dibuat_pada) ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Pastikan semua 7 hari ada (isi 0 kalau tidak ada transaksi)
$grafik_labels = [];
$grafik_omzet  = [];
$grafik_trx    = [];
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('d/m', strtotime("-$i days"));
    $grafik_labels[] = $tgl;
    $found = array_filter($grafik_raw, fn($r) => $r['tanggal'] === $tgl);
    $found = array_values($found);
    $grafik_omzet[]  = $found ? (float)$found[0]['omzet'] : 0;
    $grafik_trx[]    = $found ? (int)$found[0]['jumlah_trx'] : 0;
}

// ─── PRODUK TERLARIS (TOP 5, 30 HARI) ────────────────────────────────────────
$produk_terlaris = $pdo->query("
    SELECT p.nama_produk, SUM(dp.jumlah) AS total_terjual, SUM(dp.subtotal_item) AS total_omzet
    FROM detail_penjualan dp
    JOIN produk p ON p.id = dp.produk_id
    JOIN penjualan pj ON pj.id = dp.penjualan_id
    WHERE pj.dibuat_pada >= NOW() - INTERVAL '30 days'
    GROUP BY p.id, p.nama_produk
    ORDER BY total_terjual DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// ─── TRANSAKSI TERBARU ────────────────────────────────────────────────────────
$transaksi_terbaru = $pdo->query("
    SELECT p.nomor_transaksi, p.total_bayar, p.metode_pembayaran, p.dibuat_pada,
           STRING_AGG(pr.nama_produk || ' x' || dp.jumlah, ', ' ORDER BY dp.id) AS items
    FROM penjualan p
    LEFT JOIN detail_penjualan dp ON dp.penjualan_id = p.id
    LEFT JOIN produk pr ON pr.id = dp.produk_id
    GROUP BY p.id
    ORDER BY p.dibuat_pada DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// ─── PRODUK STOK MENIPIS ─────────────────────────────────────────────────────
$produk_kritis = $pdo->query("
    SELECT nama_produk, stok_saat_ini, stok_minimum, satuan
    FROM produk
    WHERE stok_saat_ini <= stok_minimum
    ORDER BY stok_saat_ini ASC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// ─── AI INSIGHTS TERBARU ─────────────────────────────────────────────────────
$insights_terbaru = $pdo->query("
    SELECT a.tipe_insight, a.pesan_rekomendasi, p.nama_produk
    FROM ai_insights a
    LEFT JOIN produk p ON a.produk_id = p.id
    ORDER BY a.skor_presisi DESC
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

// ─── OMZET BULAN INI ─────────────────────────────────────────────────────────
$omzet_bulan = $pdo->query("
    SELECT COALESCE(SUM(total_bayar), 0)
    FROM penjualan
    WHERE DATE_TRUNC('month', dibuat_pada) = DATE_TRUNC('month', CURRENT_DATE)
")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - Smart UMKM Assistant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-64 bg-indigo-700 text-white flex flex-col flex-shrink-0">
        <div class="h-16 flex items-center px-6 font-bold text-xl border-b border-indigo-600">
            <i class="fas fa-robot mr-2"></i> Smart UMKM
        </div>
        <nav class="flex-1 px-4 py-4 space-y-2">
            <a href="index.php" class="flex items-center px-4 py-2 bg-indigo-800 rounded-lg"><i class="fas fa-home w-6"></i> Beranda</a>
            <a href="penjualan.php" class="flex items-center px-4 py-2 hover:bg-indigo-600 rounded-lg"><i class="fas fa-shopping-cart w-6"></i> Penjualan</a>
            <a href="produk.php" class="flex items-center px-4 py-2 hover:bg-indigo-600 rounded-lg"><i class="fas fa-box w-6"></i> Produk & Stok</a>
            <a href="ai_insights.php" class="flex items-center px-4 py-2 hover:bg-indigo-600 rounded-lg"><i class="fas fa-lightbulb w-6"></i> AI Insights</a>
        </nav>
        <div class="px-4 pb-4 text-xs text-indigo-300 text-center">
            <?= date('d F Y, H:i') ?> WIB
        </div>
    </aside>

    <!-- Main -->
    <main class="flex-1 flex flex-col overflow-y-auto">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800">Dashboard</h1>
                <p class="text-xs text-gray-400">Data diperbarui setiap kali halaman dibuka</p>
            </div>
            <a href="penjualan.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold flex items-center gap-2 transition">
                <i class="fas fa-plus"></i> Input Penjualan
            </a>
        </header>

        <div class="p-6 space-y-6">

            <!-- ─── KARTU STATISTIK ──────────────────────────────────────── -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                <!-- Omzet Hari Ini -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Omzet Hari Ini</p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1">
                                Rp <?= number_format($omzet_hari_ini, 0, ',', '.') ?>
                            </h3>
                            <p class="text-xs mt-1 <?= $persen_omzet >= 0 ? 'text-green-600' : 'text-red-500' ?>">
                                <i class="fas fa-arrow-<?= $persen_omzet >= 0 ? 'up' : 'down' ?>"></i>
                                <?= abs($persen_omzet) ?>% vs kemarin
                            </p>
                        </div>
                        <div class="p-3 bg-green-100 text-green-600 rounded-lg text-lg"><i class="fas fa-money-bill-wave"></i></div>
                    </div>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">

                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Laba/Rugi Hari Ini</p>
                            <h3 class="text-2xl font-bold mt-1 <?= $laba_hari_ini >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                                <?= $laba_hari_ini < 0 ? '-' : '' ?>Rp <?= number_format(abs($laba_hari_ini), 0, ',', '.') ?>
                            </h3>
                            <p class="text-xs <?= $laba_hari_ini >= 0 ? 'text-green-500' : 'text-red-500' ?> mt-1">
                                Keuntungan bersih
                            </p>
                        </div>
                        <div class="p-3 <?= $laba_hari_ini >= 0 ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' ?> rounded-lg text-lg">
                            <i class="fas fa-<?= $laba_hari_ini >= 0 ? 'wallet' : 'arrow-trend-down' ?>"></i>
                        </div>
                    </div>
                </div>
                <!-- Transaksi Hari Ini -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Transaksi Hari Ini</p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= $transaksi_hari_ini ?></h3>
                            <p class="text-xs text-gray-400 mt-1">transaksi masuk</p>
                        </div>
                        <div class="p-3 bg-blue-100 text-blue-600 rounded-lg text-lg"><i class="fas fa-shopping-bag"></i></div>
                    </div>
                </div>

                <!-- Omzet Bulan Ini -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Omzet Bulan Ini</p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1">
                                Rp <?= number_format($omzet_bulan, 0, ',', '.') ?>
                            </h3>
                            <p class="text-xs text-gray-400 mt-1"><?= date('F Y') ?></p>
                        </div>
                        <div class="p-3 bg-purple-100 text-purple-600 rounded-lg text-lg"><i class="fas fa-chart-line"></i></div>
                    </div>
                </div>

                <!-- Stok Menipis -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Stok Menipis</p>
                            <h3 class="text-2xl font-bold mt-1 <?= $stok_menipis > 0 ? 'text-red-600' : 'text-green-600' ?>">
                                <?= $stok_menipis ?> Produk
                            </h3>
                            <p class="text-xs mt-1 <?= $stok_menipis > 0 ? 'text-red-400' : 'text-green-400' ?>">
                                <?= $stok_menipis > 0 ? 'Perlu segera restock' : 'Semua stok aman' ?>
                            </p>
                        </div>
                        <div class="p-3 <?= $stok_menipis > 0 ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600' ?> rounded-lg text-lg">
                            <i class="fas fa-<?= $stok_menipis > 0 ? 'exclamation-triangle' : 'check-circle' ?>"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── GRAFIK + PRODUK TERLARIS ─────────────────────────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Grafik 7 Hari -->
                <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-gray-800">Grafik Omzet (7 Hari Terakhir)</h3>
                        <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-md">Real-time dari DB</span>
                    </div>
                    <canvas id="salesChart" height="110"></canvas>
                    <?php if (array_sum($grafik_omzet) == 0): ?>
                        <p class="text-center text-gray-400 text-sm mt-4">Belum ada data penjualan 7 hari terakhir.</p>
                    <?php endif; ?>
                </div>

                <!-- Produk Terlaris -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-fire text-orange-500 mr-2"></i>Terlaris 30 Hari</h3>
                    <?php if (empty($produk_terlaris)): ?>
                        <p class="text-gray-400 text-sm text-center py-4">Belum ada data penjualan.</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php
                            $max = $produk_terlaris[0]['total_terjual'] ?: 1;
                            foreach ($produk_terlaris as $i => $p):
                                $pct = round(($p['total_terjual'] / $max) * 100);
                            ?>
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="font-medium text-gray-700 truncate max-w-[150px]" title="<?= htmlspecialchars($p['nama_produk']) ?>">
                                            <?= $i === 0 ? '🥇' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : '·')) ?>
                                            <?= htmlspecialchars($p['nama_produk']) ?>
                                        </span>
                                        <span class="text-gray-500 font-semibold ml-2 whitespace-nowrap"><?= $p['total_terjual'] ?>x</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-1.5">
                                        <div class="bg-indigo-500 h-1.5 rounded-full" style="width:<?= $pct ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ─── TRANSAKSI TERBARU + STOK KRITIS + AI INSIGHT ────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Transaksi Terbaru -->
                <div class="lg:col-span-1 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-bold text-gray-800"><i class="fas fa-receipt text-indigo-500 mr-2"></i>Transaksi Terbaru</h3>
                        <a href="penjualan.php" class="text-xs text-indigo-500 hover:underline">Lihat semua</a>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <?php if (empty($transaksi_terbaru)): ?>
                            <p class="text-gray-400 text-sm text-center py-6">Belum ada transaksi.</p>
                        <?php endif; ?>
                        <?php foreach ($transaksi_terbaru as $t): ?>
                            <div class="px-5 py-3 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-mono font-semibold text-indigo-600"><?= htmlspecialchars($t['nomor_transaksi']) ?></p>
                                    <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($t['items'] ?? '-') ?></p>
                                    <p class="text-xs text-gray-400"><?= date('d/m H:i', strtotime($t['dibuat_pada'])) ?> · <?= htmlspecialchars($t['metode_pembayaran']) ?></p>
                                </div>
                                <span class="text-sm font-bold text-gray-800 whitespace-nowrap">Rp <?= number_format($t['total_bayar'], 0, ',', '.') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Stok Kritis -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-bold text-gray-800"><i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>Stok Kritis</h3>
                        <a href="produk.php" class="text-xs text-indigo-500 hover:underline">Restock</a>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <?php if (empty($produk_kritis)): ?>
                            <div class="px-5 py-6 text-center">
                                <i class="fas fa-check-circle text-green-400 text-2xl mb-1"></i>
                                <p class="text-gray-400 text-sm">Semua stok aman!</p>
                            </div>
                        <?php endif; ?>
                        <?php foreach ($produk_kritis as $pk): ?>
                            <div class="px-5 py-3 flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($pk['nama_produk']) ?></p>
                                    <p class="text-xs text-gray-400">min: <?= $pk['stok_minimum'] ?> <?= htmlspecialchars($pk['satuan']) ?></p>
                                </div>
                                <span class="text-sm font-bold <?= $pk['stok_saat_ini'] == 0 ? 'text-red-600 bg-red-100' : 'text-orange-600 bg-orange-100' ?> px-2 py-0.5 rounded-full">
                                    <?= $pk['stok_saat_ini'] == 0 ? 'HABIS' : $pk['stok_saat_ini'] . ' ' . htmlspecialchars($pk['satuan']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- AI Insights Preview -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-bold text-gray-800"><i class="fas fa-lightbulb text-yellow-500 mr-2"></i>AI Insights</h3>
                        <a href="ai_insights.php" class="text-xs text-indigo-500 hover:underline">Analisis baru</a>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <?php if (empty($insights_terbaru)): ?>
                            <div class="px-5 py-6 text-center">
                                <i class="fas fa-robot text-gray-300 text-2xl mb-1"></i>
                                <p class="text-gray-400 text-sm">Belum ada insight.</p>
                                <a href="ai_insights.php" class="text-xs text-indigo-500 hover:underline mt-1 block">Klik untuk analisis →</a>
                            </div>
                        <?php endif; ?>
                        <?php foreach ($insights_terbaru as $ins):
                            $warna = match ($ins['tipe_insight']) {
                                'Restock'  => 'bg-red-50 border-red-200 text-red-700',
                                'Promo'    => 'bg-blue-50 border-blue-200 text-blue-700',
                                'Trending' => 'bg-green-50 border-green-200 text-green-700',
                                default    => 'bg-purple-50 border-purple-200 text-purple-700',
                            };
                        ?>
                            <div class="px-5 py-3">
                                <span class="text-xs font-bold uppercase px-2 py-0.5 rounded-md border <?= $warna ?>">
                                    <?= htmlspecialchars($ins['tipe_insight']) ?>
                                </span>
                                <p class="text-sm text-gray-700 mt-1.5 leading-relaxed line-clamp-2">
                                    <?= htmlspecialchars($ins['pesan_rekomendasi']) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script>
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($grafik_labels) ?>,
                datasets: [{
                        label: 'Omzet (Rp)',
                        data: <?= json_encode($grafik_omzet) ?>,
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.08)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#4f46e5',
                        pointRadius: 4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Transaksi',
                        data: <?= json_encode($grafik_trx) ?>,
                        borderColor: '#f59e0b',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        borderDash: [4, 3],
                        tension: 0.4,
                        pointBackgroundColor: '#f59e0b',
                        pointRadius: 3,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ctx.datasetIndex === 0 ?
                                ' Omzet: Rp ' + ctx.raw.toLocaleString('id-ID') :
                                ' Transaksi: ' + ctx.raw + 'x'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        ticks: {
                            callback: v => 'Rp ' + (v >= 1000000 ? (v / 1000000).toFixed(1) + 'jt' : (v / 1000) + 'rb'),
                            font: {
                                size: 10
                            }
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        },
                        ticks: {
                            stepSize: 1,
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });
    </script>

</body>

</html>