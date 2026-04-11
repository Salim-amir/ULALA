<?php
// ─── config/database.php ──────────────────────
// Ganti value sesuai pgAdmin Anda
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'ulala_db');
define('DB_USER', getenv('DB_USER') ?: 'postgres');
define('DB_PASS', getenv('DB_PASS') ?: '12345678');

class Database
{
    private static ?PDO $pdo       = null;
    private static bool $available = false;

    public static function connect(): ?PDO
    {
        if (MOCK_MODE) return null;
        if (self::$pdo !== null) return self::$pdo;

        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', DB_HOST, DB_PORT, DB_NAME);
        try {
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            self::$available = true;
        } catch (PDOException $e) {
            self::$available = false;
            if (APP_DEBUG) error_log('[DB] ' . $e->getMessage());
        }
        return self::$pdo;
    }

    public static function isAvailable(): bool
    {
        return self::$available;
    }

    /** Panggil stored procedure PostgreSQL (hitung_ai_restock, dll) */
    public static function callProcedure(string $name): bool
    {
        $pdo = self::connect();
        if (!$pdo) return false;
        try {
            $pdo->exec("CALL $name()");
            return true;
        } catch (PDOException $e) {
            if (APP_DEBUG) error_log("[SP] $name: " . $e->getMessage());
            return false;
        }
    }
}
