<?php
/* FR39 - VISA gateway proxy with payment PIN verification before card checks. */
require_once __DIR__ . '/includes/auth.php';
header('Content-Type: application/json; charset=utf-8');

function gatewayStep($label, $ok, $t0){
  return ['step' => $label, 'status' => $ok ? 'pass' : 'fail', 'time' => round((microtime(true) - $t0) * 1000, 2)];
}

function gatewayExit($success, $steps, $message, $extra = []){
  echo json_encode(array_merge([
    'success' => $success,
    'steps' => $steps,
    'message' => $message,
    'total_ms' => round((microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))) * 1000, 2),
  ], $extra));
  exit;
}

function gatewayFail($pdo, $actor, $action, $detail, $steps, $message, $extra = []){
  audit($pdo, $actor, $action, $detail);
  gatewayExit(false, $steps, $message, $extra);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(400);
  echo json_encode(['success' => false, 'steps' => [], 'message' => 'POST required']);
  exit;
}

$actor = $ME['email'] ?? 'guest';
$cardNum = preg_replace('/\D/', '', $_POST['card_num'] ?? '');
$expiry = trim($_POST['expiry'] ?? '');
$cvv = trim($_POST['cvv'] ?? '');
$amount = (float)($_POST['amount'] ?? 0);
$paymentPin = trim($_POST['payment_pin'] ?? '');

$steps = [];
$t0 = microtime(true);

usleep(80000);
$pinOk = $ME && $ME['role'] === 'user' && verifyPaymentPin($ME, $paymentPin);
$steps[] = gatewayStep('Payment PIN Verification', $pinOk, $t0);
if (!$pinOk) {
  gatewayFail($pdo, $actor, 'visa_pin_failed', 'Blocked VISA gateway callback before card checks.', $steps, 'Invalid payment PIN');
}
rememberPaymentPinVerified();

usleep(90000);
$lengthOk = strlen($cardNum) >= 12 && strlen($cardNum) <= 19;
$steps[] = gatewayStep('Card Number Length', $lengthOk, $t0);
if (!$lengthOk) {
  gatewayFail($pdo, $actor, 'visa_card_declined', 'Invalid card number length.', $steps, 'Invalid card number length');
}

usleep(150000);
$luhnOk = luhn($cardNum);
$steps[] = gatewayStep('Luhn Check', $luhnOk, $t0);
if (!$luhnOk) {
  gatewayFail($pdo, $actor, 'visa_card_declined', 'Card failed Luhn validation.', $steps, 'Card failed Luhn validation');
}

usleep(120000);
$expiryMessage = '';
$expiryOk = validateCardExpiry($expiry, $expiryMessage);
$steps[] = gatewayStep('Expiry Check', $expiryOk, $t0);
if (!$expiryOk) {
  gatewayFail($pdo, $actor, 'visa_card_declined', 'Expiry declined: ' . $expiryMessage, $steps, $expiryMessage ?: 'Invalid expiry date');
}

usleep(100000);
$cvvOk = preg_match('/^\d{3}$/', $cvv);
$steps[] = gatewayStep('CVV Check', $cvvOk, $t0);
if (!$cvvOk) {
  gatewayFail($pdo, $actor, 'visa_card_declined', 'Invalid CVV.', $steps, 'Invalid CVV');
}

usleep(80000);
$amountOk = $amount > 0 && $amount < 10000;
$steps[] = gatewayStep('Amount Verification', $amountOk, $t0);
if (!$amountOk) {
  gatewayFail($pdo, $actor, 'visa_amount_declined', 'Invalid amount: ' . $amount, $steps, 'Invalid payment amount');
}

usleep(300000);
$approved = true;
$steps[] = gatewayStep('Bank Approval', $approved, $t0);

audit($pdo, $actor, 'visa_gateway_approved', 'VISACheck approved card ending ' . substr($cardNum, -4) . ' for ' . number_format($amount, 2));
gatewayExit(true, $steps, 'All checks passed', [
  'card_last4' => substr($cardNum, -4),
  'amount' => $amount,
]);
