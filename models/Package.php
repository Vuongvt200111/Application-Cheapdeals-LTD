<?php
require_once __DIR__ . '/Database.php';

class Package {
    public static function getAll() {
        $pdo = Database::getPDO();
        $pkgs = $pdo->query("SELECT * FROM packages WHERE active=1 ORDER BY category, price")->fetchAll();
        return array_map([self::class, 'applyOverrides'], $pkgs);
    }
    
    public static function getByCode($code) {
        $pdo = Database::getPDO();
        $s = $pdo->prepare("SELECT * FROM packages WHERE code=?");
        $s->execute([$code]);
        $p = $s->fetch();
        return $p ? self::applyOverrides($p) : null;
    }

    public static function applyOverrides($p) {
        global $CFG;
        $code = $p['code'];
        if (isset($CFG['stats_overrides'][$code])) {
            foreach ($CFG['stats_overrides'][$code] as $k => $v) {
                $p[$k] = $v;
            }
        }
        return $p;
    }

    public static function update($code, $price, $inventory) {
        $pdo = Database::getPDO();
        $s = $pdo->prepare("UPDATE packages SET price=?, inventory=? WHERE code=?");
        $s->execute([$price, $inventory, $code]);
        return true;
    }

    public static function add($code, $name, $category, $tier, $price, $features, $inventory, $unit = 'month') {
        $pdo = Database::getPDO();
        try {
            $pdo->exec("ALTER TABLE packages ADD COLUMN unit VARCHAR(30) DEFAULT NULL");
        } catch (Throwable $t) {}
        $s = $pdo->prepare("INSERT INTO packages (code, name, category, tier, price, features, inventory, unit) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $s->execute([$code, $name, $category, $tier, $price, $features, $inventory, $unit]);
        return true;
    }

    public static function delete($code) {
        $pdo = Database::getPDO();
        $s = $pdo->prepare("UPDATE packages SET active=0 WHERE code=?");
        $s->execute([$code]);
        return true;
    }
}
