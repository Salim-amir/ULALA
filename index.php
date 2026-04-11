<?php
/**
 * ┌─────────────────────────────────────────────┐
 * │  The Curator — Smart UMKM Assistant          │
 * │  Front Controller / Entry Point              │
 * │  Akses: http://localhost/the-curator/        │
 * └─────────────────────────────────────────────┘
 */

define('ROOT',    __DIR__);
define('APP',     ROOT . '/app');
define('VIEWS',   APP  . '/Views');

// ── Configs ───────────────────────────────────
require_once ROOT . '/config/app.php';
require_once ROOT . '/config/database.php';
require_once ROOT . '/database/seeder.php';

// ── Models ────────────────────────────────────
require_once APP . '/Models/BaseModel.php';
require_once APP . '/Models/KategoriModel.php';
require_once APP . '/Models/ProdukModel.php';
require_once APP . '/Models/PenjualanModel.php';
require_once APP . '/Models/DetailPenjualanModel.php';
require_once APP . '/Models/AiInsightModel.php';
require_once APP . '/Models/PrediksiPerformaModel.php';

// ── Controllers ───────────────────────────────
require_once APP . '/Controllers/BaseController.php';
require_once APP . '/Controllers/DashboardController.php';
require_once APP . '/Controllers/SalesController.php';
require_once APP . '/Controllers/InsightController.php';
require_once APP . '/Controllers/ReportController.php';
require_once APP . '/Controllers/ApiController.php';

// ── Router ────────────────────────────────────
require_once ROOT . '/routes/web.php';
