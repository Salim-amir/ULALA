<?php
// ─── app/Controllers/ApiController.php ───────

class ApiController extends BaseController
{
    // GET /api/produk
    public function produk(): void
    {
        $this->json((new ProdukModel())->getAll());
    }

    // GET /api/produk/kritis
    public function produkKritis(): void
    {
        $this->json((new ProdukModel())->getKritis());
    }

    // GET /api/penjualan
    public function listPenjualan(): void
    {
        $limit = (int)($_GET['limit'] ?? 10);
        $this->json((new PenjualanModel())->getAll($limit));
    }

    // POST /api/penjualan
    public function simpanPenjualan(): void
    {
        $body = $this->inputAll();
        $items = $body['items'] ?? [];

        if (empty($items)) {
            $this->json(['error' => 'Items tidak boleh kosong'], 422);
            return;
        }

        $subtotal = array_sum(array_map(fn($i) => (float)$i['subtotal_item'], $items));
        $pajak    = round($subtotal * PPN_RATE, 2);
        $total    = $subtotal + $pajak;

        $penjualanModel = new PenjualanModel();
        $nomorTrx       = $penjualanModel->generateNomorTransaksi();

        $trxData = [
            'nomor_transaksi'  => $nomorTrx,
            'metode_pembayaran'=> $body['metode_pembayaran'] ?? 'Tunai',
            'subtotal'         => $subtotal,
            'pajak'            => $pajak,
            'total_bayar'      => $total,
            'dibuat_pada'      => date('Y-m-d H:i:s'),
        ];

        $penjualanId = $penjualanModel->simpan($trxData);

        // INSERT detail_penjualan → trigger otomatis kurangi stok
        (new DetailPenjualanModel())->simpanBulk($penjualanId, $items);

        $this->json([
            'status'          => 'success',
            'penjualan_id'    => $penjualanId,
            'nomor_transaksi' => $nomorTrx,
            'subtotal'        => $subtotal,
            'pajak'           => $pajak,
            'total_bayar'     => $total,
        ], 201);
    }

    // GET /api/insights
    public function insights(): void
    {
        $tipe  = $_GET['tipe'] ?? '';
        $model = new AiInsightModel();
        $this->json($model->getAll($tipe));
    }

    // POST /api/insights/run
    // Menjalankan 3 stored procedure AI sekaligus
    public function runInsights(): void
    {
        $result = (new AiInsightModel())->runAllProcedures();
        $this->json($result);
    }

    // GET /api/prediksi
    public function prediksi(): void
    {
        $this->json((new PrediksiPerformaModel())->getAll());
    }

    // GET /api/dashboard/stats
    public function dashboardStats(): void
    {
        $penjualanModel = new PenjualanModel();
        $insightModel   = new AiInsightModel();
        $produkModel    = new ProdukModel();
        $prediksiModel  = new PrediksiPerformaModel();

        $this->json([
            'omzet_bulan_ini'  => $penjualanModel->totalOmzetBulanIni(),
            'total_transaksi'  => $penjualanModel->totalTransaksiBulanIni(),
            'produk_kritis'    => count($produkModel->getKritis()),
            'skor_presisi'     => $insightModel->getSkorPresisi(),
            'growth_forecast'  => $prediksiModel->getLatestGrowth(),
            'restock_count'    => count($insightModel->getRestock()),
        ]);
    }

    // GET /api/laporan/kategori
    public function laporanKategori(): void
    {
        $this->json(Seeder::kategoriPerforma());
    }

    // GET /api/laporan/export
    public function exportLaporan(): void
    {
        $format = $_GET['format'] ?? 'pdf';
        // Placeholder — implementasi export nyata bisa pakai TCPDF / PhpSpreadsheet
        $this->json([
            'status'  => 'success',
            'message' => "Export $format sedang diproses.",
            'url'     => BASE_URL . "/public/exports/laporan_{$format}_" . date('Ymd') . ".{$format}",
        ]);
    }
}
