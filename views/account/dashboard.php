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
    <?php
      $group1Active = in_array($tab, ['overview', 'personal', 'security'], true);
      $group2Active = in_array($tab, ['billing', 'usage', 'wishlist', 'reviews', 'order_process'], true);
      $group3Active = false; // support & logout
    ?>
    <style>
      .acc-nav-header {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        color: var(--cyan, #00e5ff);
        padding: 8px 10px 6px;
        margin-bottom: 2px;
        opacity: 0.9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        user-select: none;
        transition: opacity 0.2s ease, background 0.2s ease;
        border-radius: 6px;
      }
      .acc-nav-header:hover {
        opacity: 1;
        background: rgba(0, 229, 255, 0.1);
      }
      .acc-nav-header .toggle-arrow {
        font-size: 10px;
        transition: transform 0.25s ease;
        display: inline-block;
      }
      .acc-nav-group {
        display: flex;
        flex-direction: column;
        gap: 3px;
      }
      .acc-nav-subitems {
        display: flex;
        flex-direction: column;
        gap: 3px;
        transition: all 0.3s ease;
      }
      .acc-nav-group.collapsed .acc-nav-subitems {
        display: none !important;
      }
      .acc-nav-group.collapsed .toggle-arrow {
        transform: rotate(-90deg);
      }
    </style>
    
<style>
  @media (max-width: 650px) {
    .step-node { width: 70px !important; }
    .step-node .node-circle { width: 32px !important; height: 32px !important; font-size: 13px !important; }
    .step-node div:last-child { font-size: 10px !important; }
  }
</style>
    <nav class="acc-nav" id="cdAccNav">
      <div class="acc-nav-group <?= $group1Active ? '' : 'collapsed' ?>" data-group-id="my_account">
        <div class="acc-nav-header" onclick="cdToggleAccNav(this)" title="Click to collapse/expand">
          <span>👤 MY ACCOUNT</span>
          <span class="toggle-arrow">▾</span>
        </div>
        <div class="acc-nav-subitems">
          <a class="<?= $tab==='overview'?'active':'' ?>" href="account.php?tab=overview">📊 Overview</a>
          <a class="<?= $tab==='personal'?'active':'' ?>" href="account.php?tab=personal">✏️ Personal Info</a>
          <a class="<?= $tab==='security'?'active':'' ?>" href="account.php?tab=security">🔑 Security</a>
        </div>
      </div>

      <div class="acc-nav-group <?= $group2Active ? '' : 'collapsed' ?>" style="margin-top:10px;" data-group-id="order_management">
        <div class="acc-nav-header" onclick="cdToggleAccNav(this)" title="Click to collapse/expand">
          <span>📦 ORDER MANAGEMENT</span>
          <span class="toggle-arrow">▾</span>
        </div>
        <div class="acc-nav-subitems">
          <a class="<?= $tab==='billing'?'active':'' ?>" href="account.php?tab=billing">💳 Billing &amp; Orders</a>
          <a class="<?= $tab==='usage'?'active':'' ?>" href="account.php?tab=usage">📈 Usage &amp; Services</a>
          <a class="<?= $tab==='wishlist'?'active':'' ?>" href="account.php?tab=wishlist">💖 Wishlist</a>
          <a class="<?= $tab==='reviews'?'active':'' ?>" href="account.php?tab=reviews">⭐️ My Reviews</a>
          <a class="<?= $tab==='order_process'?'active':'' ?>" href="account.php?tab=order_process">🚚 Order process</a>
        </div>
      </div>

      <div class="acc-nav-group <?= $group3Active ? '' : 'collapsed' ?>" style="margin-top:10px;" data-group-id="system_help">
        <div class="acc-nav-header" onclick="cdToggleAccNav(this)" title="Click to collapse/expand">
          <span>⚙️ SYSTEM &amp; HELP</span>
          <span class="toggle-arrow">▾</span>
        </div>
        <div class="acc-nav-subitems">
          <a href="support.php">💬 Support</a>
          <a class="out" href="logout.php">⏻ Sign Out</a>
        </div>
      </div>
    </nav>
    <script>
      (function() {
        // Restore manual user toggle preferences from localStorage if available
        try {
          var groups = document.querySelectorAll('#cdAccNav .acc-nav-group');
          groups.forEach(function(g) {
            var gId = g.getAttribute('data-group-id');
            var savedState = localStorage.getItem('cd_nav_' + gId);
            // If user explicitly interacted with this group in localStorage, apply saved preference
            // Unless this group contains an active link (.active), which stays open for usability
            if (savedState !== null && !g.querySelector('a.active')) {
              if (savedState === 'collapsed') {
                g.classList.add('collapsed');
              } else if (savedState === 'expanded') {
                g.classList.remove('collapsed');
              }
            }
          });
        } catch(e) {}
      })();

      function cdToggleAccNav(headerEl) {
        var group = headerEl.closest('.acc-nav-group');
        if (group) {
          group.classList.toggle('collapsed');
          var isCollapsed = group.classList.contains('collapsed');
          var gId = group.getAttribute('data-group-id');
          if (gId) {
            try {
              localStorage.setItem('cd_nav_' + gId, isCollapsed ? 'collapsed' : 'expanded');
            } catch(e) {}
          }
        }
      }
    </script>
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
    <div class="hint">Your first order will automatically save you 15% and earn you reward points!</div>

  <?php elseif ($tab === 'personal'): ?>
    <div class="acc-head">✏️ Personal Info</div>
    <?php if (!empty($_GET['msg'])): ?><div class="msg ok"><?= esc($_GET['msg']) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="act" value="updateProfile">
      
      <!-- Avatar upload (BUG-019) -->
      <div class="fg">
        <label>Profile Avatar</label>
        <div class="custom-file-wrap" style="display:flex; align-items:center; gap:10px; margin-top:6px; margin-bottom:6px;">
          <label style="cursor:pointer; margin:0; font-size:12px; padding:6px 14px; background:var(--card); border:1px solid var(--line); border-radius:6px; color:var(--ink); font-weight:600; display:inline-block;">
            Choose File
            <input type="file" name="avatar" accept="image/*" style="display:none;" onchange="var fn=document.getElementById('avatar-filename'); if(fn){ fn.textContent = this.files[0] ? this.files[0].name : 'No file chosen'; }">
          </label>
          <span id="avatar-filename" style="font-size:12px; color:var(--muted);">No file chosen</span>
        </div>
        <small style="color:var(--muted); margin-top:4px; display:block;">Upload a JPG, PNG, or GIF file.</small>
      </div>
      
      <div class="fg"><label>Full name</label><input name="name" value="<?= esc($ME['name']) ?>" required></div>
      <div class="fg"><label>Email (cannot be changed)</label><input value="<?= esc($ME['email']) ?>" disabled></div>
      <div class="fg"><label>Address</label><input name="address" value="<?= esc($ME['address'] ?? '') ?>" required></div>
      
      <div class="two-col">
        <div class="fg"><label>Telephone</label><input name="phone" maxlength="10" pattern="[0-9]{10}" oninput="this.value=this.value.replace(/\D/g,'').slice(0,10)" value="<?= esc($ME['phone'] ?? '') ?>" required></div>
        <div class="fg"><label>Credit card number (optional)</label><input name="card" maxlength="19" value="<?= esc($ME['card'] ?? '') ?>" placeholder="Leave empty if not set"></div>
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
        <div class="fg"><label>New Password</label><input name="new_password" id="sec-new-password" type="password" required>
<div id="sec-pwd-meter" style="margin-top:6px;display:none;"><div style="height:6px;background:#333;border-radius:3px;overflow:hidden;margin-bottom:4px;"><div id="sec-pwd-bar" style="height:100%;width:0%;background:#e74c3c;transition:all 0.3s;"></div></div><div style="font-size:0.8rem;display:flex;gap:10px;color:var(--text-muted);"><span id="sec-chk-len">✕ Min 6 chars</span><span id="sec-chk-upper">✕ Uppercase</span><span id="sec-chk-num">✕ Number</span><span id="sec-chk-spec">✕ Special char</span></div></div></div>
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

    <?php elseif ($tab === 'order_process'): ?>
    <div class="acc-head" style="font-size:18px; margin-bottom:20px;">🚚 Order Process &amp; Live Tracking</div>
    
    <!-- 30-Second Animated 4-Step Stepper Component -->
    <div class="panel" style="margin-bottom:30px; padding:24px; border:1px solid rgba(0,229,255,0.25); background:linear-gradient(135deg, rgba(7,11,22,0.95), rgba(15,23,42,0.95)); border-radius:14px; box-shadow:0 8px 32px rgba(0,0,0,0.4);">
      <div style="font-size:14px; font-weight:700; color:var(--cyan); margin-bottom:20px; display:flex; align-items:center; justify-content:space-between;">
        <span>Live Delivery Progress</span>
        <span id="stepper-time-status" style="font-size:12px; color:var(--muted); font-weight:500;">Status: Step 1 of 4</span>
      </div>

      <div style="position:relative; margin:30px 20px 50px;">
        <!-- Stepper Background Line -->
        <div style="position:absolute; top:20px; left:0; width:100%; height:4px; background:rgba(255,255,255,0.1); border-radius:2px; z-index:1;"></div>
        <!-- Animated Progress Fill Line -->
        <div id="stepper-progress-bar" style="position:absolute; top:20px; left:0; width:0%; height:4px; background:linear-gradient(90deg, #00f2fe 0%, #4facfe 50%, #00e5ff 100%); border-radius:2px; z-index:2; transition:width 1s linear;"></div>

        <!-- 4 Step Circular Nodes -->
        <div style="position:relative; z-index:3; display:flex; justify-content:space-between;">
          <!-- Node 1 -->
          <div class="step-node" id="node-step-1" style="text-align:center; width:100px;">
            <div class="node-circle" style="width:40px; height:40px; border-radius:50%; background:var(--card); border:2px solid var(--cyan); color:var(--cyan); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:16px; margin:0 auto 10px; transition:all 0.4s ease; box-shadow:0 0 12px rgba(0,229,255,0.4);">1</div>
            <div style="font-size:12px; font-weight:700; color:var(--heading); line-height:1.3;">Awaiting confirmation</div>
          </div>
          <!-- Node 2 -->
          <div class="step-node" id="node-step-2" style="text-align:center; width:100px;">
            <div class="node-circle" style="width:40px; height:40px; border-radius:50%; background:var(--card); border:2px solid var(--line); color:var(--muted); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:16px; margin:0 auto 10px; transition:all 0.4s ease;">2</div>
            <div style="font-size:12px; font-weight:700; color:var(--muted); line-height:1.3;">Awaiting pickup</div>
          </div>
          <!-- Node 3 -->
          <div class="step-node" id="node-step-3" style="text-align:center; width:100px;">
            <div class="node-circle" style="width:40px; height:40px; border-radius:50%; background:var(--card); border:2px solid var(--line); color:var(--muted); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:16px; margin:0 auto 10px; transition:all 0.4s ease;">3</div>
            <div style="font-size:12px; font-weight:700; color:var(--muted); line-height:1.3;">Out for delivery</div>
          </div>
          <!-- Node 4 -->
          <div class="step-node" id="node-step-4" style="text-align:center; width:100px;">
            <div class="node-circle" style="width:40px; height:40px; border-radius:50%; background:var(--card); border:2px solid var(--line); color:var(--muted); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:16px; margin:0 auto 10px; transition:all 0.4s ease;">4</div>
            <div style="font-size:12px; font-weight:700; color:var(--muted); line-height:1.3;">Delivered</div>
          </div>
        </div>
      </div>
    </div>

    <script>
      (function() {
        var duration = 30; // 30 seconds total
        var startTime = Date.now();
        
        function updateStepper() {
          var elapsed = (Date.now() - startTime) / 1000;
          var cycleTime = elapsed % duration;
          var pct = (cycleTime / duration) * 100;
          
          var progressBar = document.getElementById('stepper-progress-bar');
          var statusTxt = document.getElementById('stepper-time-status');
          if (progressBar) progressBar.style.width = pct + '%';
          
          var currentStep = 1;
          if (pct >= 100) currentStep = 4;
          else if (pct >= 66.6) currentStep = 4;
          else if (pct >= 33.3) currentStep = 3;
          else if (pct >= 10) currentStep = 2;
          
          for (var i = 1; i <= 4; i++) {
            var node = document.getElementById('node-step-' + i);
            if (node) {
              var circle = node.querySelector('.node-circle');
              var label = node.querySelector('div:last-child');
              if (i <= currentStep) {
                circle.style.border = '2px solid var(--cyan)';
                circle.style.background = 'linear-gradient(135deg, #00f2fe 0%, #4facfe 100%)';
                circle.style.color = '#000';
                circle.style.boxShadow = '0 0 15px rgba(0,229,255,0.6)';
                if (label) label.style.color = 'var(--cyan)';
              } else {
                circle.style.border = '2px solid var(--line)';
                circle.style.background = 'var(--card)';
                circle.style.color = 'var(--muted)';
                circle.style.boxShadow = 'none';
                if (label) label.style.color = 'var(--muted)';
              }
            }
          }
          
          if (statusTxt) {
            var stepNames = ['Awaiting confirmation', 'Awaiting pickup', 'Out for delivery', 'Delivered'];
            statusTxt.textContent = 'Status: Step ' + currentStep + ' of 4 (' + stepNames[currentStep-1] + ')';
          }
        }
        
        updateStepper();
        setInterval(updateStepper, 500);
      })();
    </script>

    <!-- Purchased Hardware Products Details Section -->
    <h3 class="sec-h" style="font-size:16px; margin-bottom:14px;">🛍️ Purchased Hardware Items &amp; Delivery Details</h3>
    <?php
      $hwOrders = [];
      if (!empty($orders)) {
          foreach ($orders as $ord) {
              $st = strtolower($ord['status'] ?? '');
              if ($st !== 'cancelled') {
                  $hwOrders[] = $ord;
              }
          }
      }
    ?>
    <?php if (empty($hwOrders)): ?>
      <div class="panel" style="padding:20px; text-align:center; color:var(--muted);">
        No hardware orders found. <a href="index.php" style="color:var(--cyan); font-weight:bold;">Browse hardware packages</a> to place an order.
      </div>
    <?php else: ?>
      <div style="display:grid; gap:16px;">
        <?php foreach ($hwOrders as $ho): ?>
          <?php
            $itemCode = $ho['package_code'] ?? 'prod-iphone15';
            $itemName = $ho['package_name'] ?? 'Hardware Device';
            $itemImg  = function_exists('cd_product_card_image') ? cd_product_card_image($itemCode, 'Hardware') : '';
          ?>
          <div class="panel" style="padding:18px; border:1px solid rgba(0,229,255,0.18); background:rgba(15,23,42,0.8); border-radius:10px; display:flex; gap:18px; align-items:center; flex-wrap:wrap;">
            <?php if ($itemImg !== ''): ?>
              <div style="width:90px; height:80px; background:rgba(0,0,0,0.3); border-radius:8px; padding:6px; display:flex; align-items:center; justify-content:center; border:1px solid var(--line);">
                <img src="<?= htmlspecialchars($itemImg, ENT_QUOTES) ?>" alt="<?= esc($itemName) ?>" style="max-width:100%; max-height:100%; object-fit:contain;" />
              </div>
            <?php endif; ?>

            <div style="flex:1; min-width:220px;">
              <div style="font-size:16px; font-weight:800; color:var(--heading); margin-bottom:4px;"><?= esc($itemName) ?></div>
              <div style="font-size:13px; color:var(--cyan); font-weight:700; margin-bottom:6px;"><?= gbp($ho['total']) ?></div>
              <div style="font-size:12px; color:var(--muted); line-height:1.4;">
                <div>📍 <strong>Delivery Address:</strong> <?= esc($ME['address'] ?? 'Greenwich, London') ?></div>
                <div>📞 <strong>Contact Phone:</strong> <?= esc($ME['phone'] ?? 'N/A') ?></div>
                <div>📅 <strong>Order Date:</strong> <?= esc(substr($ho['created_at'], 0, 10)) ?> (Order #<?= $ho['id'] ?>)</div>
              </div>
            </div>

            <div>
              <span style="font-size:11px; font-weight:800; padding:5px 12px; border-radius:20px; text-transform:uppercase; background:rgba(0,229,255,0.15); color:var(--cyan); border:1px solid rgba(0,229,255,0.3);">
                <?= esc($ho['status'] ?? 'Paid') ?>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
<?php elseif ($tab === 'billing'):
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
              <?php
                if (strpos($o['package_name'], 'Cart (') === 0) {
                    $o_id = (int)$o['id'];
                    $stmt_items = $pdo->prepare("SELECT item_name, count(*) as qty FROM order_items WHERE order_id = ? GROUP BY item_name");
                    $stmt_items->execute([$o_id]);
                    $items_list = $stmt_items->fetchAll();
                    if (!empty($items_list)) {
                        $item_names = [];
                        foreach ($items_list as $it) {
                            $item_names[] = esc($it['item_name']) . ($it['qty'] > 1 ? ' (x' . $it['qty'] . ')' : '');
                        }
                        echo implode(', ', $item_names);
                    } else {
                        echo esc($o['package_name']);
                    }
                } else {
                    echo esc($o['package_name']);
                }
              ?>
            </td>
            <td class="discount"><?= gbp($o['saved'] ?? 0) ?></td>
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
              <div style="display:flex; flex-direction:column; gap:8px; align-items:center;">
                <a class="btn small" href="account.php?tab=reviews&prefill_code=<?= urlencode($code_val) ?>" style="padding:4px 10px; font-size:11px; width:100%; text-align:center;">Evaluate</a>
                <?php
                  $orderCreated = strtotime($o['created_at']);
                  $minsPassed = (time() - $orderCreated) / 60;
                  $st = strtolower($o['status'] ?? '');
                  if ($minsPassed <= 5 && $st !== 'cancelled' && $st !== 'refunded' && $st !== 'refund requested'):
                ?>
                  <a class="btn small btn-cancel-order" href="cancel.php?id=<?= $o['id'] ?>" data-created="<?= $o['created_at'] ?>" style="background:#ff3547; color:#fff; padding:4px 10px; font-size:11px; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; border-radius:4px; width:100%; box-sizing:border-box;">Cancel <span class="cancel-timer-badge" data-created="<?= $o['created_at'] ?>" style="font-size:10px; color:#fff; margin-left:4px;"></span></a>
                  <a class="btn small btn-refund-dyn" href="account.php?tab=billing&act=refund&id=<?= $o['id'] ?>" onclick="return confirm('Do you want to request a return / refund for this order?');" style="display:none; background:linear-gradient(135deg, #ff007f 0%, #7928ca 100%); color:#fff; padding:4px 10px; font-size:11px; text-decoration:none; align-items:center; justify-content:center; border-radius:4px; width:100%; box-sizing:border-box; font-weight:bold; margin-top:4px;">Refund</a>
                <?php elseif ($st === 'refunded' || $st === 'refund requested'): ?>
                  <span style="font-size:11px; color:var(--cyan); font-weight:bold; padding:4px 8px; background:rgba(0,229,255,0.1); border-radius:4px; border:1px solid rgba(0,229,255,0.3); text-align:center; display:block;">Refund Requested</span>
                <?php elseif ($st !== 'cancelled'): ?>
                  <a class="btn small" href="account.php?tab=billing&act=refund&id=<?= $o['id'] ?>" onclick="return confirm('Do you want to request a return / refund for this order?');" style="background:linear-gradient(135deg, #ff007f 0%, #7928ca 100%); color:#fff; padding:4px 10px; font-size:11px; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; border-radius:4px; width:100%; box-sizing:border-box; font-weight:bold;">Refund</a>
                <?php endif; ?>
              </div>
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
<script>
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.btn-cancel-order').forEach(function(btn){
    const createdStr = btn.dataset.created;
    if (!createdStr) return;
    const createdTime = new Date(createdStr).getTime();
    const deadline = createdTime + (299 * 1000);
    const badge = btn.nextElementSibling;

    function updateTimer() {
      const now = new Date().getTime();
      const diff = deadline - now;
      if (diff <= 0) {
        btn.style.display = 'none';
        if (badge) badge.innerText = '(15-min cancel limit expired)';
      } else {
        const m = Math.floor(diff / 60000);
        const s = Math.floor((diff % 60000) / 1000);
        if (badge) badge.innerText = '(Time left: ' + m + ':' + (s < 10 ? '0' : '') + s + ' to cancel)';
      }
    }
    updateTimer();
    setInterval(updateTimer, 1000);
  });
});
</script>

