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

    <aside class="w-64 bg-indigo-700 text-white flex flex-col">
        <div class="h-16 flex items-center px-6 font-bold text-xl border-b border-indigo-600">
            <i class="fas fa-robot mr-2"></i> Smart UMKM
        </div>
        <nav class="flex-1 px-4 py-4 space-y-2">
            <a href="index.php" class="flex items-center px-4 py-2 bg-indigo-800 rounded-lg"><i class="fas fa-home w-6"></i> Beranda</a>
            <a href="penjualan.php" class="flex items-center px-4 py-2 hover:bg-indigo-600 rounded-lg"><i class="fas fa-shopping-cart w-6"></i> Penjualan</a>
            <a href="produk.php" class="flex items-center px-4 py-2 hover:bg-indigo-600 rounded-lg"><i class="fas fa-box w-6"></i> Produk & Stok</a>
            <a href="ai_insights.php" class="flex items-center px-4 py-2 hover:bg-indigo-600 rounded-lg"><i class="fas fa-lightbulb w-6"></i> AI Insights</a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col overflow-y-auto">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8">
            <h1 class="text-2xl font-semibold text-gray-800">Dashboard</h1>
            <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700" onclick="window.location.href='penjualan.php'">
                + Input Penjualan
            </button>
        </header>

        <div class="p-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Omzet Hari Ini</p>
                            <h3 class="text-2xl font-bold text-gray-800">Rp 1.250.000</h3>
                        </div>
                        <div class="p-3 bg-green-100 text-green-600 rounded-lg"><i class="fas fa-money-bill-wave"></i></div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Total Transaksi</p>
                            <h3 class="text-2xl font-bold text-gray-800">24</h3>
                        </div>
                        <div class="p-3 bg-blue-100 text-blue-600 rounded-lg"><i class="fas fa-shopping-bag"></i></div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Stok Menipis</p>
                            <h3 class="text-2xl font-bold text-red-600">3 Produk</h3>
                        </div>
                        <div class="p-3 bg-red-100 text-red-600 rounded-lg"><i class="fas fa-exclamation-triangle"></i></div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-800 mb-4">Grafik Penjualan (7 Hari Terakhir)</h3>
                    <canvas id="salesChart" height="100"></canvas>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-robot text-indigo-600 mr-2"></i> Smart Recommendations
                    </h3>
                    <div class="space-y-4 flex-1">
                        <div class="p-4 bg-indigo-50 rounded-lg border border-indigo-100">
                            <span class="text-xs font-bold text-indigo-700 uppercase tracking-wider">Restock</span>
                            <p class="text-sm text-gray-700 mt-1"><b>Kopi Gula Aren</b> laku tinggi minggu ini. Sisa stok: 5. Disarankan segera restock.</p>
                        </div>
                        <div class="p-4 bg-orange-50 rounded-lg border border-orange-100">
                            <span class="text-xs font-bold text-orange-700 uppercase tracking-wider">Promo</span>
                            <p class="text-sm text-gray-700 mt-1">Penjualan <b>Roti Bakar</b> turun 20%. Rekomendasi: Buat paket bundling dengan Kopi.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Inisialisasi Chart.js
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
                datasets: [{
                    label: 'Omzet (Rp)',
                    data: [500000, 700000, 600000, 900000, 1200000, 1500000, 1250000],
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    </script>
</body>
</html>