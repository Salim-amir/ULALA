<?php
// ─── app/Models/DetailPenjualanModel.php ─────

class DetailPenjualanModel extends BaseModel
{
    public function simpanBulk(int $penjualanId, array $items): bool
    {
        if ($this->mock) return true; // Trigger mock: JS handles stock

        // INSERT → trigger trg_kurangi_stok otomatis jalan di DB
        foreach ($items as $item) {
            $this->execute("
                INSERT INTO detail_penjualan
                (penjualan_id, produk_id, jumlah, harga_satuan, subtotal_item)
                VALUES (?,?,?,?,?)",
                [
                    $penjualanId,
                    $item['produk_id'],
                    $item['jumlah'],
                    $item['harga_satuan'],
                    $item['subtotal_item'],
                ]
            );
        }
        return true;
    }

    public function getByPenjualan(int $penjualanId): array
    {
        if ($this->mock) {
            return array_values(array_filter(
                Seeder::detailPenjualan(),
                fn($d) => $d['penjualan_id'] === $penjualanId
            ));
        }
        return $this->query("
            SELECT dp.*, p.nama_produk, p.sku
            FROM detail_penjualan dp
            JOIN produk p ON dp.produk_id = p.id
            WHERE dp.penjualan_id = ?", [$penjualanId]);
    }
}
