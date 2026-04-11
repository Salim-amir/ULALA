<?php
// ─── app/Controllers/InsightController.php ───

class InsightController extends BaseController
{
    public function index(): void
    {
        $insightModel  = new AiInsightModel();
        $produkModel   = new ProdukModel();
        $prediksiModel = new PrediksiPerformaModel();

        $data = [
            'pageTitle'       => 'AI Insights',
            'activeNav'       => 'insights',
            'skorPresisi'     => $insightModel->getSkorPresisi(),
            'restockInsights' => $insightModel->getRestock(),
            'promoInsights'   => $insightModel->getPromo(),
            'bundlingInsights'=> $insightModel->getBundling(),
            'topProduk'       => Seeder::topProduk(),
            'prediksi'        => $prediksiModel->getAll(),
        ];

        $this->render('insights', $data);
    }
}