<script>
function updateCancelTimers() {
  document.querySelectorAll('.btn-cancel-order').forEach(function(btn){
    const createdStr = btn.dataset.created;
    if (!createdStr) return;
    const createdTime = new Date(createdStr.replace(/-/g, '/')).getTime();
    const deadline = createdTime + (299 * 1000);
    const badge = btn.querySelector('.cancel-timer-badge');
    const now = new Date().getTime();
    const diff = deadline - now;

    if (diff <= 0) {
      btn.style.display = 'none';
    } else {
      const m = Math.floor(diff / 60000);
      const s = Math.floor((diff % 60000) / 1000);
      if (badge) badge.innerText = '(' + m + ':' + (s < 10 ? '0' : '') + s + ')';
    }
  });
}
document.addEventListener('DOMContentLoaded', function(){
  updateCancelTimers();
  setInterval(updateCancelTimers, 1000);
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const secInput = document.getElementById('sec-new-password');
  const secMeter = document.getElementById('sec-pwd-meter');
  const secBar = document.getElementById('sec-pwd-bar');
  const secLen = document.getElementById('sec-chk-len');
  const secUpper = document.getElementById('sec-chk-upper');
  const secNum = document.getElementById('sec-chk-num');
  const secSpec = document.getElementById('sec-chk-spec');

  if(secInput && secMeter){
    secInput.addEventListener('input', function(){
      const val = secInput.value;
      if(!val){ secMeter.style.display = 'none'; return; }
      secMeter.style.display = 'block';
      const hasLen = val.length >= 6;
      const hasUpper = /[A-Z]/.test(val);
      const hasNum = /[0-9]/.test(val);
      const hasSpec = /[\W_]/.test(val);

      secLen.innerHTML = (hasLen ? '✓' : '✕') + ' Min 6 chars'; secLen.style.color = hasLen ? '#2ecc71' : '#e74c3c';
      secUpper.innerHTML = (hasUpper ? '✓' : '✕') + ' Uppercase'; secUpper.style.color = hasUpper ? '#2ecc71' : '#e74c3c';
      secNum.innerHTML = (hasNum ? '✓' : '✕') + ' Number'; secNum.style.color = hasNum ? '#2ecc71' : '#e74c3c';
      secSpec.innerHTML = (hasSpec ? '✓' : '✕') + ' Special char'; secSpec.style.color = hasSpec ? '#2ecc71' : '#e74c3c';

      let score = (hasLen?1:0) + (hasUpper?1:0) + (hasNum?1:0) + (hasSpec?1:0);
      secBar.style.width = (score / 4 * 100) + '%';
      secBar.style.background = score <= 1 ? '#e74c3c' : score <= 3 ? '#f39c12' : '#2ecc71';
    });
  }
});
</script>