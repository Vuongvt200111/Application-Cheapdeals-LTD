<?php
if (!function_exists('cd_image_cell')) {
    function cd_image_cell($code) {
        $img = function_exists('cd_product_card_image') ? cd_product_card_image($code) : '';
        ob_start();
        ?>
        <div style="display:flex; align-items:center; gap:8px;">
            <?php if ($img !== ''): ?>
                <img src="<?= htmlspecialchars($img, ENT_QUOTES) ?>" alt="" style="width:64px; height:40px; object-fit:contain; border-radius:4px; background:rgba(0,0,0,0.2);">
            <?php else: ?>
                <div style="width:64px; height:40px; border-radius:4px; border:1px dashed var(--line); display:flex; align-items:center; justify-content:center; font-size:11px; color:var(--muted);">No img</div>
            <?php endif; ?>
            <label style="cursor:pointer; margin:0; font-size:11px; padding:4px 8px; background:var(--card); border:1px solid var(--line); border-radius:4px; color:var(--ink); font-weight:600; display:inline-block;" title="Upload new picture">
                Choose File
                <input type="file" name="image" accept=".svg,.png,.jpg,.jpeg,.webp,.gif" style="display:none;" onchange="var fn=this.parentNode.nextElementSibling; if(fn){ fn.textContent = this.files[0] ? this.files[0].name : 'No file chosen'; }">
            </label>
            <span style="font-size:10px; color:var(--muted); max-width:90px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">No file chosen</span>
        </div>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('cd_image_picker')) {
    function cd_image_picker($library = [], $current = '') {
        ob_start();
        ?>
        <div style="margin-top:10px; margin-bottom:10px;">
            <label style="display:block; margin-bottom:4px; font-size:12px; font-weight:600;">Product Picture (Optional)</label>
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
              <label style="cursor:pointer; margin:0; font-size:12px; padding:6px 12px; background:var(--card); border:1px solid var(--line); border-radius:6px; color:var(--ink); font-weight:600; display:inline-block;">
                Choose File
                <input type="file" name="image" accept=".svg,.png,.jpg,.jpeg,.webp,.gif" style="display:none;" onchange="var fn=this.parentNode.nextElementSibling; if(fn){ fn.textContent = this.files[0] ? this.files[0].name : 'No file chosen'; }">
              </label>
              <span style="font-size:12px; color:var(--muted);">No file chosen</span>
            </div>
            <label style="display:block; margin-bottom:4px; font-size:11px; color:var(--muted);">Or reuse an existing artwork:</label>
            <select name="image_from" style="width:100%; max-width:260px; font-size:12px; padding:4px;">
                <option value="">-- None --</option>
                <?php if (!empty($library) && is_array($library)): ?>
                    <?php foreach ($library as $k => $item): ?>
                        <?php 
                            $cCode = is_array($item) ? ($item['code'] ?? '') : (is_string($k) ? $k : $item);
                            $cLabel = is_array($item) ? ($item['label'] ?? $cCode) : (is_string($item) ? $item : $cCode);
                        ?>
                        <option value="<?= htmlspecialchars($cCode, ENT_QUOTES) ?>"><?= htmlspecialchars($cLabel, ENT_QUOTES) ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <?php
        return ob_get_clean();
    }
}

if (!isset($imageLibrary) || !is_array($imageLibrary)) {
    global $pdo;
    $imageLibrary = function_exists('cd_image_library') ? cd_image_library($pdo ?? null) : [];
}
?>
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
    <thead><tr><th>Name</th><th>Category</th><th>Tier</th><th>Price (£)</th><th>Inventory</th><th>Picture</th>
                        <th>Picture</th>
                        <th>Actions</th></tr></thead>
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
            <input name="inventory" type="number" min="0" oninput="if(this.value<0)this.value=0;" min="0" oninput="if(this.value<0)this.value=0;" value="<?= $p['inventory'] ?>" style="width:70px;padding:6px;border:1px solid var(--line);border-radius:7px;background:var(--card);color:var(--ink)">
          </td>
          <td>
            <?= cd_image_cell($p["code"] ?? "") ?></td><td><button class="btn small" type="submit">Save</button>
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
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="act" value="addPkg"><input type="hidden" name="back" value="packages">
      <div class="fg"><label>Name</label><input name="name" required></div>
      <div class="two-col">
        <div class="fg"><label>Category</label><select name="category"><?php foreach (['Data','Mobile','Broadband','Tablet','Bundles'] as $c): ?><option><?= $c ?></option><?php endforeach; ?></select></div>
        <div class="fg"><label>Tier</label><select name="tier"><?php foreach (['Lite','Standard','Premium','Deal'] as $t): ?><option><?= $t ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="two-col">
        <div class="fg"><label>Price (£)</label><input name="price" type="number" step="0.01" value="20" required></div>
        <div class="fg"><label>Cycle Times</label>
          <select name="unit">
            <option value="month">Monthly (£/month)</option>
            <option value="Hourly">Hourly (£/Hourly)</option>
            <option value="1-Day">1-Day (£/1-Day)</option>
            <option value="3-Day">3-Day (£/3-Day)</option>
            <option value="7-Day">7-Day (£/7-Day)</option>
            <option value="30-Day">30-Day (£/30-Day)</option>
          </select>
        </div>
      </div>
      <div class="fg"><label>Inventory Stock</label><input name="inventory" type="number" min="0" oninput="if(this.value<0)this.value=0;" value="10" required></div>
      <div class="fg"><label>Features (one per line)</label><textarea name="features" rows="3" placeholder="1 mobile phone&#10;500 free minutes" required></textarea></div>
                <?= cd_image_picker($imageLibrary) ?>
      <button class="btn" type="submit">Create option</button>
    </form>
  </div>

  <hr style="border:0; border-top: 1px solid var(--line); margin: 30px 0;">

  <h3 class="sec-h">2. Hardware Devices</h3>
  <div class="table-scroll"><table>
    <thead><tr><th>Name</th><th>Price (£)</th><th>Inventory</th><th>Description</th><th>Picture</th>
                        <th>Picture</th>
                        <th>Actions</th></tr></thead>
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
            <input name="inventory" type="number" min="0" oninput="if(this.value<0)this.value=0;" min="0" oninput="if(this.value<0)this.value=0;" value="<?= $p['inventory'] ?>" style="width:70px;padding:6px;border:1px solid var(--line);border-radius:7px;background:var(--card);color:var(--ink)">
          </td>
          <td>
            <textarea name="description" style="width:200px;height:45px;padding:6px;border:1px solid var(--line);border-radius:7px;background:var(--card);color:var(--ink);font-size:11px;"><?= esc($p['description']) ?></textarea>
          </td>
          <td>
            <?= cd_image_cell($p["code"] ?? "") ?></td><td><button class="btn small" type="submit">Save</button>
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
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="act" value="addProd"><input type="hidden" name="back" value="packages">
      <div class="fg"><label>Product Name</label><input name="name" placeholder="e.g. CheapDeals Gaming Laptop" required></div>
      <div class="two-col">
        <div class="fg"><label>Price (£)</label><input name="price" type="number" step="0.01" value="29.99" required></div>
        <div class="fg"><label>Inventory Stock</label><input name="inventory" type="number" min="0" oninput="if(this.value<0)this.value=0;" min="0" oninput="if(this.value<0)this.value=0;" value="10" min="0" oninput="if(this.value<0)this.value=0;" required></div>
      </div>
      <div class="fg"><label>Product Description</label><textarea name="description" rows="3" placeholder="Enter short product specifications..." required></textarea></div>
      <?= cd_image_picker($imageLibrary) ?>
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
              <form enctype="multipart/form-data" method="post" style="margin:0">
                <input type="hidden" name="act" value="confirmRequirement">
                <input type="hidden" name="req_id" value="<?= $r['id'] ?>">
                <button class="btn small block" type="submit" style="padding:6px 10px;">Confirm</button>
              </form>
            <?php endif; ?>
            <form enctype="multipart/form-data" method="post" onsubmit="return confirm('Delete this requirement?')" style="margin:0">
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
        <!-- Supplement to FR28: SLA Technical Escalation Dashboard Component (BUG-021) -->
        <div style="padding:12px; background:rgba(255, 193, 7, 0.1); border:1px solid #ffc107; border-radius:8px; margin-bottom:15px;">
            <form enctype="multipart/form-data" method="post" style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin:0;">
                <input type="hidden" name="act" value="escalateTicket">
                <input type="hidden" name="user_id" value="<?= $uid ?>">
                <span style="font-weight:bold; font-size:13px; color:#b58100;">⚡ SLA Action Panel:</span>
                <select name="escalate_tier" style="padding:4px; border-radius:6px; background:var(--card); color:var(--ink); border:1px solid var(--line);">
                    <option value="Technical Support Desk">Escalate to Technical</option>
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
        <div class="chat" style="height:350px; overflow-y:auto; padding:10px; border:1px solid var(--line); border-radius:8px; background:transparent; display:flex; flex-direction:column;">
          <?php if (!$chat_msgs): ?><p class="lead" style="margin:10px">No messages.</p><?php endif; ?>
          <?php foreach ($chat_msgs as $m): ?>
            <?php
            $isUser = ($m['sender'] === 'user');
            $isProductLink = (strpos($m['message'], '[Liên kết sản phẩm]:') !== false);
            $displayMsg = $m['message'];
            if ($isProductLink) {
                $displayMsg = str_replace('[Liên kết sản phẩm]:', '📦 *Product Inquiry:* ', $m['message']);
            }
            ?>
            <div class="bubble <?= $m['sender'] ?>" 
                 style="margin-bottom:12px; padding:10px; border-radius:12px; max-width:75%; <?= $isUser ? 'align-self:flex-start; background:#e9ecef; color:#000;' : 'align-self:flex-end; margin-left:auto; background:#007bff; color:#fff;' ?>; <?= $isProductLink ? 'border: 1px dashed rgba(0,0,0,0.2); background:#d4edda; color:#155724;' : '' ?>">
              
              <div class="who" style="font-size:11px; font-weight:bold; opacity:0.8;">
                <?= $isUser ? 'Customer' : 'You (staff)' ?>
              </div>
              <div class="msg-text" style="margin-top:4px; white-space: pre-wrap;"><?= esc($displayMsg) ?></div>
              <div class="time" style="font-size:9px; text-align:right; opacity:0.6; margin-top:4px;">
                <?= esc(substr($m['created_at'], 11, 5)) ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <form enctype="multipart/form-data" method="post" class="chat-input">
          <input type="hidden" name="act" value="reply"><input type="hidden" name="user_id" value="<?= $uid ?>">
          <input name="message" placeholder="Type a reply..." required><button class="btn" type="submit">Reply</button>
        </form>
      <?php endif; ?>
    </div></section>
  </div>

<?php elseif ($tab === 'vouchers'): ?>
  <!-- Supplement to FR30: Improve target audience segmentation interface (Generate a voucher removed per user request) -->
  <div class="panel" style="max-width:560px; margin-top: 0;">
    <h3 class="sec-h" style="margin-top:0">🎟️ Potential member benefits</h3>
    <p class="lead" style="margin-top:0">Filter users by consumption logs and dispatch a targeted voucher campaign code instantly.</p>
    <form enctype="multipart/form-data" method="post" style="display:flex; flex-direction:column; gap:12px;">
      <input type="hidden" name="act" value="issueCohortVoucher">
      <input type="hidden" name="back" value="vouchers">
      <div class="two-col">
        <div class="fg" style="margin:0;"><label>Target Rule</label>
        <select name="rule" style="padding:8px; border:1px solid var(--line); border-radius:7px; background:var(--card); color:var(--ink);">
          <option value="data_usage_gt_20gb">High Data Users (>20GB / month)</option>
          <option value="loyal_customers_gt_1year">Loyal Account Holders (>1 Year)</option>
          <option value="multi_device_purchasers">Multi-Device Purchasers</option>
          <option value="inactive_users_gt_30days">Inactive Customers (>30 Days)</option>
        </select>
        </div>
        <div class="fg" style="margin:0;"><label>Discount %</label>
        <input name="discount" type="number" min="1" max="50" value="15" style="padding:8px; border:1px solid var(--line); border-radius:7px; background:var(--card); color:var(--ink);">
        </div>
      </div>
      <button class="btn" type="submit">Launch Campaign</button>
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
              <form enctype="multipart/form-data" method="post" style="margin:0">
                <input type="hidden" name="act" value="confirmRequirement">
                <input type="hidden" name="req_id" value="<?= $r['id'] ?>">
                <button class="btn small block" type="submit" style="padding:6px 10px;">Confirm</button>
              </form>
            <?php endif; ?>
            <form enctype="multipart/form-data" method="post" onsubmit="return confirm('Delete this requirement?')" style="margin:0">
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

<?php elseif ($tab === 'orders'): ?>
  <!-- SEARCH FILTER TOOLBAR INTERFACE FOR ORDERS (BUG-Nam/Orders) -->
  <div class="panel" style="margin-bottom: 18px; padding: 14px;">
    <form enctype="multipart/form-data" method="get" action="staff.php" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; margin: 0;">
      <input type="hidden" name="tab" value="orders">

      <!-- SEARCH FILTER FOR CUSTOMER NAME -->
      <div class="fg" style="margin: 0; flex: 1; min-width: 200px;">
        <label style="font-weight: bold; font-size: 13px; color: var(--ink);">🔍 Filter Customer Name:</label>
        <input name="f_customer" value="<?= esc($filterCustomer) ?>" placeholder="e.g. N,Nam..." style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 7px; background: var(--card); color: var(--ink);">
      </div>

      <!-- SEARCH FILTER FOR TOTAL PRICE -->
      <div class="fg" style="margin: 0; min-width: 200px;">
        <label style="font-weight: bold; font-size: 13px; color: var(--ink);">💰 Sort by Total Price:</label>
        <select name="s_total" style="width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 7px; background: var(--card); color: var(--ink);">
          <option value="">-- Default (Newest) --</option>
          <option value="desc" <?= $sortTotal === 'desc' ? 'selected' : '' ?>>Highest to Lowest (High → Low)</option>
          <option value="asc" <?= $sortTotal === 'asc' ? 'selected' : '' ?>>Lowest to Highest (Low → High)</option>
        </select>
      </div>

      <!-- Action Buttons for Form -->
      <div style="display: flex; gap: 8px;">
        <button class="btn" type="submit" style="padding: 8px 16px; height: 38px;">Apply Filters</button>
        <?php if ($filterCustomer !== '' || $sortTotal !== ''): ?>
          <a class="btn sec" href="staff.php?tab=orders" style="display: inline-flex; align-items: center; justify-content: center; padding: 8px 16px; height: 38px; text-decoration: none; font-size: 13px;">Clear</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <?php if (!$orders): ?>
    <p class="lead">No orders match your filters. <a href="staff.php?tab=orders">Reset layout</a>.</p>
  <?php else: ?>
    <div class="table-scroll"><table>
      <thead><tr><th>Date</th><th>Customer</th><th>Email</th><th>Package / Product</th><th>Total</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td><?= esc(substr($o['created_at'],0,10)) ?></td>
          <td style="font-weight: bold; color: #00f2fe;"><?= esc($o['customer']) ?></td>
          <td><?= esc($o['email']) ?></td>
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
          <td style="font-weight: bold;"><?= gbp($o['total']) ?></td>
          <td><span class="badge" style="background: #2b8a3e; color: #fff; border:none;"><?= esc($o['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/../layout/footer.php'; ?>