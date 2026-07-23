<?php
$cur = 'account.php'; $pageTitle = 'My Account';
require __DIR__ . '/../layout/header.php';

// Resolve purchased items to review
$purchasedItems = [];
if (!empty($orders)) {
    foreach ($orders as $o) {
        // Look up item code in order_items if order has items, else fallback
        $o_id = (int)$o['id'];
        $stmt_items = $pdo->prepare("SELECT item_name, item_type, price FROM order_items WHERE order_id = ?");
        $stmt_items->execute([$o_id]);
        $items_list = $stmt_items->fetchAll();
        
        if ($items_list) {
            foreach ($items_list as $i_row) {
                // Find matching product or package code
                $name_check = $i_row['item_name'];
                $s_pkg = $pdo->prepare("SELECT code FROM packages WHERE name = ?");
                $s_pkg->execute([$name_check]);
                $code_val = $s_pkg->fetchColumn();
                if (!$code_val) {
                    $s_prd = $pdo->prepare("SELECT code FROM products WHERE name = ?");
                    $s_prd->execute([$name_check]);
                    $code_val = $s_prd->fetchColumn();
                }
                if ($code_val) {
                    $purchasedItems[$code_val] = $name_check;
                }
            }
        } else {
            // fallback
            $name_check = $o['package_name'];
            $s_pkg = $pdo->prepare("SELECT code FROM packages WHERE name = ?");
            $s_pkg->execute([$name_check]);
            $code_val = $s_pkg->fetchColumn();
            if (!$code_val) {
                $s_prd = $pdo->prepare("SELECT code FROM products WHERE name = ?");
                $s_prd->execute([$name_check]);
                $code_val = $s_prd->fetchColumn();
            }
            if ($code_val) {
                $purchasedItems[$code_val] = $name_check;
            }
        }
    }
}
?>
<div class="acc-layout">
  <aside class="acc-side">
    <div class="acc-prof">
      <?php if (!empty($ME['avatar'])): ?>
        <img src="<?= esc($ME['avatar']) ?>" style="width:84px; height:84px; border-radius:50%; object-fit:cover; margin:0 auto 12px; display:block; border: 2px solid var(--neon);" />
      <?php else: ?>
        <div class="av"><?= esc(strtoupper(substr($ME['name'],0,1))) ?></div>
      <?php endif; ?>
      <h3><?= esc($ME['name']) ?></h3><div class="email"><?= esc($ME['email']) ?></div>
      <span class="status"><span class="dot"></span> Active</span>
    </div>
    <nav class="acc-nav">
      <a class="<?= $tab==='overview'?'active':'' ?>" href="account.php?tab=overview">📊 Overview</a>
      <a class="<?= $tab==='personal'?'active':'' ?>" href="account.php?tab=personal">✏️ Personal Info</a>
      <a class="<?= $tab==='security'?'active':'' ?>" href="account.php?tab=security">🔑 Security</a>
      <a class="<?= $tab==='usage'?'active':'' ?>" href="account.php?tab=usage">📈 Usage</a>
      <a class="<?= $tab==='billing'?'active':'' ?>" href="account.php?tab=billing">💳 Billing</a>
      <a class="<?= $tab==='wishlist'?'active':'' ?>" href="account.php?tab=wishlist">❤️ Wishlist</a>
      <a class="<?= $tab==='reviews'?'active':'' ?>" href="account.php?tab=reviews">⭐ My Reviews</a>
      <a href="support.php">💬 Support</a>
      <a class="out" href="logout.php">⏻ Sign Out</a>
    </nav>
  </aside>
  <section class="acc-main"><div class="panel">
  
  <?php if ($tab === 'overview'):
    $saved = 0; foreach ($orders as $o) $saved += (float)$o['saved'];
    $cur_pkg = '—';
    if ($orders) {
        $first_o = $orders[0];
        if (strpos($first_o['package_name'], 'Cart (') === 0) {
            $o_id = (int)$first_o['id'];
            $stmt_items = $pdo->prepare("SELECT item_name, count(*) as qty FROM order_items WHERE order_id = ? GROUP BY item_name");
            $stmt_items->execute([$o_id]);
            $items_list = $stmt_items->fetchAll();
            $item_names = [];
            foreach ($items_list as $it) {
                $item_names[] = $it['item_name'] . ($it['qty'] > 1 ? ' (x' . $it['qty'] . ')' : '');
            }
            $cur_pkg = implode(', ', $item_names);
        } else {
            $cur_pkg = $first_o['package_name'];
        }
    }
  ?>
    <div class="acc-head">📊 Account Overview</div>
    <div class="stats">
      <div class="stat"><div class="num"><?= count($orders) ?></div><div class="lab">Total orders</div></div>
      <div class="stat"><div class="num green"><?= gbp($saved) ?></div><div class="lab">Total saved</div></div>
      <div class="stat"><div class="num" style="font-size:17px"><?= esc($cur_pkg) ?></div><div class="lab">Current package</div></div>
    </div>
    <div class="stats" style="margin-top: 15px;">
      <div class="stat" style="grid-column: span 3;"><div class="num" style="color:var(--brand)"><?= $points ?></div><div class="lab">Loyalty Points Available (£<?= number_format($points, 2) ?> discount)</div></div>
    </div>
    <div class="hint">Every order via the app saves you 15% automatically and earns you 1% reward points!</div>

  <?php elseif ($tab === 'personal'): ?>
    <div class="acc-head">✏️ Personal Info</div>
    <?php if (!empty($_GET['msg'])): ?><div class="msg ok"><?= esc($_GET['msg']) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="act" value="updateProfile">
      
      <!-- Avatar upload (BUG-019) -->
      <div class="fg">
        <label>Profile Avatar</label>
        <input type="file" name="avatar" accept="image/*">
        <small style="color:var(--muted); margin-top:4px; display:block;">Upload a JPG, PNG, or GIF file.</small>
      </div>
      
      <div class="fg"><label>Full name</label><input name="name" value="<?= esc($ME['name']) ?>" required></div>
      <div class="fg"><label>Email (cannot be changed)</label><input value="<?= esc($ME['email']) ?>" disabled></div>
      <div class="fg"><label>Address</label><input name="address" value="<?= esc($ME['address']) ?>" required></div>
      
      <div class="two-col">
        <div class="fg"><label>Telephone</label><input name="phone" value="<?= esc($ME['phone']) ?>" required></div>
        <div class="fg"><label>Credit card number (optional)</label><input name="card" maxlength="19" value="<?= esc($ME['card']) ?>" placeholder="Leave empty if not set"></div>
      </div>
      
      <!-- PIN management (BUG-031) -->
      <div class="fg">
        <label>6-Digit Payment PIN (leave empty to keep current)</label>
        <input name="payment_pin" type="password" maxlength="6" pattern="\d{6}" placeholder="e.g. 123456">
      </div>
      
      <button class="btn" type="submit" style="margin-top:10px;">Save changes</button>
    </form>

  <?php elseif ($tab === 'security'): ?>
    <!-- Security tab for change password (BUG-007, BUG-030) -->
    <div class="acc-head">🔑 Security Settings</div>
    <?php if (!empty($_GET['msg'])): ?><div class="msg ok"><?= esc($_GET['msg']) ?></div><?php endif; ?>
    <?php if (!empty($_GET['err'])): ?><div class="msg err"><?= esc($_GET['err']) ?></div><?php endif; ?>
    
    <form method="post">
      <input type="hidden" name="act" value="changePassword">
      <div class="fg">
        <label>Registered Email</label>
        <div style="display:flex; gap:8px;">
          <input value="<?= esc($ME['email']) ?>" disabled style="flex:1;">
          <button type="button" class="btn small" id="sendSecCodeBtn" style="white-space:nowrap;">Get Code</button>
        </div>
      </div>
      
      <div class="fg">
        <label>6-Digit Email Verification Code</label>
        <input name="code" maxlength="6" placeholder="Enter code received" required>
      </div>
      
      <div class="two-col">
        <div class="fg"><label>New Password</label><input name="new_password" type="password" required></div>
        <div class="fg"><label>Confirm New Password</label><input name="confirm_password" type="password" required></div>
      </div>
      
      <button class="btn" type="submit" style="margin-top:10px;">Change Password</button>
    </form>

  <?php elseif ($tab === 'usage'):
    $h = 0; foreach (str_split($ME['email']) as $ch) $h = ($h*31 + ord($ch)) % 1000;
    $mins = 320 + $h%180; $sms = 40 + $h%60; $data = round(4 + ($h%55)/10, 1);
    function bar($l,$u,$t,$unit,$p){ return "<div class='usage-item'><div class='top'><span>$l</span><span>$u / $t $unit</span></div><div class='bar'><span style='--w:{$p}%'></span></div></div>"; }
  ?>
    <div class="acc-head">📈 Usage this month</div>
    <?= bar('Call minutes',$mins,500,'min',round($mins/5)) ?>
    <?= bar('SMS',$sms,100,'sms',$sms) ?>
    <?= bar('Mobile data',$data,10,'GB',round($data*10)) ?>
    <div class="hint">Usage is simulated for this prototype.</div>

  <?php elseif ($tab === 'wishlist'): ?>
    <div class="acc-head">❤️ Your Wishlist</div>
    <?php if (empty($wishlist_items)): ?><p class="lead">Your wishlist is empty. <a href="index.php">Browse packages</a>.</p>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($wishlist_items as $p): 
          $feat = isset($p['features']) ? (json_decode($p['features'], true) ?: []) : [$p['description']]; 
          $is_pkg = isset($p['features']);
        ?>
          <div class="card" style="margin-top:0;">
            <h3><?= esc($p['name']) ?></h3>
            <div class="price"><?= gbp($p['price']) ?><?php if ($is_pkg): ?> <small>/ month</small><?php endif; ?></div>
            <div class="card-meta">
              <span class="meta-sales">👤.<?= $p['sales_count'] ?> purchased</span>
              <?php if ($p['rating_count'] > 0): ?>
              <span class="meta-rating">⭐ <?= number_format($p['rating_score'], 1) ?> (👤.<?= $p['rating_count'] ?> rated)</span>
            <?php endif; ?>
              <span class="meta-stock">📦 <?= $p['inventory'] ?> left</span>
            </div>
            <ul><?php foreach ($feat as $f): ?><li><?= esc($f) ?></li><?php endforeach; ?></ul>
            <?php if (!empty($p['is_custom_req'])): ?>
              <a class="btn" href="checkout.php?custom=<?= urlencode($p['name']) ?>&cprice=<?= $p['price'] ?>&code=custom">Order this</a>
            <?php else: ?>
              <a class="btn" href="checkout.php?code=<?= urlencode($p['code']) ?>">Order this</a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  <?php elseif ($tab === 'reviews'): ?>
    <!-- Reviews Tab for BUG-018 -->
    <div class="acc-head">⭐ Submit a Product Review</div>
    <?php if (!empty($_GET['msg'])): ?><div class="msg ok"><?= esc($_GET['msg']) ?></div><?php endif; ?>
    
    <?php if (empty($purchasedItems)): ?>
      <p class="lead">You have not purchased any products yet. Reviews are only allowed after a successful purchase.</p>
    <?php else: ?>
      <form method="post" style="margin-bottom:30px;">
        <input type="hidden" name="act" value="submitReview">
        <div class="fg">
          <label>Select Purchased Product/Package</label>
          <select name="item_code" required>
            <?php foreach ($purchasedItems as $cd => $nm): ?>
              <option value="<?= esc($cd) ?>" <?= (isset($_GET['prefill_code']) && $_GET['prefill_code'] === $cd) ? 'selected' : '' ?>><?= esc($nm) ?> (<?= esc($cd) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg">
          <label>Rating (1 to 5 Stars)</label>
          <select name="rating" required>
            <option value="5">⭐⭐⭐⭐⭐ (5 Stars)</option>
            <option value="4">⭐⭐⭐⭐ (4 Stars)</option>
            <option value="3">⭐⭐⭐ (3 Stars)</option>
            <option value="2">⭐⭐ (2 Stars)</option>
            <option value="1">⭐ (1 Star)</option>
          </select>
        </div>
        <div class="fg">
          <label>Your Comment</label>
          <textarea name="comment" rows="3" placeholder="Tell us what you think of this product..." required></textarea>
        </div>
        <button class="btn" type="submit">Submit Review</button>
      </form>
    <?php endif; ?>
    
    <div class="acc-head" style="margin-top:20px;">⭐ Your Reviews History</div>
    <?php if (empty($reviews)): ?>
      <p class="lead">You have not submitted any reviews yet.</p>
    <?php else: ?>
      <div class="table-scroll"><table>
        <thead><tr><th>Date</th><th>Item Code</th><th>Rating</th><th>Comment</th><th style="text-align:center;">Actions</th></tr></thead>
        <tbody>
          <?php foreach ($reviews as $r): ?>
            <tr>
              <td><?= esc(substr($r['created_at'],0,10)) ?></td>
              <td><code><?= esc($r['item_code']) ?></code></td>
              <td style="color:var(--brand);"><?= str_repeat('⭐', $r['rating']) ?></td>
              <td><?= esc($r['comment']) ?></td>
              <td style="text-align:center;">
                <div style="display:inline-flex; gap:8px;">
                  <button type="button" class="btn small sec" style="padding:2px 8px; font-size:11px;" 
                          onclick="prefillReviewEdit('<?= esc($r['item_code']) ?>', <?= $r['rating'] ?>, '<?= esc(rawurlencode($r['comment'])) ?>')">
                    Edit
                  </button>
                  <form method="post" onsubmit="return confirm('Delete this review?');" style="margin:0;">
                    <input type="hidden" name="act" value="deleteReview">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button type="submit" class="btn small danger" style="padding:2px 8px; font-size:11px;">
                      Delete
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php endif; ?>

  <?php else:
    $hasDeal = false; foreach ($orders as $o) if (preg_match('/Bundle|Triple|Double/', $o['package_name'])) $hasDeal = true;
    $code = $hasDeal ? 'SAVE10' : 'WELCOME5';
  ?>
    <div class="acc-head">💳 Billing &amp; orders</div>
    <div class="offer-banner"><div>🎁 Your offer code: <code><?= $code ?></code></div><a class="btn small" href="checkout.php">Pay / order now</a></div>
    <?php if (!$orders): ?><p class="lead">No orders yet. <a href="index.php">Browse packages</a>.</p>
    <?php else: ?>
      <div class="table-scroll"><table>
        <thead><tr><th>Date</th><th>Package</th><th>Saved</th><th>Total paid</th><th>Status</th><th>Action</th></tr></thead>
        <tbody><?php foreach ($orders as $o): ?>
          <tr>
            <td><?= esc(substr($o['created_at'],0,10)) ?></td>
            <td>
              <?php if (strpos($o['package_name'], 'Cart (') === 0): ?>
                <?php
                  $o_id = (int)$o['id'];
                  $stmt_items = $pdo->prepare("SELECT item_name, count(*) as qty FROM order_items WHERE order_id = ? GROUP BY item_name");
                  $stmt_items->execute([$o_id]);
                  $items_list = $stmt_items->fetchAll();
                  $item_names = [];
                  foreach ($items_list as $it) {
                      $item_names[] = esc($it['item_name']) . ($it['qty'] > 1 ? ' (x' . $it['qty'] . ')' : '');
                  }
                  echo implode(', ', $item_names);
                ?>
              <?php else: ?>
                <?= esc($o['package_name']) ?>
              <?php endif; ?>
            </td>
            <td class="discount"><?= gbp($o['saved']) ?></td>
            <td><?= gbp($o['total']) ?></td>
            <td><?= esc($o['status']) ?></td>
            <td>
              <?php
                // Find matching item code for this order to prefill (BUG-Billing/Evaluate)
                $o_id = (int)$o['id'];
                $stmt_items = $pdo->prepare("SELECT item_name FROM order_items WHERE order_id = ?");
                $stmt_items->execute([$o_id]);
                $name_check = $stmt_items->fetchColumn();
                if (!$name_check) {
                    $name_check = $o['package_name'];
                }
                
                $s_pkg = $pdo->prepare("SELECT code FROM packages WHERE name = ?");
                $s_pkg->execute([$name_check]);
                $code_val = $s_pkg->fetchColumn();
                if (!$code_val) {
                    $s_prd = $pdo->prepare("SELECT code FROM products WHERE name = ?");
                    $s_prd->execute([$name_check]);
                    $code_val = $s_prd->fetchColumn();
                }
                if (!$code_val) {
                    $code_val = '';
                }
              ?>
              <a class="btn small" href="account.php?tab=reviews&prefill_code=<?= urlencode($code_val) ?>" style="padding:4px 10px; font-size:11px;">Evaluate</a>
            </td>
          </tr>
        <?php endforeach; ?></tbody>
      </table></div>
    <?php endif; ?>
  <?php endif; ?>
  </div></section>
</div>

<script>
function prefillReviewEdit(itemCode, rating, rawComment) {
    const itemSelect = document.querySelector('select[name="item_code"]');
    const ratingSelect = document.querySelector('select[name="rating"]');
    const commentTextarea = document.querySelector('textarea[name="comment"]');
    const submitBtn = document.querySelector('form[method="post"] button[type="submit"]');
    
    if (itemSelect) itemSelect.value = itemCode;
    if (ratingSelect) ratingSelect.value = rating;
    if (commentTextarea) commentTextarea.value = decodeURIComponent(rawComment.replace(/\+/g, ' '));
    if (submitBtn) submitBtn.textContent = 'Update Review';
    
    const formEl = document.querySelector('form[method="post"] select[name="item_code"]').closest('form');
    if (formEl) {
        formEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

document.addEventListener('DOMContentLoaded', () => {
  const sendSecBtn = document.getElementById('sendSecCodeBtn');
  if (sendSecBtn) {
    sendSecBtn.addEventListener('click', () => {
      sendSecBtn.disabled = true;
      sendSecBtn.textContent = 'Sending...';
      
      fetch('account.php?action=send_security_code')
      .then(res => res.json())
      .then(data => {
        if (data.error) {
          toast(data.error);
          sendSecBtn.disabled = false;
          sendSecBtn.textContent = 'Get Code';
        } else {
          toast(data.message || 'Code sent successfully!');
          let seconds = 60;
          const timer = setInterval(() => {
            seconds--;
            if (seconds <= 0) {
              clearInterval(timer);
              sendSecBtn.disabled = false;
              sendSecBtn.textContent = 'Get Code';
            } else {
              sendSecBtn.textContent = `Retry in ${seconds}s`;
            }
          }, 1000);
        }
      })
      .catch(err => {
        console.error(err);
        toast('Failed to send code.');
        sendSecBtn.disabled = false;
        sendSecBtn.textContent = 'Get Code';
      });
    });
  }
});
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const avInput = document.querySelector('input[name="avatar"]');
  if (avInput) {
    avInput.addEventListener('change', function(){
      if (this.files && this.files[0] && this.files[0].size > 5242880) {
        alert('File size exceeds 5MB limit. Please select a smaller avatar image.');
        this.value = '';
      }
    });
  }
});
</script>