<?php
// ─── app/Models/BaseModel.php ─────────────────

abstract class BaseModel
{
    protected ?PDO $db;
    protected bool $mock;

    public function __construct()
    {
        $this->mock = MOCK_MODE;
        $this->db   = $this->mock ? null : Database::connect();
    }

    protected function query(string $sql, array $params = []): array
    {
        if (!$this->db) return [];
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function queryOne(string $sql, array $params = []): array|false
    {
        if (!$this->db) return false;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    protected function execute(string $sql, array $params = []): int
    {
        if (!$this->db) return 0;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $this->db->lastInsertId();
    }
}
