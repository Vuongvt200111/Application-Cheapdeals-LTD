<?php
require_once __DIR__ . '/Database.php';

class Wishlist {
    public static function getForUser($userId) {
        $pdo = Database::getPDO();
        $s = $pdo->prepare("SELECT package_code FROM wishlist WHERE user_id=?");
        $s->execute([$userId]);
        return $s->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }
    public static function toggle($userId, $code) {
        $pdo = Database::getPDO();
        $s = $pdo->prepare("SELECT id FROM wishlist WHERE user_id=? AND package_code=?");
        $s->execute([$userId, $code]);
        if ($s->fetch()) {
            $s2 = $pdo->prepare("DELETE FROM wishlist WHERE user_id=? AND package_code=?");
            $s2->execute([$userId, $code]);
            return 'removed';
        } else {
            $s2 = $pdo->prepare("INSERT INTO wishlist (user_id, package_code) VALUES (?, ?)");
            $s2->execute([$userId, $code]);
            return 'added';
        }
    }
}
