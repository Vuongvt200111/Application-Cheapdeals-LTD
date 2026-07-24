<?php
/**
 * v4 schema bootstrap — FR17, FR22, FR29, FR34, FR35 (Lê Dương Phú)
 * Uses the same "auto-create on first load" convention v3 already used
 * for audit_log / vouchers in includes/auth.php, so no manual DB import
 * is needed. Safe to require_once from every page that needs the new
 * tables/columns — everything below is idempotent.
 *
 * Requires: $pdo (PDO connection) already available, i.e. include this
 * AFTER includes/auth.php.
 */

if (!function_exists('ensureColumn')) {
  function ensureColumn(PDO $pdo, string $table, string $col, string $ddl): void {
    $s = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
                         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $s->execute([$table, $col]);
    if ((int)$s->fetchColumn() === 0) {
      $pdo->exec("ALTER TABLE `$table` ADD COLUMN $ddl");
    }
  }
}

/* FR17 — Smart Shopping Cart: line items belonging to one order */
$pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  item_name VARCHAR(190) NOT NULL,
  item_type VARCHAR(40) NOT NULL DEFAULT 'package',
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* FR35 — Hardware Delivery Stepper: one shipment per order that contains hardware */
$pdo->exec("CREATE TABLE IF NOT EXISTS shipments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL UNIQUE,
  status VARCHAR(20) NOT NULL DEFAULT 'Order Placed',
  tracking_url VARCHAR(255) NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* FR34 — cancellation grace window + refund flag on orders */
ensureColumn($pdo, 'orders', 'cancel_deadline', 'cancel_deadline DATETIME NULL');
ensureColumn($pdo, 'orders', 'refunded',        'refunded TINYINT(1) NOT NULL DEFAULT 0');
ensureColumn($pdo, 'orders', 'has_hardware',    'has_hardware TINYINT(1) NOT NULL DEFAULT 0');

/* FR29 — physical inventory on hardware packages (already defined as inventory column in packages table) */
// ensureColumn($pdo, 'packages', 'stock', 'inventory INT NULL');

/* Grace window length for FR34, in minutes — single source of truth */
if (!defined('CANCEL_GRACE_MINUTES')) define('CANCEL_GRACE_MINUTES', 5);

/* ---------------------------------------------------------------------
 * FR17 — server-side pricing authority.
 * checkout.php must never trust a client-posted total. Everything below
 * recomputes the charge from data the server controls: package prices
 * come fresh from the DB, "build your own" prices come from this fixed
 * menu (mirrors the dropdown values in build.php's own inline JS), and
 * offer codes are re-validated here rather than trusting a pre-computed
 * discount from the page.
 * --------------------------------------------------------------------- */
if (!defined('BYO_BASE_PRICE')) define('BYO_BASE_PRICE', 10);
if (!defined('BYO_APP_DISCOUNT_PCT')) define('BYO_APP_DISCOUNT_PCT', 0.15); /* the "automatic 15%" advertised app-wide */

if (!function_exists('byoDevicePrices'))  { function byoDevicePrices(): array  { return [0, 6, 8, 10]; } }
if (!function_exists('byoMinutePrices'))  { function byoMinutePrices(): array  { return [3, 5, 9]; } }
if (!function_exists('byoDataPrices'))    { function byoDataPrices(): array    { return [4, 7, 12]; } }

/** Validate a "build your own" component against its allowed price list; falls back to the cheapest option if tampered. */
if (!function_exists('byoValidate')) {
  function byoValidate($value, array $allowed): int {
    $value = (int)$value;
    return in_array($value, $allowed, true) ? $value : $allowed[0];
  }
}

/** Authoritative custom-build price: base + validated device/minutes/data add-ons. */
if (!function_exists('byoPrice')) {
  function byoPrice($device, $minutes, $data): float {
    return BYO_BASE_PRICE
         + byoValidate($device, byoDevicePrices())
         + byoValidate($minutes, byoMinutePrices())
         + byoValidate($data, byoDataPrices());
  }
}

/** Resolve an offer code to a discount fraction (0..0.5), re-checking SAVE10/WELCOME5/dynamic vouchers server-side. Empty/invalid -> 0. */
if (!function_exists('resolveOfferDiscountPct')) {
  function resolveOfferDiscountPct(PDO $pdo, string $rawCode): float {
    $code = strtoupper(trim($rawCode));
    if ($code === '') return 0.0;
    
    // 1. Check vouchers table
    $s = $pdo->prepare('SELECT discount FROM vouchers WHERE code=?');
    $s->execute([$code]);
    $row = $s->fetch();
    if ($row) {
        return max(0, min(50, (int)$row['discount'])) / 100;
    }
    
    // 2. Check campaigns table (BUG-Campaign/Apply)
    $s2 = $pdo->prepare('SELECT discount_pct FROM campaigns WHERE code=? AND active=1');
    $s2->execute([$code]);
    $row2 = $s2->fetch();
    if ($row2) {
        return max(0, min(100, (float)$row2['discount_pct'])) / 100;
    }
    
    return 0.0;
  }
}

/* FR8 / FR30 — Custom Build Requirements list */
$pdo->exec("CREATE TABLE IF NOT EXISTS custom_requirements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  device VARCHAR(60) NOT NULL,
  minutes VARCHAR(60) NOT NULL,
  data VARCHAR(60) NOT NULL,
  sms VARCHAR(60) DEFAULT NULL,
  addons TEXT DEFAULT NULL,
  price DECIMAL(10,2) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* FR28 — SLA Technical Ticket Escalation table (BUG-021) */
$pdo->exec("CREATE TABLE IF NOT EXISTS ticket_escalations (
  user_id INT PRIMARY KEY,
  tier VARCHAR(255) NOT NULL,
  compensation DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status VARCHAR(50) NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
