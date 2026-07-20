<?php
$cur = 'staff.php'; $pageTitle = 'Staff Panel';
require __DIR__ . '/../layout/header.php';
?>
<h2 class="title">Staff Panel</h2>
<p class="lead">Change prices, update inventory levels, manage hardware, generate vouchers, answer feedback.</p>
<div class="tabs">
  <a class="<?= $tab==='packages'?'active':'' ?>" href="staff.php?tab=packages">📦 Packages &amp; Products</a>
  <a class="<?= $tab==='feedback'?'active':'' ?>" href="staff.php?tab=feedback">💬 Feedback</a>
  <a class="<?= $tab==='requirements'?'active':'' ?>" href="staff.php?tab=requirements">📋 Requirements</a>
  <a class="<?= $tab==='orders'?'active':'' ?>" href="staff.php?tab=orders">🧾 Orders</a>
  <a class="<?= $tab==='vouchers'?'active':'' ?>" href="staff.php?tab=vouchers">🎟️ Vouchers</a>
</div>

<?php if ($tab === 'packages'): ?>
  <?php if (!empty($_GET['msg'])): ?><div class="msg ok"><?= esc($_GET['msg']) ?></div><?php endif; ?>
  
  <h3 class="sec-h">1. Telecom Subscription Packages</h3>
  <div class="table-scroll"><table>
    <thead><tr><th>Name</th><th>Category</th><th>Tier</th><th>Price (£)</th><th>Inventory</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($pkgs as $p): ?>
      <tr>
        <td><?= esc($p['name']) ?></td><td><?= esc($p['category']) ?></td><td><?= esc($p['tier']) ?></td>
        <form method="post" style="margin:0">
          <input type="hidden" name="act" value="savePrice"><input type="hidden" name="code" value="<?= esc($p['code']) ?>"><input type="hidden" name="back" value="packages">
          <td>
            <input name="price" type="number" step="0.01" value="<?= $p['price'] ?>" style="width:80px;padding:6px;border:1px solid var(--line);border-radius:7px;background:var(--card);color:var(--ink)">
          </td>
          <td>
            <input name="inventory" type="number" value="<?= $p['inventory'] ?>" style="width:70px;padding:6px;border:1px solid var(--line);border-radius:7px;background:var(--card);color:var(--ink)">
          </td>
          <td>
            <button class="btn small" type="submit">Save</button>
          </td>
        </form>
        <td>
          <form method="post" onsubmit="return confirm('Delete this package?')" style="margin:0">
            <input type="hidden" name="act" value="delPkg"><input type="hidden" name="code" value="<?= esc($p['code']) ?>"><input type="hidden" name="back" value="packages">
            <button class="btn small danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>

  <div class="panel" style="max-width:520px;margin-top:18px">
    <h3 class="sec-h" style="margin-top:0">Add new Telecom Option</h3>
    <form method="post">
      <input type="hidden" name="act" value="addPkg"><input type="hidden" name="back" value="packages">
      <div class="fg"><label>Name</label><input name="name" required></div>
      <div class="two-col">
        <div class="fg"><label>Category</label><select name="category"><?php foreach (['Mobile','Broadband','Tablet','Bundles'] as $c): ?><option><?= $c ?></option><?php endforeach; ?></select></div>
        <div class="fg"><label>Tier</label><select name="tier"><?php foreach (['Lite','Standard','Premium','Deal'] as $t): ?><option><?= $t ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="two-col">
        <div class="fg"><label>Price (£/month)</label><input name="price" type="number" step="0.01" value="20" required></div>
        <div class="fg"><label>Inventory Stock</label><input name="inventory" type="number" value="10" required></div>
      </div>
      <div class="fg"><label>Features (one per line)</label><textarea name="features" rows="3" placeholder="1 mobile phone&#10;500 free minutes" required></textarea></div>
      <button class="btn" type="submit">Create option</button>
    </form>
  </div>

  <hr style="border:0; border-top: 1px solid var(--line); margin: 30px 0;">

  <h3 class="sec-h">2. Hardware Devices (Phần cứng)</h3>
  <div class="table-scroll"><table>
    <thead><tr><th>Name</th><th>Price (£)</th><th>Inventory</th><th>Description</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($prods as $p): ?>
      <tr>
        <td><strong><?= esc($p['name']) ?></strong></td>
        <form method="post" style="margin:0">
          <input type="hidden" name="act" value="saveProdPrice"><input type="hidden" name="code" value="<?= esc($p['code']) ?>"><input type="hidden" name="back" value="packages">
          <td>
            <input name="price" type="number" step="0.01" value="<?= $p['price'] ?>" style="width:80px;padding:6px;border:1px solid var(--line);border-radius:7px;background:var(--card);color:var(--ink)">
          </td>
          <td>
            <input name="inventory" type="number" value="<?= $p['inventory'] ?>" style="width:70px;padding:6px;border:1px solid var(--line);border-radius:7px;background:var(--card);color:var(--ink)">
          </td>
          <td>
            <textarea name="description" style="width:200px;height:45px;padding:6px;border:1px solid var(--line);border-radius:7px;background:var(--card);color:var(--ink);font-size:11px;"><?= esc($p['description']) ?></textarea>
          </td>
          <td>
            <button class="btn small" type="submit">Save</button>
          </td>
        </form>
        <td>
          <form method="post" onsubmit="return confirm('Delete this hardware product?')" style="margin:0">
            <input type="hidden" name="act" value="delProd"><input type="hidden" name="code" value="<?= esc($p['code']) ?>"><input type="hidden" name="back" value="packages">
            <button class="btn small danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>

  <div class="panel" style="max-width:520px;margin-top:18px">
    <h3 class="sec-h" style="margin-top:0">Add new Hardware Device</h3>
    <form method="post">
      <input type="hidden" name="act" value="addProd"><input type="hidden" name="back" value="packages">
      <div class="fg"><label>Product Name</label><input name="name" placeholder="e.g. CheapDeals Gaming Laptop" required></div>
      <div class="two-col">
        <div class="fg"><label>Price (£)</label><input name="price" type="number" step="0.01" value="29.99" required></div>
        <div class="fg"><label>Inventory Stock</label><input name="inventory" type="number" value="10" required></div>
      </div>
      <div class="fg"><label>Product Description</label><textarea name="description" rows="3" placeholder="Enter short product specifications..." required></textarea></div>
      <button class="btn" type="submit">Create Hardware</button>
    </form>
  </div>

