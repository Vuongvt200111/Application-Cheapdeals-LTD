<?php
/* FR18 / FR30 - validate a voucher code; returns JSON {valid, discount, message}. */
require_once __DIR__ . '/includes/auth.php';   // gives $pdo and ensures the vouchers table exists
header('Content-Type: application/json; charset=utf-8');

$code = strtoupper(trim($_GET['code'] ?? ''));
$v = null;
if ($code !== '') {
  $s = $pdo->prepare('SELECT discount, expires_at, created_by FROM vouchers WHERE code=?');
  $s->execute([$code]);
  $v = $s->fetch();
}

if (!$v) {
  echo json_encode(['valid' => false, 'discount' => 0, 'message' => 'Voucher code not found.']);
  exit;
}

// Single-use check (BUG-027)
if ($ME) {
  $s_used = $pdo->prepare('SELECT id FROM user_vouchers WHERE user_id=? AND voucher_code=?');
  $s_used->execute([$ME['id'], $code]);
  if ($s_used->fetch()) {
    echo json_encode(['valid' => false, 'discount' => 0, 'message' => 'You have already used this promo code.']);
    exit;
  }
}

$cohortEligible = true;
$errorMsg = '';
if ($v && $ME) {
  $meta = $v['created_by'] ?? '';
  if (strpos($meta, '[Cohort Rule: data_usage_gt_20gb]') !== false) {
    $h = 0; 
    foreach (str_split($ME['email']) as $ch) {
      $h = ($h * 31 + ord($ch)) % 1000;
    }
    $simulatedData = round(4 + ($h % 55) / 10, 1);
    if ($simulatedData <= 20.0) {
      $cohortEligible = false;
      $errorMsg = 'Checkout Blocked: This voucher is strictly reserved for users with data usage > 20GB (Your current data: ' . $simulatedData . 'GB).';
    }
  }
}

if (!$cohortEligible) {
  echo json_encode(['valid' => false, 'discount' => 0, 'message' => $errorMsg]);
  exit;
}

$expiresAt = trim((string)($v['expires_at'] ?? ''));
if ($expiresAt !== '') {
  try {
    $expiryDate = new DateTimeImmutable($expiresAt . ' 23:59:59');
    $today = new DateTimeImmutable('today');
    if ($expiryDate < $today) {
      echo json_encode(['valid' => false, 'discount' => 0, 'expired' => true, 'message' => 'Voucher expired on ' . $expiresAt . '.']);
      exit;
    }
  } catch (Throwable $e) {
    echo json_encode(['valid' => false, 'discount' => 0, 'expired' => true, 'message' => 'Voucher expiry date is invalid.']);
    exit;
  }
}

echo json_encode([
  'valid' => true,
  'discount' => (int)$v['discount'],
  'expires_at' => $expiresAt ?: null,
  'message' => $expiresAt !== '' ? ('Voucher valid until ' . $expiresAt . '.') : 'Voucher valid.'
]);
