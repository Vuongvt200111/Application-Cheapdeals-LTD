<?php
require_once __DIR__ . '/Database.php';

class Product {
    public static function getAll() {
        $pdo = Database::getPDO();
        $prods = $pdo->query("SELECT * FROM products WHERE active=1 ORDER BY name")->fetchAll();
        return array_map([self::class, 'applyOverrides'], $prods);
    }
    
    public static function getByCode($code) {
        $pdo = Database::getPDO();
        $s = $pdo->prepare("SELECT * FROM products WHERE code=?");
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

    public static function update($code, $price, $inventory, $description = null) {
        $pdo = Database::getPDO();
        if ($description !== null) {
            $s = $pdo->prepare("UPDATE products SET price=?, inventory=?, description=? WHERE code=?");
            $s->execute([$price, $inventory, $description, $code]);
        } else {
            $s = $pdo->prepare("UPDATE products SET price=?, inventory=? WHERE code=?");
            $s->execute([$price, $inventory, $code]);
        }
        return true;
    }

    public static function add($code, $name, $description, $price, $inventory) {
        $pdo = Database::getPDO();
        $s = $pdo->prepare("INSERT INTO products (code, name, description, price, inventory) VALUES (?, ?, ?, ?, ?)");
        $s->execute([$code, $name, $description, $price, $inventory]);
        return true;
    }

    public static function delete($code) {
        $pdo = Database::getPDO();
        $s = $pdo->prepare("DELETE FROM products WHERE code=?");
        $s->execute([$code]);
        return true;
    }
}