<?php elseif ($tab === 'feedback'): ?>
  <div class="acc-layout">
    <aside><div class="panel" style="padding:14px"><h3 class="sec-h" style="margin:0 0 10px">Conversations</h3>
      <?php if (!$threads): ?><p class="lead">No conversations yet.</p><?php endif; ?>
      <?php foreach ($threads as $t): ?>
        <a class="thread <?= $uid===(int)$t['id']?'active':'' ?>" href="staff.php?tab=feedback&user_id=<?= $t['id'] ?>" style="text-decoration:none; display:flex; justify-content:space-between; align-items:center;">
          <div>
            <strong><?= esc($t['name']) ?></strong>
            <div style="font-size:12px;color:var(--muted)"><?= esc($t['email']) ?></div>
            <?php if (isset($escMap[(int)$t['id']])): ?>
              <span class="tag small" style="background:#ff2bd6; color:#fff; font-size:9px; padding:2px 5px; border-radius:4px; margin-top:3px; display:inline-block; font-weight:bold;">
                ⚡ <?= esc($escMap[(int)$t['id']]['status'] === 'Pending Supervisor Approval' ? 'Pending Esc.' : 'Escalated') ?>
              </span>
            <?php endif; ?>
          </div>
          <span class="badge"><?= $t['messages'] ?></span>
        </a>
      <?php endforeach; ?>
    </div></aside>
    <section class="acc-main"><div class="panel">
      <?php if (!$uid): ?><p class="lead">Select a conversation to reply.</p>
      <?php elseif ($tab === 'requirements'): ?>
  <?php if (!empty($_GET['msg'])): ?><div class="msg ok"><?= esc($_GET['msg']) ?></div><?php endif; ?>
  <h3 class="sec-h" style="margin-top:0">📋 Custom Build Requirements</h3>
  <p class="lead" style="margin-top:0">Review custom packages built and requested by users. Approve or decline them below.</p>
  
  <div class="table-scroll"><table style="width:100%; border-collapse:collapse;">
    <thead>
      <tr>
        <th style="padding:10px; width:6%;">ID</th>
        <th style="padding:10px; width:15%;">Customer</th>
        <th style="padding:10px; width:20%;">Email</th>
        <th style="padding:10px; width:30%;">Details</th>
        <th style="padding:10px; width:10%;">Price</th>
        <th style="padding:10px; width:10%;">Status</th>
        <th style="padding:10px; width:9%;">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$requirements): ?><tr><td colspan="7" style="text-align:center;color:var(--muted); padding:20px;">No custom requirements submitted yet.</td></tr><?php endif; ?>
    <?php foreach ($requirements as $r): ?>
      <tr style="border-bottom: 1px solid var(--line);">
        <td style="padding:10px;">#<?= $r['id'] ?></td>
        <td style="padding:10px; font-weight:600;"><?= esc($r['user_name']) ?></td>
        <td style="padding:10px;"><?= esc($r['user_email']) ?></td>
        <td style="padding:10px;">
          <div style="font-size:12px; text-align:left; line-height:1.4; background:rgba(255,255,255,0.02); padding:8px; border-radius:6px; border:1px solid var(--line);">
            <strong>Device:</strong> <?= esc($r['device']) ?><br>
            <strong>Minutes:</strong> <?= esc($r['minutes']) ?><br>
            <strong>Data:</strong> <?= esc($r['data']) ?><br>
            <strong>SMS:</strong> <?= esc($r['sms']) ?><br>
            <strong>Add-ons:</strong> <?= esc($r['addons'] ?: 'None') ?>
          </div>
        </td>
        <td style="padding:10px; font-weight:700; color:var(--heading);"><?= gbp($r['price']) ?></td>
        <td style="padding:10px;">
          <span class="tag" style="background: <?= $r['status'] === 'Confirmed' ? 'var(--brand)' : 'var(--muted)' ?>; color:#fff; padding: 3px 8px; border-radius: 4px; font-size:11px; font-weight:bold;">
            <?= esc($r['status']) ?>
          </span>
        </td>
        <td style="padding:10px;">
          <div style="display:flex; flex-direction:column; gap:6px; align-items:stretch;">
            <?php if ($r['status'] === 'Pending'): ?>
              <form method="post" style="margin:0">
                <input type="hidden" name="act" value="confirmRequirement">
                <input type="hidden" name="req_id" value="<?= $r['id'] ?>">
                <button class="btn small block" type="submit" style="padding:6px 10px;">Confirm</button>
              </form>
            <?php endif; ?>
            <form method="post" onsubmit="return confirm('Delete this requirement?')" style="margin:0">
              <input type="hidden" name="act" value="deleteRequirement">
              <input type="hidden" name="req_id" value="<?= $r['id'] ?>">
              <button class="btn small danger block" type="submit" style="padding:6px 10px;">Delete</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>

