<?php
/* ============================================================
   Session, helpers, current user, and seed accounts.
   Every page does:  require __DIR__.'/includes/auth.php';
   …then has $pdo, $CFG, $ME and the helper functions available.
   ============================================================ */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';   // -> $pdo, $CFG

function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function gbp($n){ return '£' . number_format((float)$n, 2); }
function redirect($to){ header('Location: ' . $to); exit; }

function currentUser($pdo){
  if (empty($_SESSION['uid'])) return null;
  $s = $pdo->prepare('SELECT id,name,email,role,address,phone,card,points,payment_pin_hash,avatar FROM users WHERE id=?');
  $s->execute([$_SESSION['uid']]);
  return $s->fetch() ?: null;
}
function requireLogin($me){ if (!$me) redirect('login.php'); }
function requireRole($me, $roles){ if (!$me || !in_array($me['role'], $roles, true)) redirect('index.php'); }

/* Is this account the ONE permanently-fixed admin? */
function isPrimaryAdmin($email){
  global $CFG;
  return strtolower(trim($email)) === strtolower($CFG['primary_admin']);
}

/* current chosen interface: 'desktop' or 'mobile' (cookie, default desktop) */
function currentView(){
  if (isset($_GET['view']) && in_array($_GET['view'], ['desktop','mobile'], true)) {
    setcookie('cd_view', $_GET['view'], time()+31536000, '/');
    return $_GET['view'];
  }
  return $_COOKIE['cd_view'] ?? 'desktop';
}

/* create admin / staff / demo-user once (passwords hashed) */
function ensureSeed($pdo){
  $accts = [
    ['Admin',          'admin@cheapdeals.com', 'admin123', 'admin', 'Head Office',  '0000000000', '0000000000000000'],
    ['Staff Member',   'staff@cheapdeals.com', 'staff123', 'staff', 'Head Office',  '0000000000', '0000000000000000'],
    ['Nhựt Trần Minh', 'mminhnhut4@gmail.com', '123456',   'user',  '204 Cộng Hoà', '0971041373', '4111 1111 1111 1111'],
  ];
  foreach ($accts as $a) {
    $s = $pdo->prepare('SELECT id FROM users WHERE email=?');
    $s->execute([$a[1]]);
    if (!$s->fetch()) {
      $pdo->prepare('INSERT INTO users(name,email,password,role,address,phone,card) VALUES (?,?,?,?,?,?,?)')
          ->execute([$a[0], $a[1], password_hash($a[2], PASSWORD_DEFAULT), $a[3], $a[4], $a[5], $a[6]]);
    }
  }
}
ensureSeed($pdo);

/* ------------------------------------------------------------------
   Auto-create and alter tables dynamically.
   ------------------------------------------------------------------ */
