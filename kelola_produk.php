<?php

/**
 * kelola_produk.php
 * ─────────────────────────────────────────────────────────────────
 * Master data produk dengan fitur: search, filter kategori,
 * pagination, tombol Edit & Hapus, dan tombol Tambah Produk.
 *
 * Referensi tabel DB:
 *   produk   : id, sku, kategori_id, nama_produk, harga_jual,
 *              stok_saat_ini, stok_minimum, satuan, url_gambar,
 *              diperbarui_pada
 *   kategori : id, nama_kategori, deskripsi
 * ─────────────────────────────────────────────────────────────────
 */

// session_start();
// if (!isset($_SESSION['user_id'])) {
//     header('Location: login.php?error=session_expired');
//     exit;
// }

$page_title  = 'Kelola Produk';
$active_menu = 'kelola_produk';

// ── Flash messages ─────────────────────────────────────────────────
$flash_type = '';
$flash_msg  = '';
if (isset($_GET['success'])) {
    $flash_type = 'success';
    $flash_msg  = match ($_GET['success']) {
        'added'   => 'Produk baru berhasil ditambahkan.',
        'updated' => 'Data produk berhasil diperbarui.',
        'deleted' => 'Produk berhasil dihapus.',
        default   => 'Operasi berhasil.',
    };
}
if (isset($_GET['error'])) {
    $flash_type = 'danger';
    $flash_msg  = match ($_GET['error']) {
        'not_found'   => 'Produk tidak ditemukan.',
        'delete_fail' => 'Gagal menghapus produk. Produk mungkin masih terkait transaksi.',
        'db_error'    => 'Terjadi kesalahan database.',
        default       => 'Terjadi kesalahan.',
    };
}

// ── Parameter filter & pagination dari GET ─────────────────────────
$search       = trim($_GET['q']          ?? '');
$filter_kat   = trim($_GET['kategori']   ?? '');
$filter_stok  = trim($_GET['stok']       ?? '');   // 'kritis' | 'normal' | ''
$page         = max(1, (int)($_GET['page'] ?? 1));
$per_page     = 10;
$offset       = ($page - 1) * $per_page;

// ── TODO: Query DB ─────────────────────────────────────────────────

require_once 'config/db.php';

// Daftar kategori untuk dropdown filter
$kategori_list = $pdo->query("SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori")->fetchAll(PDO::FETCH_ASSOC);

// Query produk dengan filter
$where   = [];
$params  = [];

if ($search !== '') {
    $where[]  = "(p.nama_produk ILIKE :q OR p.sku ILIKE :q)";
    $params[':q'] = '%' . $search . '%';
}
if ($filter_kat !== '') {
    $where[]  = "k.nama_kategori = :kat";
    $params[':kat'] = $filter_kat;
}
if ($filter_stok === 'kritis') {
    $where[] = "p.stok_saat_ini <= p.stok_minimum";
}

$where_sql   = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$count_sql   = "SELECT COUNT(*) FROM produk p LEFT JOIN kategori k ON k.id = p.kategori_id $where_sql";
$stmt_count  = $pdo->prepare($count_sql);
$stmt_count->execute($params);
$total_produk = (int)$stmt_count->fetchColumn();

$data_sql = "
    SELECT p.id, p.sku, p.nama_produk, p.harga_jual,
           p.stok_saat_ini, p.stok_minimum, p.satuan,
           p.diperbarui_pada, k.nama_kategori
    FROM produk p
    LEFT JOIN kategori k ON k.id = p.kategori_id
    $where_sql
    ORDER BY p.nama_produk ASC
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($data_sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,   PDO::PARAM_INT);
$stmt->execute();
$produk_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_pages = (int)ceil($total_produk / $per_page);


// ── Helper: status & pill class berdasarkan stok ──────────────────
function stok_status(int $stok, int $minimum): array
{
    // Jika stok 0, bar kosong dan merah
    if ($stok <= 0) {
        return ['label' => 'Habis', 'pill' => 'pill-danger', 'color' => 'var(--danger)', 'pct' => 0];
    }

    // Jika stok di bawah atau pas di angka minimum (Kritis)
    if ($stok <= $minimum) {
        // Kita hitung persentase terhadap angka minimum (biar bar tidak terlihat penuh saat kritis)
        $pct = ($minimum > 0) ? (int)(($stok / $minimum) * 100) : 0;
        // Kita batasi pct maksimal 100 untuk bar merah ini
        return [
            'label' => 'Kritis',
            'pill'  => 'pill-danger',
            'color' => 'var(--danger)',
            'pct'   => min(100, $pct)
        ];
    }

    // Jika stok di atas minimum (Aman/Normal)
    return [
        'label' => 'Normal',
        'pill'  => 'badge-green',
        'color' => 'var(--success)',
        'pct'   => 100 // Jika aman, bar kita buat penuh saja agar kontras
    ];
}

