<?php
// ─── config/app.php ───────────────────────────
define('APP_NAME',    'The Curator');
define('APP_TAGLINE', 'Editorial Intelligence');
define('APP_VERSION', '2.0.0');
define('APP_ENV',     getenv('APP_ENV') ?: 'development');
define('APP_DEBUG',   APP_ENV === 'development');

// Base URL — sesuaikan jika folder berbeda
define('BASE_URL', '/the-curator');

// PPN
define('PPN_RATE', 0.11);

// MOCK_MODE = true  → gunakan seeder (tanpa DB)
// MOCK_MODE = false → koneksi ke PostgreSQL pgAdmin
define('MOCK_MODE', false);

date_default_timezone_set('Asia/Jakarta');

if (APP_DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
