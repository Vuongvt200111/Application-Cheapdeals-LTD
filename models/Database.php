<?php
class Database {
    public static function getPDO() {
        global $pdo;
        return $pdo;
    }
}
