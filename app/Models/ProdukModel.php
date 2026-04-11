<?php
// ─── app/Models/ProdukModel.php ───────────────

class ProdukModel extends BaseModel
{
    public function getAll(): array
    {
        if ($this->mock) {
            $produk   = Seeder::produk();
            $kategori = Seeder::kategori();
            $katMap   = array_column($kategori, 'nama_kategori', 'id');
            $emojiMap = Seeder::emojiMap();
            return array_map(function($p) use ($katMap, $emojiMap) {
                $p['nama_kategori'] = $katMap[$p['kategori_id']] ?? '-';
                $p['emoji']         = $emojiMap[$p['kategori_id']] ?? '📦';
                return $p;
            }, $produk);
        }
        return $this->query("
            SELECT p.*, k.nama_kategori
            FROM produk p
            LEFT JOIN kategori k ON p.kategori_id = k.id
            ORDER BY p.id
        ");
    }

    public function getById(int $id): array|false
    {
        if ($this->mock) {
            $list = array_filter(Seeder::produk(), fn($p) => $p['id'] === $id);
            $p = array_values($list)[0] ?? false;
            if ($p) {
                $kat = Seeder::kategori();
                $katMap = array_column($kat, 'nama_kategori', 'id');
                $p['nama_kategori'] = $katMap[$p['kategori_id']] ?? '-';
                $p['emoji'] = Seeder::emojiMap()[$p['kategori_id']] ?? '📦';
            }
            return $p;
        }
        return $this->queryOne("
            SELECT p.*, k.nama_kategori
            FROM produk p LEFT JOIN kategori k ON p.kategori_id = k.id
            WHERE p.id = ?", [$id]);
    }

    public function getKritis(): array
    {
        if ($this->mock) {
            $emojiMap = Seeder::emojiMap();
            $katMap   = array_column(Seeder::kategori(), 'nama_kategori', 'id');
            return array_values(array_map(
                function($p) use ($emojiMap, $katMap) {
                    $p['emoji']         = $emojiMap[$p['kategori_id']] ?? '📦';
                    $p['nama_kategori'] = $katMap[$p['kategori_id']] ?? '-';
                    $p['status']        = 'STOK KRITIS';
                    return $p;
                },
                array_filter(Seeder::produk(), fn($p) => $p['stok_saat_ini'] <= $p['stok_minimum'])
            ));
        }
        // v_produk_kritis view
        return $this->query("SELECT * FROM v_produk_kritis ORDER BY stok_saat_ini ASC");
    }

    public function search(string $q): array
    {
        if ($this->mock) {
            $all = $this->getAll();
            $q   = strtolower($q);
            return array_values(array_filter($all, fn($p) =>
                str_contains(strtolower($p['nama_produk']), $q) ||
                str_contains(strtolower($p['sku'] ?? ''), $q)
            ));
        }
        return $this->query("
            SELECT p.*, k.nama_kategori
            FROM produk p LEFT JOIN kategori k ON p.kategori_id = k.id
            WHERE LOWER(p.nama_produk) LIKE ? OR LOWER(p.sku) LIKE ?
            ORDER BY p.nama_produk LIMIT 10",
            ["%$q%", "%$q%"]
        );
    }

    public function kurangiStok(int $produkId, int $jumlah): bool
    {
        if ($this->mock) {
            // In mock mode stok dikurangi via JS state
            return true;
        }
        // Trigger trg_kurangi_stok akan otomatis dijalankan saat INSERT detail_penjualan
        return true;
    }
}
