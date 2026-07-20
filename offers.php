<?php
$cur = 'offers.php'; $pageTitle = 'Special offers';
require __DIR__ . '/includes/header.php';
$personal = '';
if ($ME && $ME['role'] === 'user') {
  $s = $pdo->prepare('SELECT package_name FROM orders WHERE user_id=?'); $s->execute([$ME['id']]);
  $hasDeal = false; foreach ($s->fetchAll() as $o) if (preg_match('/Bundle|Triple|Double/', $o['package_name'])) $hasDeal = true;
  $code = $hasDeal ? 'SAVE10' : 'WELCOME5';
  $txt  = $hasDeal ? 'As a multi-package customer, enjoy 10% off your next order' : 'New customer offer: 5% off your next order';
  $personal = '<div class="offer-banner"><div>🎁 <strong>Personalised for you:</strong> ' . esc($txt) . ' — code <code>' . $code . '</code></div><a class="btn small" href="checkout.php">Use it now</a></div>';
}
?>
<h2 class="title">Special offers</h2>
<p class="lead">Use these codes at checkout for an extra discount (on top of the 15% app discount).</p>
<?= $personal ?>
<div class="grid">
  <div class="card"><div class="tags"><span class="tag cat">Save 10%</span></div><h3>SAVE10</h3><div class="price">10% <small>off</small></div>
    <ul><li>Extra 10% off any package</li><li>Stacks with app 15%</li><li>Enter at checkout</li></ul>
    <a class="btn ghost" href="checkout.php">Order &amp; apply</a></div>
  <div class="card"><div class="tags"><span class="tag cat">Welcome</span></div><h3>WELCOME5</h3><div class="price">5% <small>off</small></div>
    <ul><li>Extra 5% off for new customers</li><li>Stacks with app 15%</li><li>Enter at checkout</li></ul>
    <a class="btn ghost" href="checkout.php">Order &amp; apply</a></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
