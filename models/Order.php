<?php
require_once __DIR__ . '/Database.php';

class Order {
    public static function getForUser($userId) {
        $pdo = Database::getPDO();
        $s = $pdo->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY id DESC");
        $s->execute([$userId]);
        return $s->fetchAll();
    }
    public static function getAll() {
        $pdo = Database::getPDO();
        return $pdo->query("SELECT o.*, u.name customer, u.email FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.id DESC")->fetchAll();
    }
    public static function add($userId, $packageName, $total, $saved, $status = 'Paid') {
        $pdo = Database::getPDO();
        $s = $pdo->prepare("INSERT INTO orders (user_id, package_name, total, saved, status) VALUES (?, ?, ?, ?, ?)");
        $s->execute([$userId, $packageName, $total, $saved, $status]);
        return true;
    }
}
