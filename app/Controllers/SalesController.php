<?php
// ─── app/Controllers/SalesController.php ─────

class SalesController extends BaseController
{
    public function index(): void
    {
        $produkModel    = new ProdukModel();
        $penjualanModel = new PenjualanModel();

        $data = [
            'pageTitle'  => 'Input Penjualan',
            'activeNav'  => 'sales',
            'produkList' => $produkModel->getAll(),
            'recentTrx'  => $penjualanModel->getRecent(5),
            'nomorBaru'  => $penjualanModel->generateNomorTransaksi(),
        ];

        $this->render('sales', $data);
    }
}