$pdo->exec("CREATE TABLE IF NOT EXISTS audit_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  actor VARCHAR(160), action VARCHAR(60), detail TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

$pdo->exec("CREATE TABLE IF NOT EXISTS vouchers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) UNIQUE, discount INT NOT NULL,
  created_by VARCHAR(160), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

require_once __DIR__ . '/schema_v4.php';

// Alter packages to add metadata columns
try { $pdo->exec("ALTER TABLE packages ADD COLUMN sales_count INT NOT NULL DEFAULT 0"); } catch(Throwable $t){}
try { $pdo->exec("ALTER TABLE packages ADD COLUMN rating_score DECIMAL(3,2) NOT NULL DEFAULT 0.00"); } catch(Throwable $t){}
try { $pdo->exec("ALTER TABLE packages ADD COLUMN rating_count INT NOT NULL DEFAULT 0"); } catch(Throwable $t){}
try { $pdo->exec("ALTER TABLE packages ADD COLUMN inventory INT NOT NULL DEFAULT 10"); } catch(Throwable $t){}

// Seed initial values for packages if they are 0
$pdo->exec("UPDATE packages SET sales_count=1243, rating_score=0.00, rating_count=0, inventory=15 WHERE code='mob-pro' AND sales_count=0");
$pdo->exec("UPDATE packages SET sales_count=850, rating_score=0.00, rating_count=0, inventory=10 WHERE code='mobile' AND sales_count=0");
$pdo->exec("UPDATE packages SET sales_count=420, rating_score=0.00, rating_count=0, inventory=8 WHERE code='mob-lite' AND sales_count=0");
$pdo->exec("UPDATE packages SET sales_count=920, rating_score=0.00, rating_count=0, inventory=12 WHERE code='broadband' AND sales_count=0");
$pdo->exec("UPDATE packages SET sales_count=1100, rating_score=0.00, rating_count=0, inventory=14 WHERE code='double' AND sales_count=0");
$pdo->exec("UPDATE packages SET sales_count=1500, rating_score=0.00, rating_count=0, inventory=15 WHERE code='triple' AND sales_count=0");

// Create products table (Hardware devices)
$pdo->exec("CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  description TEXT NOT NULL,
  price DECIMAL(8,2) NOT NULL,
  inventory INT NOT NULL DEFAULT 10,
  sales_count INT NOT NULL DEFAULT 0,
  rating_score DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  rating_count INT NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1
)");

// Seed products if not exists (BUG-004: Added Phone Devices Oppo, iPhone, Samsung)
$products = [
    ['prod-laptop', 'CheapDeals Laptop Pro 15', 'High-performance laptop with Intel Core i7, 16GB RAM, 512GB SSD, perfect for students and business users.', 599.00, 10, 45, 4.7, 12],
    ['prod-desktop', 'CheapDeals Desktop Office PC', 'Reliable office desktop computer with Intel Core i5, 8GB RAM, 256GB SSD, preloaded with Windows 11.', 399.00, 12, 28, 4.5, 8],
    ['prod-keyboard', 'CheapDeals Mechanical Keyboard', 'RGB backlit mechanical keyboard with blue switches, durable keys, and comfortable typing layout.', 45.00, 25, 80, 4.6, 15],
    ['prod-mouse', 'CheapDeals Wireless Mouse', 'Ergonomic wireless mouse with adjustable DPI, long battery life, and silent clicks.', 15.00, 30, 150, 4.4, 32],
    ['prod-cable', 'CheapDeals DisplayPort to HDMI Cable', 'High-speed gold-plated DP to HDMI adapter cable (6ft) supporting 4K resolution at 60Hz.', 12.00, 50, 300, 4.8, 90],
    ['prod-iphone15', 'iPhone 15 Pro', 'Flagship Apple iPhone with A17 Pro chip, Titanium design, 128GB storage, 48MP camera.', 999.00, 15, 62, 4.9, 14],
    ['prod-s24', 'Samsung Galaxy S24', 'Flagship Android smartphone with Galaxy AI, 50MP camera, 8GB RAM, 256GB storage.', 899.00, 12, 48, 4.8, 10],
    ['prod-reno11', 'Oppo Reno 11 5G', 'Stylish mid-range Android phone with 67W SUPERVOOC charging, 50MP portrait camera, 128GB storage.', 399.00, 20, 35, 4.6, 9]
];
foreach ($products as $p_item) {
    $s_check = $pdo->prepare("SELECT id FROM products WHERE code=?");
    $s_check->execute([$p_item[0]]);
    if (!$s_check->fetch()) {
        $pdo->prepare("INSERT INTO products (code, name, description, price, inventory, sales_count, rating_score, rating_count) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute($p_item);
    }
}

// Create wishlist table
$pdo->exec("CREATE TABLE IF NOT EXISTS wishlist (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  package_code VARCHAR(40) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

try { $pdo->exec("ALTER TABLE users ADD COLUMN points INT NOT NULL DEFAULT 0"); } catch(Throwable $t){}
try { $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL AFTER phone"); } catch(Throwable $t){}

$pdo->exec("CREATE TABLE IF NOT EXISTS campaigns (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  code VARCHAR(40) UNIQUE,
  discount_pct DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  discount_abs DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  target_segment VARCHAR(100) NOT NULL DEFAULT 'All',
  active TINYINT(1) NOT NULL DEFAULT 1
)");
try { $pdo->exec("ALTER TABLE campaigns ADD COLUMN description TEXT NULL"); } catch(Throwable $t){}

$pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  user_email VARCHAR(160) NOT NULL,
  action VARCHAR(60) NOT NULL,
  detail TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create reviews table for BUG-018
$pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  item_code VARCHAR(40) NOT NULL,
  rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
  comment TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_item (item_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Auto-sync packages and products ratings from reviews table on startup (BUG-Rating/Sync)
try {
    $s_pkg_revs = $pdo->query("SELECT item_code, AVG(rating) as avg_score, COUNT(*) as cnt FROM reviews GROUP BY item_code");
    $all_revs = $s_pkg_revs->fetchAll();
    
    // First reset all to 0
    $pdo->exec("UPDATE packages SET rating_score = 0.00, rating_count = 0");
    $pdo->exec("UPDATE products SET rating_score = 0.00, rating_count = 0");
    
    // Then apply calculated scores
    foreach ($all_revs as $r) {
        $item_code = $r['item_code'];
        $avg_score = round((float)$r['avg_score'], 2);
        $avg_count = (int)$r['cnt'];
        
        $s_up_pkg = $pdo->prepare("UPDATE packages SET rating_score = ?, rating_count = ? WHERE code = ?");
        $s_up_pkg->execute([$avg_score, $avg_count, $item_code]);
        
        $s_up_prd = $pdo->prepare("UPDATE products SET rating_score = ?, rating_count = ? WHERE code = ?");
        $s_up_prd->execute([$avg_score, $avg_count, $item_code]);
    }
} catch (Throwable $t) {}

// Create user_vouchers table for BUG-027
$pdo->exec("CREATE TABLE IF NOT EXISTS user_vouchers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  voucher_code VARCHAR(40) NOT NULL,
  used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_voucher (user_id, voucher_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Create email_verifications table for BUG-029/30
$pdo->exec("CREATE TABLE IF NOT EXISTS email_verifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(160) NOT NULL UNIQUE,
  code VARCHAR(6) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$s = $pdo->query("SELECT COUNT(*) FROM campaigns");
if ($s->fetchColumn() == 0) {
    $pdo->exec("INSERT INTO campaigns(name, code, discount_pct, discount_abs, target_segment, active) VALUES 
        ('10% Off Subscription', 'SAVE10', 10.00, 0.00, 'All', 1),
        ('Welcome Discount', 'WELCOME5', 5.00, 0.00, 'All', 1)");
}

/* FR20 - Luhn (mod-10) card-number check, ISO/IEC 7812-1. */
function luhn($number){
  $n = preg_replace('/\D/', '', (string)$number);
  if (strlen($n) < 12) return false;
  $sum = 0; $alt = false;
  for ($i = strlen($n) - 1; $i >= 0; $i--) {
    $d = (int)$n[$i];
    if ($alt) { $d *= 2; if ($d > 9) $d -= 9; }
    $sum += $d; $alt = !$alt;
  }
  return ($sum % 10) === 0;
}

/* BUG-010 - validateCardExpiry definition */
if (!function_exists('validateCardExpiry')) {
    function validateCardExpiry($expiry, &$message = null){
      $expiry = trim((string)$expiry);
      if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $expiry, $m)) {
        $message = 'Invalid expiry format. Use MM/YY.';
        return false;
      }
      $month = (int)$m[1];
      $year = 2000 + (int)$m[2];
      $now = new DateTimeImmutable('first day of this month 00:00:00');
      $expiresAt = (new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month)))->modify('first day of next month');
      if ($expiresAt <= $now) {
        $message = 'Card expired.';
        return false;
      }
      $message = '';
      return true;
    }
}

/* FR38 - append-only audit logger (never throws into the page). */
function audit($pdo, $actor, $action, $detail){
  try { $pdo->prepare('INSERT INTO audit_log(actor,action,detail) VALUES (?,?,?)')->execute([$actor, $action, $detail]); }
  catch (Throwable $e) {}
  try { $pdo->prepare('INSERT INTO audit_logs(user_email,action,detail) VALUES (?,?,?)')->execute([$actor, $action, $detail]); }
  catch (Throwable $e) {}
}

if (!function_exists('tableColumnExists')) {
    function tableColumnExists($pdo, $table, $column){
      try {
        $s = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $s->execute([$column]);
        return (bool)$s->fetch();
      } catch (Throwable $e) {
        return false;
      }
    }
}

if (!tableColumnExists($pdo, 'vouchers', 'expires_at')) {
  $pdo->exec("ALTER TABLE vouchers ADD COLUMN expires_at DATE DEFAULT NULL AFTER discount");
}

// Seed default vouchers
$defaultVouchers = [
  ['SAVE10', 10, '2099-12-31', 'system'],
  ['WELCOME5', 5, '2099-12-31', 'system'],
];
$voucherSeed = $pdo->prepare('INSERT INTO vouchers(code,discount,expires_at,created_by) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE discount=VALUES(discount)');
foreach ($defaultVouchers as $v) {
  try {
    $voucherSeed->execute($v);
  } catch (Throwable $t) {}
}

if (!tableColumnExists($pdo, 'users', 'payment_pin_hash')) {
  $pdo->exec("ALTER TABLE users ADD COLUMN payment_pin_hash VARCHAR(255) DEFAULT NULL AFTER card");
}

// Seed the payment pin for Nhựt Trần Minh (123456)
$demoPinHash = password_hash('123456', PASSWORD_DEFAULT);
$pdo->prepare("UPDATE users SET payment_pin_hash=? WHERE role='user' AND name='Nhựt Trần Minh' AND (payment_pin_hash IS NULL OR payment_pin_hash='')")
    ->execute([$demoPinHash]);

if (!function_exists('verifyPaymentPin')) {
    function verifyPaymentPin($me, $pin){
      if (!$me) return false;
      // If PIN is not set yet, any 6-digit PIN is accepted (will be saved upon form submit) (BUG-PIN/Verify)
      if (empty($me['payment_pin_hash'])) {
          return preg_match('/^\d{6}$/', trim((string)$pin));
      }
      $pin = trim((string)$pin);
      return preg_match('/^\d{6}$/', $pin) && password_verify($pin, $me['payment_pin_hash']);
    }
}

if (!function_exists('rememberPaymentPinVerified')) {
    function rememberPaymentPinVerified(){
      $_SESSION['payment_pin_verified_at'] = time();
    }
}

if (!function_exists('paymentPinRecentlyVerified')) {
    function paymentPinRecentlyVerified($seconds = 120){
      return !empty($_SESSION['payment_pin_verified_at'])
        && (time() - (int)$_SESSION['payment_pin_verified_at']) <= $seconds;
    }
}

$ME = currentUser($pdo);
$view = currentView();
?>