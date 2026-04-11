<?php
require 'config.php';

// ─── SIMPAN TRANSAKSI ───────────────────────────────────────────────────────
if (isset($_POST['simpan_transaksi'])) {
    try {
        $pdo->beginTransaction();

        $items   = json_decode($_POST['cart_items'], true);
        $metode  = $_POST['metode_pembayaran'];
        $subtotal = 0;

        if (empty($items)) throw new Exception("Keranjang kosong.");

        // Hitung subtotal
        foreach ($items as $item) {
            $subtotal += $item['harga'] * $item['jumlah'];
        }
        $pajak      = round($subtotal * 0.0); // bisa set 0.11 kalau mau PPN
        $total_bayar = $subtotal + $pajak;
        $nomor_transaksi = 'TRX-' . strtoupper(substr(uniqid(), -6));

        // Insert header penjualan
        $stmt = $pdo->prepare("INSERT INTO penjualan (nomor_transaksi, metode_pembayaran, subtotal, pajak, total_bayar, dibuat_pada) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$nomor_transaksi, $metode, $subtotal, $pajak, $total_bayar]);
        $penjualan_id = $pdo->lastInsertId();

        // Insert detail & kurangi stok
        foreach ($items as $item) {
            // Cek stok cukup
            $cek = $pdo->prepare("SELECT stok_saat_ini FROM produk WHERE id = ?");
            $cek->execute([$item['produk_id']]);
            $stok = $cek->fetchColumn();
            if ($stok < $item['jumlah']) {
                throw new Exception("Stok '{$item['nama']}' tidak cukup (tersisa: $stok).");
            }

            // Insert detail_penjualan
            $d = $pdo->prepare("INSERT INTO detail_penjualan (penjualan_id, produk_id, jumlah, harga_satuan, subtotal_item) VALUES (?, ?, ?, ?, ?)");
            $d->execute([$penjualan_id, $item['produk_id'], $item['jumlah'], $item['harga'], $item['harga'] * $item['jumlah']]);

            // Kurangi stok
            $s = $pdo->prepare("UPDATE produk SET stok_saat_ini = stok_saat_ini - ? WHERE id = ?");
            $s->execute([$item['jumlah'], $item['produk_id']]);
        }

        $pdo->commit();
        header("Location: penjualan.php?success=" . urlencode($nomor_transaksi));
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// ─── HAPUS TRANSAKSI ────────────────────────────────────────────────────────
if (isset($_GET['hapus'])) {
    try {
        $pdo->beginTransaction();
        $id = (int)$_GET['hapus'];

        // Kembalikan stok
        $detail = $pdo->prepare("SELECT produk_id, jumlah FROM detail_penjualan WHERE penjualan_id = ?");
        $detail->execute([$id]);
        foreach ($detail->fetchAll() as $d) {
            $pdo->prepare("UPDATE produk SET stok_saat_ini = stok_saat_ini + ? WHERE id = ?")->execute([$d['jumlah'], $d['produk_id']]);
        }

        $pdo->prepare("DELETE FROM detail_penjualan WHERE penjualan_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM penjualan WHERE id = ?")->execute([$id]);
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
    }
    header("Location: penjualan.php");
    exit;
}

// ─── FETCH DATA ─────────────────────────────────────────────────────────────
$produk_list = $pdo->query("SELECT id, nama_produk, harga_jual, stok_saat_ini, satuan FROM produk WHERE stok_saat_ini > 0 ORDER BY nama_produk")->fetchAll(PDO::FETCH_ASSOC);

$riwayat = $pdo->query("
    SELECT p.*, 
           STRING_AGG(pr.nama_produk || ' x' || dp.jumlah, ', ' ORDER BY dp.id) AS items_summary
    FROM penjualan p
    LEFT JOIN detail_penjualan dp ON dp.penjualan_id = p.id
    LEFT JOIN produk pr ON pr.id = dp.produk_id
    GROUP BY p.id
    ORDER BY p.dibuat_pada DESC
    LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penjualan - Smart UMKM Assistant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-64 bg-indigo-700 text-white flex flex-col flex-shrink-0">
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

    <!-- Main -->
    <main class="flex-1 flex flex-col overflow-y-auto">
        <header class="h-16 bg-white shadow-sm flex items-center px-8">
            <h1 class="text-2xl font-semibold text-gray-800"><i class="fas fa-shopping-cart text-indigo-600 mr-2"></i>Input Penjualan</h1>
        </header>

        <div class="p-6 space-y-6">

            <?php if (isset($_GET['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg flex items-center">
                    <i class="fas fa-check-circle mr-2 text-green-600"></i>
                    Transaksi <strong class="mx-1"><?= htmlspecialchars($_GET['success']) ?></strong> berhasil disimpan & stok diperbarui!
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- ─── FORM TRANSAKSI BARU ───────────────────────────────────── -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="font-bold text-gray-800 text-lg mb-4"><i class="fas fa-plus-circle text-indigo-600 mr-2"></i>Transaksi Baru</h2>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    <!-- Kiri: pilih produk -->
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-3">Pilih Produk</h3>
                        <div class="flex gap-2 mb-3">
                            <div class="flex-1 relative">
                                <input type="text" id="search_produk" placeholder="Cari nama produk..."
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    oninput="filterProduk(this.value)" autocomplete="off">
                                <!-- Dropdown autocomplete -->
                                <div id="autocomplete_list" class="hidden absolute z-20 bg-white border border-gray-200 rounded-lg shadow-lg w-full mt-1 max-h-52 overflow-y-auto"></div>
                            </div>
                        </div>

                        <!-- Pilih dari grid produk -->
                        <div id="produk_grid" class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-64 overflow-y-auto pr-1">
                            <?php foreach ($produk_list as $p): ?>
                                <button type="button" onclick='tambahKeKeranjang(<?= json_encode($p) ?>)'
                                    class="produk-card text-left border border-gray-200 rounded-lg px-3 py-2 hover:border-indigo-400 hover:bg-indigo-50 transition text-sm"
                                    data-nama="<?= strtolower(htmlspecialchars($p['nama_produk'])) ?>">
                                    <div class="font-semibold text-gray-800 truncate"><?= htmlspecialchars($p['nama_produk']) ?></div>
                                    <div class="text-indigo-600 font-bold">Rp <?= number_format($p['harga_jual'], 0, ',', '.') ?></div>
                                    <div class="text-gray-400 text-xs">Stok: <?= $p['stok_saat_ini'] ?> <?= htmlspecialchars($p['satuan']) ?></div>
                                </button>
                            <?php endforeach; ?>
                            <?php if (empty($produk_list)): ?>
                                <p class="text-gray-400 text-sm col-span-2">Tidak ada produk dengan stok tersedia.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Kanan: keranjang -->
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-3">Keranjang Belanja</h3>

                        <div id="cart_empty" class="text-center text-gray-400 py-8">
                            <i class="fas fa-shopping-basket fa-2x mb-2 opacity-40"></i>
                            <p class="text-sm">Belum ada produk dipilih</p>
                        </div>

                        <div id="cart_list" class="space-y-2 hidden max-h-52 overflow-y-auto pr-1"></div>

                        <div id="cart_summary" class="hidden mt-4 border-t pt-4 space-y-3">
                            <div class="flex justify-between text-sm text-gray-600">
                                <span>Subtotal</span>
                                <span id="summary_subtotal" class="font-semibold">Rp 0</span>
                            </div>
                            <div class="flex justify-between text-base font-bold text-gray-800">
                                <span>Total Bayar</span>
                                <span id="summary_total" class="text-indigo-700 text-lg">Rp 0</span>
                            </div>

                            <form method="POST" action="" id="form_transaksi">
                                <input type="hidden" name="simpan_transaksi" value="1">
                                <input type="hidden" name="cart_items" id="cart_items_input">
                                <div class="flex gap-3 mt-2">
                                    <select name="metode_pembayaran" required
                                        class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">Metode Bayar...</option>
                                        <option value="Tunai">💵 Tunai</option>
                                        <option value="QRIS">📱 QRIS</option>
                                        <option value="Transfer">🏦 Transfer Bank</option>
                                    </select>
                                    <button type="button" onclick="submitTransaksi()"
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg font-semibold text-sm flex items-center gap-2 transition">
                                        <i class="fas fa-check"></i> Simpan
                                    </button>
                                </div>
                                <button type="button" onclick="clearCart()" class="mt-2 text-xs text-red-400 hover:text-red-600 w-full text-center">
                                    <i class="fas fa-times mr-1"></i>Kosongkan keranjang
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── RIWAYAT TRANSAKSI ─────────────────────────────────────── -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-bold text-gray-800"><i class="fas fa-history text-indigo-600 mr-2"></i>Riwayat Transaksi</h2>
                    <span class="text-xs text-gray-400">50 transaksi terakhir</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">No. Transaksi</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Waktu</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Item Dibeli</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Metode</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
                                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($riwayat as $r): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-5 py-3 text-sm font-mono font-semibold text-indigo-700"><?= htmlspecialchars($r['nomor_transaksi']) ?></td>
                                    <td class="px-5 py-3 text-sm text-gray-500"><?= date('d/m/Y H:i', strtotime($r['dibuat_pada'])) ?></td>
                                    <td class="px-5 py-3 text-sm text-gray-700 max-w-xs">
                                        <span class="truncate block" title="<?= htmlspecialchars($r['items_summary'] ?? '-') ?>">
                                            <?= htmlspecialchars($r['items_summary'] ?? '-') ?>
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-sm text-gray-600"><?= htmlspecialchars($r['metode_pembayaran']) ?></td>
                                    <td class="px-5 py-3 text-sm font-bold text-gray-800 text-right">Rp <?= number_format($r['total_bayar'], 0, ',', '.') ?></td>
                                    <td class="px-5 py-3 text-center">
                                        <a href="?hapus=<?= $r['id'] ?>"
                                            onclick="return confirm('Hapus transaksi <?= htmlspecialchars($r['nomor_transaksi']) ?>?\nStok produk akan dikembalikan.')"
                                            class="text-red-400 hover:text-red-600 text-sm"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($riwayat)): ?>
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-gray-400">Belum ada riwayat transaksi.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div><!-- /p-6 -->
    </main>

    <!-- ─── JAVASCRIPT KERANJANG ──────────────────────────────────────────────── -->
    <script>
        let cart = {}; // { produk_id: { nama, harga, jumlah, stok_max, satuan } }

        const produkData = <?= json_encode(array_values($produk_list)) ?>;

        // ── Filter produk grid ──────────────────────────────────────────────────────
        function filterProduk(q) {
            q = q.toLowerCase().trim();
            document.querySelectorAll('.produk-card').forEach(card => {
                card.style.display = !q || card.dataset.nama.includes(q) ? '' : 'none';
            });
        }

        // ── Tambah ke keranjang ─────────────────────────────────────────────────────
        function tambahKeKeranjang(p) {
            const id = p.id;
            if (cart[id]) {
                if (cart[id].jumlah >= cart[id].stok_max) {
                    alert('Stok ' + p.nama_produk + ' tidak mencukupi!');
                    return;
                }
                cart[id].jumlah++;
            } else {
                cart[id] = {
                    produk_id: id,
                    nama: p.nama_produk,
                    harga: parseFloat(p.harga_jual),
                    jumlah: 1,
                    stok_max: parseInt(p.stok_saat_ini),
                    satuan: p.satuan
                };
            }
            renderCart();
        }

        // ── Render keranjang ────────────────────────────────────────────────────────
        function renderCart() {
            const list = document.getElementById('cart_list');
            const empty = document.getElementById('cart_empty');
            const summary = document.getElementById('cart_summary');
            const keys = Object.keys(cart);

            if (keys.length === 0) {
                list.classList.add('hidden');
                empty.classList.remove('hidden');
                summary.classList.add('hidden');
                return;
            }

            list.classList.remove('hidden');
            empty.classList.add('hidden');
            summary.classList.remove('hidden');

            list.innerHTML = '';
            let subtotal = 0;
            keys.forEach(id => {
                const item = cart[id];
                const itemTotal = item.harga * item.jumlah;
                subtotal += itemTotal;
                list.innerHTML += `
        <div class="flex items-center gap-3 bg-gray-50 rounded-lg px-3 py-2 text-sm">
            <div class="flex-1 min-w-0">
                <div class="font-semibold text-gray-800 truncate">${item.nama}</div>
                <div class="text-gray-500 text-xs">Rp ${fmt(item.harga)} / ${item.satuan}</div>
            </div>
            <div class="flex items-center gap-1">
                <button onclick="ubahJumlah(${id}, -1)" class="w-6 h-6 rounded-full bg-gray-200 hover:bg-red-200 text-gray-700 font-bold text-xs flex items-center justify-center">−</button>
                <input type="number" min="1" max="${item.stok_max}" value="${item.jumlah}"
                    onchange="setJumlah(${id}, this.value)"
                    class="w-10 text-center border border-gray-300 rounded text-sm py-0.5 font-semibold">
                <button onclick="ubahJumlah(${id}, 1)" class="w-6 h-6 rounded-full bg-gray-200 hover:bg-green-200 text-gray-700 font-bold text-xs flex items-center justify-center">+</button>
            </div>
            <div class="text-right min-w-[70px]">
                <div class="font-bold text-indigo-700">Rp ${fmt(itemTotal)}</div>
                <button onclick="hapusItem(${id})" class="text-red-400 hover:text-red-600 text-xs"><i class="fas fa-times"></i> hapus</button>
            </div>
        </div>`;
            });

            document.getElementById('summary_subtotal').textContent = 'Rp ' + fmt(subtotal);
            document.getElementById('summary_total').textContent = 'Rp ' + fmt(subtotal);
        }

        function ubahJumlah(id, delta) {
            if (!cart[id]) return;
            const baru = cart[id].jumlah + delta;
            if (baru < 1) {
                hapusItem(id);
                return;
            }
            if (baru > cart[id].stok_max) {
                alert('Stok tidak mencukupi!');
                return;
            }
            cart[id].jumlah = baru;
            renderCart();
        }

        function setJumlah(id, val) {
            val = parseInt(val);
            if (isNaN(val) || val < 1) val = 1;
            if (val > cart[id].stok_max) {
                alert('Stok tidak mencukupi!');
                val = cart[id].stok_max;
            }
            cart[id].jumlah = val;
            renderCart();
        }

        function hapusItem(id) {
            delete cart[id];
            renderCart();
        }

        function clearCart() {
            cart = {};
            renderCart();
        }

        function fmt(n) {
            return Number(n).toLocaleString('id-ID');
        }

        // ── Submit transaksi ────────────────────────────────────────────────────────
        function submitTransaksi() {
            if (Object.keys(cart).length === 0) {
                alert('Keranjang masih kosong!');
                return;
            }
            const metode = document.querySelector('[name="metode_pembayaran"]').value;
            if (!metode) {
                alert('Pilih metode pembayaran dulu!');
                return;
            }

            const items = Object.values(cart).map(i => ({
                produk_id: i.produk_id,
                nama: i.nama,
                harga: i.harga,
                jumlah: i.jumlah
            }));
            document.getElementById('cart_items_input').value = JSON.stringify(items);
            document.getElementById('form_transaksi').submit();
        }
    </script>

</body>

</html>