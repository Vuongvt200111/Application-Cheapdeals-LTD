<?php
/* FR34 - Order Cancellation & Automatic Refund
   A customer can cancel a "Paid" order while it's still inside its grace
   window (cancel_deadline, set at checkout — see checkout.php / CANCEL_GRACE_MINUTES
   in includes/schema_v4.php). Cancelling flips the order to "Cancelled",
   marks it refunded, and fires a (simulated) refund webhook, all logged to
   the immutable audit trail used elsewhere in the app (FR38). */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/schema_v4.php';
requireLogin($ME);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['act'] ?? '') !== 'cancelOrder') {
  redirect('account.php?tab=billing');
}

$orderId = (int)($_POST['order_id'] ?? 0);
$s = $pdo->prepare('SELECT * FROM orders WHERE id=? AND user_id=?');
$s->execute([$orderId, $ME['id']]);
$order = $s->fetch();

if (!$order) {
  redirect('account.php?tab=billing&msg=' . urlencode('Order not found.'));
}
if ($order['status'] !== 'Paid') {
  redirect('account.php?tab=billing&msg=' . urlencode('This order can no longer be cancelled.'));
}
if (empty($order['cancel_deadline']) || strtotime($order['cancel_deadline']) < time()) {
  redirect('account.php?tab=billing&msg=' . urlencode('The cancellation window for this order has closed.'));
}

/* Flip status + refund flag, then simulate calling the payment gateway's refund webhook */
$pdo->prepare("UPDATE orders SET status='Cancelled', refunded=1 WHERE id=?")->execute([$orderId]);
$refundRef = 'RF-' . strtoupper(substr(md5($orderId . microtime()), 0, 8));
audit($pdo, $ME['email'], 'order_cancelled', 'Order #' . $orderId . ' (' . $order['package_name'] . ', ' . gbp($order['total']) . ') cancelled within grace window.');
audit($pdo, $ME['email'], 'refund_webhook', 'Refund webhook fired for order #' . $orderId . ' — ref ' . $refundRef . ', amount ' . gbp($order['total']) . '.');

redirect('account.php?tab=billing&msg=' . urlencode('Order cancelled and £' . number_format($order['total'], 2) . ' refunded (ref ' . $refundRef . ').'));
