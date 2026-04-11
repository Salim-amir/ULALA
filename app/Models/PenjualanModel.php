<?php
// ─── app/Models/PenjualanModel.php ───────────

class PenjualanModel extends BaseModel
{
    private static array $mockStore = []; // session-runtime mock storage

    public function getAll(int $limit = 20): array
    {
        if ($this->mock) {
            $all = array_merge(self::$mockStore, Seeder::penjualan());
            usort($all, fn($a,$b) => strcmp($b['dibuat_pada'], $a['dibuat_pada']));
            return array_slice($all, 0, $limit);
        }
        return $this->query("
            SELECT * FROM penjualan
            ORDER BY dibuat_pada DESC LIMIT ?", [$limit]);
    }

    public function getRecent(int $limit = 5): array
    {
        return array_slice($this->getAll(50), 0, $limit);
    }

    public function simpan(array $data): int
    {
        if ($this->mock) {
            $id = count(self::$mockStore) + count(Seeder::penjualan()) + 1;
            self::$mockStore[] = array_merge($data, ['id' => $id]);
            return $id;
        }
        $sql = "INSERT INTO penjualan
                (nomor_transaksi, metode_pembayaran, subtotal, pajak, total_bayar)
                VALUES (?,?,?,?,?)
                RETURNING id";
        $row = $this->queryOne($sql, [
            $data['nomor_transaksi'],
            $data['metode_pembayaran'],
            $data['subtotal'],
            $data['pajak'],
            $data['total_bayar'],
        ]);
        return (int)($row['id'] ?? 0);
    }

    public function totalOmzetBulanIni(): float
    {
        if ($this->mock) {
            return array_sum(array_column(Seeder::penjualan(), 'total_bayar'));
        }
        $row = $this->queryOne("
            SELECT COALESCE(SUM(total_bayar),0) AS total FROM penjualan
            WHERE DATE_TRUNC('month', dibuat_pada) = DATE_TRUNC('month', NOW())");
        return (float)($row['total'] ?? 0);
    }

    public function totalTransaksiBulanIni(): int
    {
        if ($this->mock) return count(Seeder::penjualan());
        $row = $this->queryOne("
            SELECT COUNT(*) AS total FROM penjualan
            WHERE DATE_TRUNC('month', dibuat_pada) = DATE_TRUNC('month', NOW())");
        return (int)($row['total'] ?? 0);
    }

    public function generateNomorTransaksi(): string
    {
        $latest = $this->getAll(1);
        if (empty($latest)) return '#TRX-99422';
        preg_match('/#TRX-(\d+)/', $latest[0]['nomor_transaksi'] ?? '', $m);
        return '#TRX-' . ((int)($m[1] ?? 99421) + 1);
    }
}