<?php else: ?>
        <!-- BỔ SUNG CHO FR28: SLA Technical Escalation Dashboard Component (BUG-021) -->
        <div style="padding:12px; background:rgba(255, 193, 7, 0.1); border:1px solid #ffc107; border-radius:8px; margin-bottom:15px;">
            <form method="post" style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin:0;">
                <input type="hidden" name="act" value="escalateTicket">
                <input type="hidden" name="user_id" value="<?= $uid ?>">
                <span style="font-weight:bold; font-size:13px; color:#b58100;">⚡ SLA Action Panel:</span>
                <select name="escalate_tier" style="padding:4px; border-radius:6px; background:var(--card); color:var(--ink); border:1px solid var(--line);">
                    <option value="Tier-2 Technical Support Desk">Escalate to Tier-2 Technical</option>
                    <option value="Tier-3 Network Engineering">Escalate to Tier-3 Infrastructure</option>
                    <option value="Billing Ops Senior Manager">Escalate to High Priority Billing</option>
                </select>
                <div style="display:flex; align-items:center; gap:5px;">
                    <label style="font-size:12px; color: var(--ink);">Credit Note (£):</label>
                    <input type="number" name="compensation" value="0.00" step="0.01" style="width:70px; padding:4px; border-radius:6px; border:1px solid var(--line); background:var(--card); color:var(--ink);" required>
                </div>
                <button class="btn small danger" type="submit">Confirm Escalation</button>
            </form>
            
            <?php if (isset($escMap[(int)$uid])): ?>
              <div style="margin-top:10px; padding:8px; border-radius:6px; background:rgba(255, 43, 214, 0.1); border:1px solid var(--neon2); font-size:12px; color:var(--ink);">
                <strong>Current Escalation:</strong> <?= esc($escMap[(int)$uid]['tier']) ?> &middot; 
                <strong>Credit Note:</strong> <?= gbp($escMap[(int)$uid]['compensation']) ?> &middot; 
                <strong>Status:</strong> <span style="font-weight:bold; color:#ff2e63;"><?= esc($escMap[(int)$uid]['status']) ?></span>
              </div>
            <?php endif; ?>
        </div>
        <div class="chat">
          <?php if (!$chat_msgs): ?><p class="lead" style="margin:10px">No messages.</p><?php endif; ?>
          <?php foreach ($chat_msgs as $m): ?>
            <div class="bubble <?= $m['sender'] ?>"><div class="who"><?= $m['sender']==='user'?'Customer':'You (staff)' ?></div><?= esc($m['message']) ?></div>
          <?php endforeach; ?>
        </div>
        <form method="post" class="chat-input">
          <input type="hidden" name="act" value="reply"><input type="hidden" name="user_id" value="<?= $uid ?>">
          <input name="message" placeholder="Type a reply..." required><button class="btn" type="submit">Reply</button>
        </form>
      <?php endif; ?>
    </div></section>
  </div>

