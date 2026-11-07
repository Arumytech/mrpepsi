<?php
namespace App\Core;

use PDO;

abstract class Model {
    protected PDO $db;
    protected string $table = '';
    protected string $primaryKey = 'id';

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function all(string $orderBy = 'id DESC'): array {
        $stmt = $this->db->query("SELECT * FROM `{$this->table}` ORDER BY {$orderBy}");
        return $stmt->fetchAll();
    }

    public function find($id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ? LIMIT 1");
        $stmt->execute([$id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function where(string $column, $value, string $orderBy = 'id DESC'): array {
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE `{$column}` = ? ORDER BY {$orderBy}");
        $stmt->execute([$value]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $columns = implode(', ', array_map(fn($col) => "`$col`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt = $this->db->prepare("INSERT INTO `{$this->table}` ({$columns}) VALUES ({$placeholders})");
        $stmt->execute(array_values($data));
        return (int)$this->db->lastInsertId();
    }

    public function update($id, array $data): bool {
        $fields = implode(', ', array_map(fn($col) => "`$col` = ?", array_keys($data)));
        $values = array_values($data);
        $values[] = $id;
        $stmt = $this->db->prepare("UPDATE `{$this->table}` SET {$fields} WHERE `{$this->primaryKey}` = ?");
        return $stmt->execute($values);
    }

    public function delete($id): bool {
        $stmt = $this->db->prepare("DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?");
        return $stmt->execute([$id]);
    }

    public function raw(string $sql, array $params = []): array {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
