<?php
/**
 * simpan_produk.php
 * Proses POST untuk tambah (action=tambah) dan edit (action=edit) produk.
 */
session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: kelola_produk.php'); exit; }

require_once 'config/db.php';

// ── Helper: generate SKU dari prefix kategori ─────────────────────
function generate_sku_from_prefix(PDO $pdo, int $kategori_id): ?string {
    $stmt = $pdo->prepare("SELECT sku_prefix FROM kategori WHERE id = ?");
    $stmt->execute([$kategori_id]);
    $prefix = $stmt->fetchColumn();
    if (!$prefix) return null;

    // Cari nomor urut tertinggi untuk prefix ini
    $stmt2 = $pdo->prepare("
        SELECT sku FROM produk
        WHERE sku ~ ?
        ORDER BY sku DESC
    ");
    $stmt2->execute(['^' . $prefix . '-[0-9]+']);
    $existing = $stmt2->fetchAll(PDO::FETCH_COLUMN);

    $max_num = 0;
    foreach ($existing as $sku_existing) {
        if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)$/', $sku_existing, $m)) {
            $num = (int)$m[1];
            if ($num > $max_num) $max_num = $num;
        }
    }

    $next_num = $max_num + 1;
    $next_sku = $prefix . '-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);

    // Race condition guard
    $cek = $pdo->prepare("SELECT 1 FROM produk WHERE sku = ?");
    $cek->execute([$next_sku]);
    $attempts = 0;
    while ($cek->fetchColumn() && $attempts < 100) {
        $next_num++;
        $next_sku = $prefix . '-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
        $cek->execute([$next_sku]);
        $attempts++;
    }

    return $next_sku;
}

$action        = $_POST['action'] ?? '';
$id            = (int)($_POST['id'] ?? 0);
$sku           = trim($_POST['sku']           ?? '');
$nama_produk   = trim($_POST['nama_produk']   ?? '');
$kategori_id   = (int)($_POST['kategori_id']  ?? 0);
$harga_jual    = (float)($_POST['harga_jual'] ?? 0);
$stok_saat_ini = max(0, (int)($_POST['stok_saat_ini'] ?? 0));
$stok_minimum  = max(0, (int)($_POST['stok_minimum']  ?? 5));
$satuan        = trim($_POST['satuan']        ?? 'pcs');

// Jika SKU kosong, generate otomatis dari prefix kategori
if (!$sku && $kategori_id) {
    $sku = generate_sku_from_prefix($pdo, $kategori_id) ?? '';
}

// Jika masih kosong setelah auto-generate (kategori tanpa prefix), wajib isi manual
if (!$sku) {
    $redirect = $action === 'edit' ? "edit_produk.php?id=$id" : 'tambah_produk.php';
    $params   = http_build_query(array_filter([
        'error'         => 'required',
        'nama_produk'   => $nama_produk,
        'kategori_id'   => $kategori_id ?: null,
        'harga_jual'    => $harga_jual  ?: null,
        'stok_saat_ini' => $stok_saat_ini,
        'stok_minimum'  => $stok_minimum,
        'satuan'        => $satuan,
    ]));
    header("Location: $redirect?$params");
    exit;
}

// Validasi wajib
if (!$sku || !$nama_produk || !$kategori_id || $harga_jual <= 0) {
    $redirect = $action === 'edit' ? "edit_produk.php?id=$id" : 'tambah_produk.php';
    header("Location: $redirect?error=required");
    exit;
}

try {
    if ($action === 'tambah') {
        // Cek SKU unik
        $chk = $pdo->prepare("SELECT 1 FROM produk WHERE sku = ?");
        $chk->execute([$sku]);
        if ($chk->fetchColumn()) {
            header("Location: tambah_produk.php?error=sku_taken&sku=".urlencode($sku)."&nama_produk=".urlencode($nama_produk));
            exit;
        }

        $pdo->prepare("
            INSERT INTO produk (sku, kategori_id, nama_produk, harga_jual, stok_saat_ini, stok_minimum, satuan, diperbarui_pada)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ")->execute([$sku, $kategori_id, $nama_produk, $harga_jual, $stok_saat_ini, $stok_minimum, $satuan]);

        header('Location: kelola_produk.php?success=added');

    } elseif ($action === 'edit' && $id > 0) {
        // Cek SKU unik (boleh sama dengan milik sendiri)
        $chk = $pdo->prepare("SELECT 1 FROM produk WHERE sku = ? AND id <> ?");
        $chk->execute([$sku, $id]);
        if ($chk->fetchColumn()) {
            header("Location: edit_produk.php?id=$id&error=sku_taken");
            exit;
        }

        $pdo->prepare("
            UPDATE produk
            SET sku = ?, kategori_id = ?, nama_produk = ?, harga_jual = ?,
                stok_saat_ini = ?, stok_minimum = ?, satuan = ?, diperbarui_pada = NOW()
            WHERE id = ?
        ")->execute([$sku, $kategori_id, $nama_produk, $harga_jual, $stok_saat_ini, $stok_minimum, $satuan, $id]);

        header('Location: kelola_produk.php?success=updated');

    } else {
        header('Location: kelola_produk.php');
    }
    exit;

} catch (PDOException $e) {
    error_log('[simpan_produk] ' . $e->getMessage());
    $redirect = $action === 'edit' ? "edit_produk.php?id=$id" : 'tambah_produk.php';
    header("Location: $redirect?error=db_error");
    exit;
}