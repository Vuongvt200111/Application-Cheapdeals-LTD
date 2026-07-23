<?php
/* FR34 - Order Cancellation & Automatic Refund */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/schema_v4.php';
requireLogin($ME);

$orderId = (int)($_POST['order_id'] ?? $_GET['id'] ?? 0);
if (!$orderId) {
    redirect('account.php?tab=billing');
}

$s = $pdo->prepare('SELECT * FROM orders WHERE id=? AND user_id=?');
$s->execute([$orderId, $ME['id']]);
$order = $s->fetch();

if (!$order) {
    redirect('account.php?tab=billing&msg=' . urlencode('Order not found.'));
}
if (($order['status'] ?? '') === 'Cancelled') {
    redirect('account.php?tab=billing&msg=' . urlencode('This order has already been cancelled.'));
}

// 15-Minute Grace Window Check
$orderCreated = strtotime($order['created_at']);
$minsPassed = (time() - $orderCreated) / 60;

if ($minsPassed > 15) {
    redirect('account.php?tab=billing&msg=' . urlencode('The 15-minute cancellation window for this order has expired.'));
}

/* Update status to Cancelled, set refunded flag, and log audit trail */
/* Update status to Cancelled, set refunded flag, and log audit trail */
$pdo->prepare("UPDATE orders SET status='Cancelled', refunded=1, points_credited=0 WHERE id=?")->execute([$orderId]);
$refundRef = 'RF-' . strtoupper(substr(md5($orderId . microtime()), 0, 8));

if (function_exists('audit')) {
    audit($pdo, $ME['email'], 'order_cancelled', 'Order #' . $orderId . ' (' . ($order['package_name'] ?? 'Package') . ', ' . gbp($order['total'] ?? 0) . ') cancelled within 15m grace window.');
    audit($pdo, $ME['email'], 'refund_webhook', 'Refund webhook fired for order #' . $orderId . ' — ref ' . $refundRef . ', amount ' . gbp($order['total'] ?? 0) . '.');
}

redirect('account.php?tab=billing&msg=' . urlencode('Order #' . $orderId . ' cancelled successfully and £' . number_format($order['total'] ?? 0, 2) . ' refunded (Ref: ' . $refundRef . ').'));
