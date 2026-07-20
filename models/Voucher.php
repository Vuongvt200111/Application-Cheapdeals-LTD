<?php
require_once __DIR__ . '/Database.php';

class Voucher {
    public static function getAll() {
        $pdo = Database::getPDO();
        return $pdo->query("SELECT code, discount, created_by, created_at FROM vouchers ORDER BY id DESC LIMIT 50")->fetchAll();
    }
    public static function add($code, $discount, $createdBy) {
        $pdo = Database::getPDO();
        $s = $pdo->prepare("INSERT INTO vouchers (code, discount, created_by, created_at) VALUES (?, ?, ?, NOW())");
        $s->execute([$code, $discount, $createdBy]);
        return true;
    }
}
