<?php
require_once __DIR__ . '/Database.php';

class Ticket {
    public static function getForUser($userId) {
        $pdo = Database::getPDO();
        $s = $pdo->prepare("SELECT sender, message, created_at FROM feedback WHERE user_id=? ORDER BY id");
        $s->execute([$userId]);
        return $s->fetchAll();
    }
    public static function getAllThreads() {
        $pdo = Database::getPDO();
        return $pdo->query("SELECT u.id, u.name, u.email, COUNT(f.id) messages, MAX(f.created_at) last_at FROM feedback f JOIN users u ON u.id=f.user_id GROUP BY u.id, u.name, u.email ORDER BY last_at DESC")->fetchAll();
    }
    public static function reply($userId, $sender, $message) {
        $pdo = Database::getPDO();
        $s = $pdo->prepare("INSERT INTO feedback (user_id, sender, message) VALUES (?, ?, ?)");
        $s->execute([$userId, $sender, $message]);
        return true;
    }
}
