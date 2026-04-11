<?php
// ─── routes/web.php ───────────────────────────

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri    = rtrim(str_replace(BASE_URL, '', $uri), '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

// ── API Routes (JSON) ─────────────────────────
if (str_starts_with($uri, '/api')) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    $api = new ApiController();
    match(true) {
        $uri === '/api/produk'           => $api->produk(),
        $uri === '/api/produk/kritis'    => $api->produkKritis(),
        $uri === '/api/penjualan'        => ($method==='POST' ? $api->simpanPenjualan() : $api->listPenjualan()),
        $uri === '/api/insights'         => $api->insights(),
        $uri === '/api/insights/run'     => $api->runInsights(),
        $uri === '/api/prediksi'         => $api->prediksi(),
        $uri === '/api/dashboard/stats'  => $api->dashboardStats(),
        $uri === '/api/laporan/kategori' => $api->laporanKategori(),
        $uri === '/api/laporan/export'   => $api->exportLaporan(),
        default => (function(){ http_response_code(404); echo json_encode(['error'=>'Not Found']); })()
    };
    exit;
}

// ── Web Routes ────────────────────────────────
match($uri) {
    '/'             => (new DashboardController())->index(),
    '/dashboard'    => (new DashboardController())->index(),
    '/sales'        => (new SalesController())->index(),
    '/insights'     => (new InsightController())->index(),
    '/reports'      => (new ReportController())->index(),
    default         => (function(){
        http_response_code(404);
        echo '<h1 style="font-family:sans-serif;text-align:center;margin-top:20vh">404 — Halaman tidak ditemukan</h1>';
    })()
};
