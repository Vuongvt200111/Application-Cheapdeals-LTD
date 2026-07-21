<?php
require_once __DIR__ . '/includes/auth.php';

// Redirect guest to login (BUG-018/Offers/Guest access check)
if (!$ME) {
    redirect('login.php?back=offers.php');
}

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

// Fetch all active campaigns dynamically from database (BUG-018/Offers)
$s_camp = $pdo->query("SELECT * FROM campaigns WHERE active = 1 ORDER BY id DESC");
$campaigns = $s_camp->fetchAll();
?>
<style>
  .card ul {
    list-style: none !important;
    padding-left: 0 !important;
    margin: 15px 0 20px 0 !important;
    text-align: left !important;
  }
  .card ul li {
    margin-bottom: 8px !important;
    font-size: 14px !important;
    color: var(--ink) !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
  }
  .card ul li::before {
    content: "✓" !important;
    color: var(--green) !important;
    font-weight: bold !important;
  }
</style>
<h2 class="title">Special offers</h2>
<p class="lead">Use these codes at checkout for an extra discount (on top of the 15% app discount).</p>
<?= $personal ?>

<div class="grid">
  <?php if (empty($campaigns)): ?>
    <p class="lead" style="grid-column: 1/-1;">No special promotional offers available at the moment.</p>
  <?php else: ?>
    <?php foreach ($campaigns as $c): 
      // Specific tags for default campaigns per screenshot
      if ($c['code'] === 'SAVE10') {
          $tag = 'SAVE 10%';
      } elseif ($c['code'] === 'WELCOME5') {
          $tag = 'WELCOME';
      } else {
          $tag = $c['discount_pct'] > 0 ? 'SAVE ' . (int)$c['discount_pct'] . '%' : 'SAVE ' . gbp($c['discount_abs']);
      }
    ?>
      <div class="card">
        <div class="tags">
          <span class="tag cat"><?= esc($tag) ?></span>
          <?php if ($c['target_segment'] !== 'All'): ?>
            <span class="tag" style="background:var(--soft); color:var(--brand);"><?= esc($c['target_segment']) ?></span>
          <?php endif; ?>
        </div>
        <h3><?= esc($c['code']) ?></h3>
        <div class="price">
          <?= $c['discount_pct'] > 0 ? (int)$c['discount_pct'] . '% <small>off</small>' : gbp($c['discount_abs']) . ' <small>off</small>' ?>
        </div>
        <ul>
          <?php if ($c['code'] === 'SAVE10'): ?>
            <li>Extra 10% off any package</li>
            <li>Stacks with app 15%</li>
            <li>Enter at checkout</li>
          <?php elseif ($c['code'] === 'WELCOME5'): ?>
            <li>Extra 5% off for new customers</li>
            <li>Stacks with app 15%</li>
            <li>Enter at checkout</li>
          <?php elseif (!empty($c['description']) && trim($c['description']) !== '' && strtolower(trim($c['description'])) !== 'none'): ?>
            <?php 
              $lines = explode("\n", str_replace("\r", "", $c['description'])); 
              foreach ($lines as $line):
                $line = trim($line);
                if ($line !== ''):
            ?>
                  <li><?= esc($line) ?></li>
            <?php 
                endif;
              endforeach; 
            ?>
          <?php else: ?>
            <li>Extra <?= $c['discount_pct'] > 0 ? (int)$c['discount_pct'] . '%' : gbp($c['discount_abs']) ?> off: <?= esc($c['name']) ?></li>
            <li>Stacks with app 15%</li>
            <li>Enter at checkout</li>
          <?php endif; ?>
        </ul>
        <a class="btn" href="checkout.php?code=<?= urlencode($c['code']) ?>" style="display:block; text-align:center; font-weight:700;">ORDER &amp; APPLY</a>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
