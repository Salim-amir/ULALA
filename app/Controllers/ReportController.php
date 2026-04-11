<?php
// ─── app/Controllers/ReportController.php ────

class ReportController extends BaseController
{
    public function index(): void
    {
        $penjualanModel = new PenjualanModel();
        $prediksiModel  = new PrediksiPerformaModel();

        $data = [
            'pageTitle'      => 'Laporan & Ekspor',
            'activeNav'      => 'reports',
            'omzetBulanIni'  => $penjualanModel->totalOmzetBulanIni(),
            'totalTrx'       => $penjualanModel->totalTransaksiBulanIni(),
            'growthForecast' => $prediksiModel->getLatestGrowth(),
            'kategoriPerf'   => Seeder::kategoriPerforma(),
            'prediksi'       => $prediksiModel->getAll(),
        ];

        $this->render('reports', $data);
    }
}
