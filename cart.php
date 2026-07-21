<?php
/* FR17 - Smart Shopping Cart Module
   Lets a customer collect several packages / devices in a session-based
   cart, then pay for all of them in one checkout. Each cart line becomes
   one row in order_items once the order is placed (see checkout.php). */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/schema_v4.php';
requireLogin($ME);

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];

/* ---- add an item to the cart ---- */
if (isset($_GET['add'])) {
  if ($_GET['add'] === 'custom') {
    $name = trim($_GET['name'] ?? 'Custom Package');
    $d = byoValidate($_GET['d'] ?? 0, byoDevicePrices());
    $m = byoValidate($_GET['m'] ?? 0, byoMinutePrices());
    $a = byoValidate($_GET['a'] ?? 0, byoDataPrices());
    $price = byoPrice($d, $m, $a); /* server-computed — $_GET['price'] is never trusted */
    $hw = $d > 0; /* FR35: a real device add-on means this custom build includes physical hardware */
    $key = 'custom-' . md5($name . $d . $m . $a);
    if (isset($_SESSION['cart'][$key])) $_SESSION['cart'][$key]['qty']++;
    else $_SESSION['cart'][$key] = ['code' => null, 'name' => $name, 'price' => $price, 'type' => 'custom', 'qty' => 1, 'hw' => $hw];
  } else {
    $code = $_GET['add'];
    $s = $pdo->prepare('SELECT code,name,price,inventory FROM packages WHERE code=? AND active=1'); $s->execute([$code]); $p = $s->fetch();
    if ($p) {
      $p['type'] = 'package';
    } else {
      $s_prod = $pdo->prepare('SELECT code,name,price,inventory FROM products WHERE code=? AND active=1');
      $s_prod->execute([$code]);
      $p = $s_prod->fetch();
      if ($p) {
        $p['type'] = 'product';
      }
    }
    if ($p) {
      $type = $p['type'];
      $is_hw = ($type === 'product');
      $inventory = (int)$p['inventory'];
      
      $new_qty = 1;
      if (isset($_SESSION['cart'][$p['code']])) {
        $new_qty = $_SESSION['cart'][$p['code']]['qty'] + 1;
      }
      
      if ($new_qty > $inventory) {
        redirect('cart.php?msg=' . urlencode('Sorry, "' . $p['name'] . '" only has ' . $inventory . ' left in stock.'));
      }
      
      if (isset($_SESSION['cart'][$p['code']])) $_SESSION['cart'][$p['code']]['qty']++;
      else $_SESSION['cart'][$p['code']] = ['code' => $p['code'], 'name' => $p['name'], 'price' => (float)$p['price'], 'type' => $type, 'qty' => 1, 'hw' => $is_hw];
    }
  }
  redirect('cart.php?msg=' . urlencode('Added to cart.'));
}

/* ---- update quantities / remove / clear ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $act = $_POST['act'] ?? '';
  if ($act === 'updateQty') {
    foreach ((array)($_POST['qty'] ?? []) as $key => $qty) {
      $qty = (int)$qty;
      if (!isset($_SESSION['cart'][$key])) continue;
      if ($qty <= 0) {
        unset($_SESSION['cart'][$key]);
        continue;
      }
      
      // Verify stock limits (BUG-Cart/Stock)
      $it = $_SESSION['cart'][$key];
      if ($it['type'] !== 'custom') {
        $s_stock = $pdo->prepare("SELECT name, inventory FROM packages WHERE code=? AND active=1 UNION SELECT name, inventory FROM products WHERE code=? AND active=1");
        $s_stock->execute([$it['code'], $it['code']]);
        $item_stock = $s_stock->fetch();
        if ($item_stock) {
          $inventory = (int)$item_stock['inventory'];
          $item_name = $item_stock['name'];
          if ($qty > $inventory) {
            redirect('cart.php?msg=' . urlencode('Sorry, "' . $item_name . '" only has ' . $inventory . ' left in stock.'));
          }
        }
      }
      
      $_SESSION['cart'][$key]['qty'] = min($qty, 20);
    }
    redirect('cart.php?msg=' . urlencode('Cart updated.'));
  } elseif ($act === 'remove') {
    unset($_SESSION['cart'][$_POST['key'] ?? '']);
    redirect('cart.php?msg=' . urlencode('Item removed.'));
  } elseif ($act === 'clear') {
    $_SESSION['cart'] = [];
    redirect('cart.php?msg=' . urlencode('Cart cleared.'));
  }
}

$cur = 'cart.php'; $pageTitle = 'Your Cart';
require __DIR__ . '/includes/header.php';

$cart = $_SESSION['cart'];
$total = 0; foreach ($cart as $it) $total += $it['price'] * $it['qty'];
?>
<h2 class="title">🛒 Your Cart</h2>
<p class="lead">Collect several packages or devices here, then pay for everything in one go.</p>
<?php if (!empty($_GET['msg'])): ?><div class="msg ok"><?= esc($_GET['msg']) ?></div><?php endif; ?>

<div class="panel" style="max-width:680px">
<?php if (!$cart): ?>
  <p class="lead">Your cart is empty. <a href="index.php">Browse packages</a> or <a href="build.php">build your own</a>.</p>
<?php else: ?>
  <form method="post">
    <input type="hidden" name="act" value="updateQty">
    <div class="table-scroll"><table>
      <thead><tr><th>Item</th><th>Price</th><th>Qty</th><th>Line total</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($cart as $key => $it): ?>
        <tr>
          <td><?= esc($it['name']) ?><?= $it['type']==='custom' ? ' <span class="tag cat">custom</span>' : '' ?></td>
          <td><?= gbp($it['price']) ?></td>
          <td><input type="number" min="0" max="20" name="qty[<?= esc($key) ?>]" value="<?= (int)$it['qty'] ?>" style="width:64px;padding:6px;border:1px solid var(--line);border-radius:7px;background:var(--card);color:var(--ink)"></td>
          <td><?= gbp($it['price'] * $it['qty']) ?></td>
          <td><button class="btn small danger" type="submit" form="rm-<?= esc($key) ?>">Remove</button></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <div class="summary" style="margin-top:12px">
      <div class="row total"><span>Cart total / month</span><span><?= gbp($total) ?></span></div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px">
      <button class="btn" type="submit">Update quantities</button>
      <a class="btn" href="checkout.php?cart=1">Proceed to checkout</a>
      <button class="btn sec" type="submit" form="clear-cart">Clear cart</button>
    </div>
  </form>
  <form id="clear-cart" method="post" action="cart.php" style="display:none">
    <input type="hidden" name="act" value="clear">
  </form>
  <?php foreach ($cart as $key => $it): ?>
    <form id="rm-<?= esc($key) ?>" method="post" action="cart.php" style="display:none">
      <input type="hidden" name="act" value="remove"><input type="hidden" name="key" value="<?= esc($key) ?>">
    </form>
  <?php endforeach; ?>
<?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
