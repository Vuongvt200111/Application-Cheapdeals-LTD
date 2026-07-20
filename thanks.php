<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin($ME);
$o = $_SESSION['last_order'] ?? null;
unset($_SESSION['last_order']);
if (!$o) redirect('index.php');
$cur = 'thanks.php'; $pageTitle = 'Thank you';
require __DIR__ . '/includes/header.php';
?>
<div style="text-align:center">
  <div class="thanks-check">✓</div>
  <h2 class="title" style="text-align:center">Thank you, <?= esc($ME['name']) ?>! 🎉</h2>
  <p class="lead">Your payment was successful. A receipt has been emailed to <?= esc($o['email']) ?>.</p>
  <div class="panel" style="max-width:420px;margin:6px auto 18px;text-align:left">
    <div class="summary" style="margin:0;border:none;background:transparent">
      <div class="row"><span>Package</span><span><?= esc($o['name']) ?></span></div>
      <div class="row"><span>You saved</span><span class="discount"><?= gbp($o['saved']) ?></span></div>
      <?php if (isset($o['visa_ms'])): ?><div class="row"><span>VISACheck (Luhn)</span><span class="discount">&#10003; verified in <?= esc($o['visa_ms']) ?> ms</span></div><?php endif; ?>
      <div class="row total"><span>Total paid</span><span><?= gbp($o['total']) ?></span></div>
    </div>
  </div>
  <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
    <a class="btn" href="account.php?tab=billing">View my orders</a>
    <a class="btn sec" href="index.php">Continue shopping</a>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