<?php elseif ($tab === 'vouchers'): ?>
  <div class="panel" style="max-width:560px">
    <h3 class="sec-h" style="margin-top:0">Generate a voucher</h3>
    <p class="lead" style="margin-top:0">The unique code is saved to the database; a customer can enter it at checkout (FR30).</p>
    <form method="post" style="display:flex;gap:8px;align-items:flex-end">
      <input type="hidden" name="act" value="issueVoucher"><input type="hidden" name="back" value="vouchers">
      <div class="fg" style="margin:0"><label>Discount %</label><input name="discount" type="number" min="1" max="50" value="10" style="width:120px"></div>
      <button class="btn" type="submit">Generate</button>
    </form>
  </div>

  <!-- BỔ SUNG CHO FR30: Cải tiến giao diện chọn tệp phân khúc khách hàng mục tiêu -->
  <div class="panel" style="max-width:560px; margin-top: 15px;">
    <h3 class="sec-h" style="margin-top:0">🎟️ Demographic Cohort Campaign Panel (FR30)</h3>
    <p class="lead" style="margin-top:0">Filter users by consumption logs and dispatch a targeted voucher campaign code instantly.</p>
    <form method="post" style="display:flex; flex-direction:column; gap:12px;">
      <input type="hidden" name="act" value="issueCohortVoucher">
      <input type="hidden" name="back" value="vouchers">
      <div class="two-col">
        <div class="fg" style="margin:0;"><label>Target Cohort Rule</label>
        <select name="cohort_rule" style="padding:8px; border:1px solid var(--line); border-radius:7px; background:var(--card); color:var(--ink);">
          <option value="data_usage_gt_20gb">High Data Users (>20GB / mo)</option>
          <option value="loyal_customers_gt_1year">Loyal Account Holders (>1 Year)</option>
          <option value="multi_device_purchasers">Multi-Device Purchasers</option>
          <option value="inactive_users_gt_30days">Inactive Customers (>30 Days)</option>
        </select>
        </div>
        <div class="fg" style="margin:0;"><label>Discount %</label>
        <input name="discount" type="number" min="1" max="50" value="15" style="padding:8px; border:1px solid var(--line); border-radius:7px; background:var(--card); color:var(--ink);">
        </div>
      </div>
      <button class="btn" type="submit">Launch Cohort Campaign</button>
    </form>
  </div>

  <div class="table-scroll" style="margin-top:24px"><table>
    <thead><tr><th>Code</th><th>Discount</th><th>Created by</th><th>When</th></tr></thead>
    <tbody>
    <?php if (!$vouchers): ?><tr><td colspan="4" style="text-align:center;color:var(--muted)">No vouchers yet.</td></tr><?php endif; ?>
    <?php foreach ($vouchers as $v): ?>
      <tr><td><strong style="letter-spacing:1px"><?= esc($v['code']) ?></strong></td><td class="discount"><?= (int)$v['discount'] ?>% off</td><td><?= esc($v['created_by']) ?></td><td><?= esc(substr($v['created_at'],0,19)) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>

