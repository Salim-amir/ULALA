<?php
// ─── app/Models/KategoriModel.php ────────────

class KategoriModel extends BaseModel
{
    public function getAll(): array
    {
        if ($this->mock) return Seeder::kategori();
        return $this->query("SELECT * FROM kategori ORDER BY id");
    }
}