// ── Helper: bangun URL paginasi tanpa menghilangkan filter ─────────
function paginate_url(int $p): string
{
    $params = $_GET;
    $params['page'] = $p;
    return '?' . http_build_query($params);
}

include 'layout/header.php';
?>

<div class="page-content">

    <?php if ($flash_msg): ?>
        <div id="php-flash" class="alert alert-<?= $flash_type ?>">
            <i class="fa-solid <?= $flash_type === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
            <?= htmlspecialchars($flash_msg) ?>
        </div>
    <?php endif; ?>

    <div class="form-card" style="margin-bottom:0;">

        <!-- ── Toolbar ─────────────────────────────────────────────────── -->
        <!--
      Filter search & kategori menggunakan GET form agar URL-nya shareable
      dan tombol Back/Forward browser tetap ingat filter.
    -->
        <form method="GET" action="kelola_produk.php" id="filter-form">
            <div class="table-toolbar">
                <div class="search-input-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input
                        type="text"
                        name="q"
                        id="produk-search"
                        placeholder="Cari produk, SKU, kategori..."
                        value="<?= htmlspecialchars($search) ?>"
                        oninput="debounceFilter()">
                </div>
                <div class="toolbar-right">
                    <!-- Filter Kategori -->
                    <select name="kategori" class="btn-sm" style="padding:7px 10px;cursor:pointer;" onchange="this.form.submit()">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($kategori_list as $kat): ?>
                            <option value="<?= htmlspecialchars($kat['nama_kategori']) ?>"
                                <?= $filter_kat === $kat['nama_kategori'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($kat['nama_kategori']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Filter Stok Kritis -->
                    <select name="stok" class="btn-sm" style="padding:7px 10px;cursor:pointer;" onchange="this.form.submit()">
                        <option value="">Semua Stok</option>
                        <option value="kritis" <?= $filter_stok === 'kritis' ? 'selected' : '' ?>>Stok Kritis</option>
                    </select>

                    <?php if ($search || $filter_kat || $filter_stok): ?>
                        <a href="kelola_produk.php" class="btn-sm" title="Reset filter">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>

                    <!--
            TODO: Tombol ini membuka modal atau redirect ke form tambah produk
            Contoh: href="tambah_produk.php"
          -->
                    <a href="tambah_produk.php" class="btn-add">
                        <i class="fa-solid fa-plus"></i> Tambah Produk
                    </a>
                </div>
            </div>
        </form>

        <!-- ── Tabel Produk ─────────────────────────────────────────────── -->
        <div class="table-wrap">
            <!--
        Kolom referensi:
          produk.nama_produk, produk.sku, kategori.nama_kategori,
          produk.harga_jual, produk.stok_saat_ini, produk.stok_minimum,
          produk.satuan, produk.diperbarui_pada
      -->
            <table id="produk-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>SKU</th>
                        <th>Kategori</th>
                        <th>Harga Jual</th>
                        <th>Stok</th>
                        <th>Satuan</th>
                        <th>Status</th>
                        <th>Diperbarui</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="produk-tbody">
                    <?php if (empty($produk_list)): ?>
                        <tr>
                            <td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted);">
                                <i class="fa-solid fa-box-open" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                                <?= $search || $filter_kat ? 'Produk tidak ditemukan untuk filter ini.' : 'Belum ada produk.' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($produk_list as $p):
                            $st = stok_status((int)$p['stok_saat_ini'], (int)$p['stok_minimum']);
                        ?>
                            <tr>
                                <td>
                                    <div class="td-product">
                                        <div class="td-thumb">
                                            <?php if (!empty($p['url_gambar'])): ?>
                                                <img src="<?= htmlspecialchars($p['url_gambar']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:var(--radius-sm);">
                                            <?php else: ?>
                                                <i class="fa-solid fa-box"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="td-product-info">
                                            <div class="p-name"><?= htmlspecialchars($p['nama_produk']) ?></div>
                                            <div class="p-sku"><?= htmlspecialchars($p['sku']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-family:var(--font-mono);font-size:12px;">
                                        <?= htmlspecialchars($p['sku']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($p['nama_kategori'] ?? '—') ?></td>
                                <td>
                                    <strong style="font-family:var(--font-mono);">
                                        Rp <?= number_format((float)$p['harga_jual'], 0, ',', '.') ?>
                                    </strong>
                                </td>
                                <td>
                                    <div class="stok-bar-wrap">
                                        <span style="font-weight:700; color:<?= $st['color'] ?>;">
                                            <?= (int)$p['stok_saat_ini'] ?>
                                        </span>
                                        <div class="stok-mini-bar">
                                            <div class="stok-mini-fill <?= ($st['label'] === 'Kritis' || $st['label'] === 'Habis') ? 'low' : '' ?>"
                                                style="width:<?= $st['pct'] ?>%;">
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($p['satuan']) ?></td>
                                <td>
                                    <span class="status-pill <?= $st['pill'] ?>">
                                        <?= $st['label'] ?>
                                    </span>
                                </td>
                                <td style="font-size:12px;color:var(--text-muted);">
                                    <?= date('d M Y', strtotime($p['diperbarui_pada'])) ?>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <!--
                      Edit  → edit_produk.php?id=X
                      Hapus → hapus_produk.php?id=X  (gunakan metode POST + CSRF di produksi)
                    -->
                                        <a href="edit_produk.php?id=<?= $p['id'] ?>" class="btn-edit" title="Edit produk">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <button
                                            class="btn-del"
                                            title="Hapus produk"
                                            onclick="konfirmasiHapus(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['nama_produk'])) ?>')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div><!-- /table-wrap -->

        <!-- ── Pagination & info ─────────────────────────────────────── -->
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-top:1px solid var(--border);background:var(--surface-2);flex-wrap:wrap;gap:8px;">
            <span style="font-size:12px;color:var(--text-muted);">
                Menampilkan <?= count($produk_list) ?> dari <?= number_format($total_produk) ?> produk
                <?= $search ? '(pencarian: "' . htmlspecialchars($search) . '")' : '' ?>
            </span>

            <?php if ($total_pages > 1): ?>
                <div style="display:flex;gap:4px;flex-wrap:wrap;">
                    <!-- Prev -->
                    <?php if ($page > 1): ?>
                        <a href="<?= paginate_url($page - 1) ?>" class="btn-sm">
                            <i class="fa-solid fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <!-- Nomor halaman -->
                    <?php
                    $range = 2;
                    for ($i = 1; $i <= $total_pages; $i++):
                        // Tampilkan halaman pertama, terakhir, dan sekitar halaman aktif
                        if ($i === 1 || $i === $total_pages || ($i >= $page - $range && $i <= $page + $range)):
                    ?>
                            <?php if ($i > 1 && $i < $page - $range): ?>
                                <span style="font-size:12px;padding:0 4px;align-self:center;color:var(--text-muted);">…</span>
                            <?php endif; ?>
                            <a href="<?= paginate_url($i) ?>"
                                class="btn-sm"
                                style="<?= $i === $page ? 'background:var(--primary);color:white;border-color:var(--primary);' : '' ?>">
                                <?= $i ?>
                            </a>
                            <?php if ($i < $total_pages && $i > $page + $range): ?>
                                <span style="font-size:12px;padding:0 4px;align-self:center;color:var(--text-muted);">…</span>
                            <?php endif; ?>
                    <?php endif;
                    endfor; ?>

                    <!-- Next -->
                    <?php if ($page < $total_pages): ?>
                        <a href="<?= paginate_url($page + 1) ?>" class="btn-sm">
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

    </div><!-- /form-card -->

    <!-- Hidden delete form (POST + CSRF — aman untuk produksi) -->
    <form id="form-hapus" method="POST" action="hapus_produk.php" style="display:none;">
        <input type="hidden" name="csrf_token" value="<?= /* TODO: csrf_token() */ 'placeholder_token' ?>">
        <input type="hidden" name="id" id="hapus-id">
    </form>

</div><!-- /page-content -->

<script>
    /* ── Konfirmasi hapus produk ─────────────────────────────────────── */
    function konfirmasiHapus(id, nama) {
        UlalaAlert.hapus(nama, () => {
            document.getElementById('hapus-id').value = id;
            document.getElementById('form-hapus').submit();
        });
    }

    /* ── Debounce pencarian agar tidak submit tiap keystroke ─────────── */
    let debounceTimer;

    function debounceFilter() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            document.getElementById('filter-form').submit();
        }, 500);
    }
</script>

<?php include 'layout/footer.php'; ?>