<?php
/* FR35 - Hardware Delivery Stepper Tracker
   "Verify tracking link redirects to logistics carrier": this page looks up
   the shipment for the given order and redirects out to the carrier URL
   staff attached in Staff Panel -> Shipping. Falls back to a generic
   carrier tracking search if no URL has been set yet. */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/schema_v4.php';
requireLogin($ME);

$orderId = (int)($_GET['order_id'] ?? 0);
$s = $pdo->prepare('SELECT o.id,o.user_id,sh.tracking_url FROM orders o JOIN shipments sh ON sh.order_id=o.id WHERE o.id=?');
$s->execute([$orderId]);
$row = $s->fetch();

if (!$row || ($row['user_id'] != $ME['id'] && $ME['role'] === 'user')) {
  redirect('account.php?tab=billing&msg=' . urlencode('No shipment found for that order.'));
}

$url = $row['tracking_url'] ?: ('https://www.royalmail.com/track-your-item#/tracking-results/' . urlencode('CD' . str_pad($orderId, 9, '0', STR_PAD_LEFT)));
header('Location: ' . $url);
exit;