<?php elseif ($tab === 'requirements'): ?>
  <?php if (!empty($_GET['msg'])): ?><div class="msg ok"><?= esc($_GET['msg']) ?></div><?php endif; ?>
  <h3 class="sec-h" style="margin-top:0">📋 Custom Build Requirements</h3>
  <p class="lead" style="margin-top:0">Review custom packages built and requested by users. Approve or decline them below.</p>
  
  <div class="table-scroll"><table style="width:100%; border-collapse:collapse;">
    <thead>
      <tr>
        <th style="padding:10px; width:6%;">ID</th>
        <th style="padding:10px; width:15%;">Customer</th>
        <th style="padding:10px; width:20%;">Email</th>
        <th style="padding:10px; width:30%;">Details</th>
        <th style="padding:10px; width:10%;">Price</th>
        <th style="padding:10px; width:10%;">Status</th>
        <th style="padding:10px; width:9%;">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$requirements): ?><tr><td colspan="7" style="text-align:center;color:var(--muted); padding:20px;">No custom requirements submitted yet.</td></tr><?php endif; ?>
    <?php foreach ($requirements as $r): ?>
      <tr style="border-bottom: 1px solid var(--line);">
        <td style="padding:10px;">#<?= $r['id'] ?></td>
        <td style="padding:10px; font-weight:600;"><?= esc($r['user_name']) ?></td>
        <td style="padding:10px;"><?= esc($r['user_email']) ?></td>
        <td style="padding:10px;">
          <div style="font-size:12px; text-align:left; line-height:1.4; background:rgba(255,255,255,0.02); padding:8px; border-radius:6px; border:1px solid var(--line);">
            <strong>Device:</strong> <?= esc($r['device']) ?><br>
            <strong>Minutes:</strong> <?= esc($r['minutes']) ?><br>
            <strong>Data:</strong> <?= esc($r['data']) ?><br>
            <strong>SMS:</strong> <?= esc($r['sms']) ?><br>
            <strong>Add-ons:</strong> <?= esc($r['addons'] ?: 'None') ?>
          </div>
        </td>
        <td style="padding:10px; font-weight:700; color:var(--heading);"><?= gbp($r['price']) ?></td>
        <td style="padding:10px;">
          <span class="tag" style="background: <?= $r['status'] === 'Confirmed' ? 'var(--brand)' : 'var(--muted)' ?>; color:#fff; padding: 3px 8px; border-radius: 4px; font-size:11px; font-weight:bold;">
            <?= esc($r['status']) ?>
          </span>
        </td>
        <td style="padding:10px;">
          <div style="display:flex; flex-direction:column; gap:6px; align-items:stretch;">
            <?php if ($r['status'] === 'Pending'): ?>
              <form method="post" style="margin:0">
                <input type="hidden" name="act" value="confirmRequirement">
                <input type="hidden" name="req_id" value="<?= $r['id'] ?>">
                <button class="btn small block" type="submit" style="padding:6px 10px;">Confirm</button>
              </form>
            <?php endif; ?>
            <form method="post" onsubmit="return confirm('Delete this requirement?')" style="margin:0">
              <input type="hidden" name="act" value="deleteRequirement">
              <input type="hidden" name="req_id" value="<?= $r['id'] ?>">
              <button class="btn small danger block" type="submit" style="padding:6px 10px;">Delete</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>

