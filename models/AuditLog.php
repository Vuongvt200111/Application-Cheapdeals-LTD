<?php
require_once __DIR__ . '/Database.php';

class AuditLog {
    public static function getAll() {
        $pdo = Database::getPDO();
        return $pdo->query("SELECT actor, action, detail, created_at FROM audit_log ORDER BY id DESC LIMIT 100")->fetchAll();
    }
}
