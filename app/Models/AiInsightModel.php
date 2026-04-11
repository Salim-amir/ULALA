<?php
// ─── app/Models/AiInsightModel.php ───────────

class AiInsightModel extends BaseModel
{
    public function getAll(string $tipe = ''): array
    {
        if ($this->mock) {
            $all     = Seeder::aiInsights();
            $produk  = Seeder::produk();
            $pMap    = array_column($produk, null, 'id');
            $emojiMap = Seeder::emojiMap();

            $all = array_map(function($i) use ($pMap, $emojiMap) {
                $p = $pMap[$i['produk_id']] ?? [];
                $i['nama_produk']  = $p['nama_produk']  ?? 'Produk';
                $i['sku']          = $p['sku']           ?? '-';
                $i['stok_saat_ini']= $p['stok_saat_ini'] ?? 0;
                $i['stok_minimum'] = $p['stok_minimum']  ?? 0;
                $i['emoji']        = $emojiMap[$p['kategori_id'] ?? 0] ?? '📦';
                return $i;
            }, $all);

            if ($tipe) {
                return array_values(array_filter($all, fn($i) => $i['tipe_insight'] === $tipe));
            }
            return $all;
        }

        $sql = "SELECT ai.*, p.nama_produk, p.sku, p.stok_saat_ini, p.stok_minimum
                FROM ai_insights ai JOIN produk p ON ai.produk_id = p.id
                WHERE ai.status = 'aktif'" .
               ($tipe ? " AND ai.tipe_insight = '$tipe'" : '') .
               " ORDER BY ai.dibuat_pada DESC";
        return $this->query($sql);
    }

    public function getRestock(): array  { return $this->getAll('RESTOCK'); }
    public function getPromo(): array    { return $this->getAll('PROMO'); }
    public function getBundling(): array { return $this->getAll('BUNDLING'); }

    public function getSkorPresisi(): float
    {
        if ($this->mock) return 98.40;
        $row = $this->queryOne("SELECT AVG(skor_presisi) AS avg FROM ai_insights WHERE status='aktif'");
        return round((float)($row['avg'] ?? 98.4), 1);
    }

    /**
     * Panggil stored procedure AI dari DB (hanya jika tidak mock)
     * Procedures: hitung_ai_restock, hitung_ai_slow_moving, hitung_ai_bundling
     */
    public function runAllProcedures(): array
    {
        if ($this->mock) {
            return ['status'=>'ok','message'=>'Analisis AI selesai (mode demo).','updated'=>9];
        }
        $results = [];
        foreach (['hitung_ai_restock','hitung_ai_slow_moving','hitung_ai_bundling'] as $proc) {
            $ok = Database::callProcedure($proc);
            $results[$proc] = $ok ? 'sukses' : 'gagal';
        }
        $total = count($this->getAll());
        return ['status'=>'ok','message'=>'3 prosedur AI telah dijalankan.','updated'=>$total,'detail'=>$results];
    }
}