<?php else: ?>
  <?php if (!$orders): ?><p class="lead">No orders yet.</p>
  <?php elseif ($tab === 'requirements'): ?>
  <?php if (!empty($_GET['msg'])): ?><div class="msg ok"><?= esc($_GET['msg']) ?></div><?php endif; ?>
  <h3 class="sec-h" style="margin-top:0">📋 Custom Build Requirements</h3>
  <p class="lead" style="margin-top:0">Review custom packages built and requested by users. Approve or decline them below.</p>
  
  <div class="table-scroll"><table style="width:100%; border-collapse:collapse;">
    <thead>
      <tr>
        <th style="padding:10px; width:6%;">ID</th>
        <th style="padding:10px; width:15%;">Customer</th>
        <th style="padding:10px; width:20%;">Email</th>
        <th style="padding:10px; width:30%;">Details</th>
        <th style="padding:10px; width:10%;">Price</th>
        <th style="padding:10px; width:10%;">Status</th>
        <th style="padding:10px; width:9%;">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$requirements): ?><tr><td colspan="7" style="text-align:center;color:var(--muted); padding:20px;">No custom requirements submitted yet.</td></tr><?php endif; ?>
    <?php foreach ($requirements as $r): ?>
      <tr style="border-bottom: 1px solid var(--line);">
        <td style="padding:10px;">#<?= $r['id'] ?></td>
        <td style="padding:10px; font-weight:600;"><?= esc($r['user_name']) ?></td>
        <td style="padding:10px;"><?= esc($r['user_email']) ?></td>
        <td style="padding:10px;">
          <div style="font-size:12px; text-align:left; line-height:1.4; background:rgba(255,255,255,0.02); padding:8px; border-radius:6px; border:1px solid var(--line);">
            <strong>Device:</strong> <?= esc($r['device']) ?><br>
            <strong>Minutes:</strong> <?= esc($r['minutes']) ?><br>
            <strong>Data:</strong> <?= esc($r['data']) ?><br>
            <strong>SMS:</strong> <?= esc($r['sms']) ?><br>
            <strong>Add-ons:</strong> <?= esc($r['addons'] ?: 'None') ?>
          </div>
        </td>
        <td style="padding:10px; font-weight:700; color:var(--heading);"><?= gbp($r['price']) ?></td>
        <td style="padding:10px;">
          <span class="tag" style="background: <?= $r['status'] === 'Confirmed' ? 'var(--brand)' : 'var(--muted)' ?>; color:#fff; padding: 3px 8px; border-radius: 4px; font-size:11px; font-weight:bold;">
            <?= esc($r['status']) ?>
          </span>
        </td>
        <td style="padding:10px;">
          <div style="display:flex; flex-direction:column; gap:6px; align-items:stretch;">
            <?php if ($r['status'] === 'Pending'): ?>
              <form method="post" style="margin:0">
                <input type="hidden" name="act" value="confirmRequirement">
                <input type="hidden" name="req_id" value="<?= $r['id'] ?>">
                <button class="btn small block" type="submit" style="padding:6px 10px;">Confirm</button>
              </form>
            <?php endif; ?>
            <form method="post" onsubmit="return confirm('Delete this requirement?')" style="margin:0">
              <input type="hidden" name="act" value="deleteRequirement">
              <input type="hidden" name="req_id" value="<?= $r['id'] ?>">
              <button class="btn small danger block" type="submit" style="padding:6px 10px;">Delete</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>

<?php else: ?>
    <div class="table-scroll"><table>
      <thead><tr><th>Date</th><th>Customer</th><th>Email</th><th>Package / Product</th><th>Total</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
        <tr><td><?= esc(substr($o['created_at'],0,10)) ?></td><td><?= esc($o['customer']) ?></td><td><?= esc($o['email']) ?></td><td><?= esc($o['package_name']) ?></td><td><?= gbp($o['total']) ?></td><td><?= esc($o['status']) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/../layout/footer.php'; ?>
