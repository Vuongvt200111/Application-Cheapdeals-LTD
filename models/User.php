<?php
require_once __DIR__ . '/Database.php';

class User {
    public static function getById($id) {
        $pdo = Database::getPDO();
        $s = $pdo->prepare("SELECT * FROM users WHERE id=?");
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }
    public static function updateProfile($userId, $name, $address, $phone, $card, $pin = null, $avatar = null) {
        $pdo = Database::getPDO();
        $s = $pdo->prepare("UPDATE users SET name=?, address=?, phone=?, card=? WHERE id=?");
        $s->execute([$name, $address, $phone, $card, $userId]);
        if ($pin !== null && trim($pin) !== '') {
            $hash = password_hash(trim($pin), PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET payment_pin_hash=? WHERE id=?")->execute([$hash, $userId]);
        }
        if ($avatar !== null && trim($avatar) !== '') {
            $pdo->prepare("UPDATE users SET avatar=? WHERE id=?")->execute([trim($avatar), $userId]);
        }
    }
    public static function updatePoints($userId, $points) {
        $pdo = Database::getPDO();
        $s = $pdo->prepare("UPDATE users SET points=? WHERE id=?");
        $s->execute([$points, $userId]);
    }
}
