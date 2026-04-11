<?php
// ─── app/Controllers/DashboardController.php ─

class DashboardController extends BaseController
{
    public function index(): void
    {
        $produkModel    = new ProdukModel();
        $penjualanModel = new PenjualanModel();
        $insightModel   = new AiInsightModel();
        $prediksiModel  = new PrediksiPerformaModel();

        $data = [
            'pageTitle'       => 'Dashboard',
            'activeNav'       => 'dashboard',
            'produkKritis'    => $produkModel->getKritis(),
            'recentTrx'       => $penjualanModel->getRecent(5),
            'restockInsights' => $insightModel->getRestock(),
            'promoInsights'   => $insightModel->getPromo(),
            'bundlingInsights'=> $insightModel->getBundling(),
            'skorPresisi'     => $insightModel->getSkorPresisi(),
            'omzetBulanIni'   => $penjualanModel->totalOmzetBulanIni(),
            'totalTrx'        => $penjualanModel->totalTransaksiBulanIni(),
            'prediksi'        => $prediksiModel->getAll(),
            'growthForecast'  => $prediksiModel->getLatestGrowth(),
        ];

        $this->render('dashboard', $data);
    }
}
