<?php
// ─── app/Models/PrediksiPerformaModel.php ────

class PrediksiPerformaModel extends BaseModel
{
    public function getAll(): array
    {
        if ($this->mock) return Seeder::prediksiPerforma();
        return $this->query("
            SELECT * FROM prediksi_performa ORDER BY tanggal_prediksi ASC LIMIT 30");
    }

    public function getLatestGrowth(): float
    {
        $all = $this->getAll();
        if (empty($all)) return 14.2;
        return (float) end($all)['persentase_pertumbuhan'];
    }
}
