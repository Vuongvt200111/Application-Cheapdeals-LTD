<?php
$cur = 'admin.php'; $pageTitle = 'Admin Panel';
require __DIR__ . '/../layout/header.php';
?>
<h2 class="title">Admin Control Panel</h2>
<p class="lead">Audit account metrics, manage security roles, compose dynamic marketing promo codes, and inspect system logs.</p>

<?php if ($msg): ?>
  <div class="msg info" style="background:var(--card); border:1px solid var(--neon); color:var(--neon); padding:10px; border-radius:8px; margin-bottom:15px; text-align:center;"><?= esc($msg) ?></div>
<?php endif; ?>

<div class="tabs">
  <a class="<?= $tab==='overview'?'active':'' ?>" href="admin.php?tab=overview">📊 System Metrics</a>
  <a class="<?= $tab==='users'?'active':'' ?>" href="admin.php?tab=users">👥 Users &amp; Roles</a>
  <a class="<?= $tab==='campaigns'?'active':'' ?>" href="admin.php?tab=campaigns">🎁 Campaign Composer</a>
  <a class="<?= $tab==='audit'?'active':'' ?>" href="admin.php?tab=audit">📜 Audit Trails Logs</a>
</div>

<!-- TAB SECTION 1: SYSTEM OVERVIEW METRICS -->
<?php if ($tab === 'overview'): ?>
  <div class="stats" style="margin-bottom:15px;">
    <div class="stat"><div class="num"><?= $s['users'] ?></div><div class="lab">Total accounts</div></div>
    <div class="stat"><div class="num"><?= $s['customers'] ?></div><div class="lab">Customers</div></div>
    <div class="stat"><div class="num"><?= $s['orders'] ?></div><div class="lab">Total orders</div></div>
  </div>
  <div class="stats">
    <div class="stat"><div class="num green"><?= gbp($s['revenue']) ?></div><div class="lab">Total revenue</div></div>
    <div class="stat"><div class="num"><?= $s['packages'] ?></div><div class="lab">Active packages</div></div>
    <div class="stat"><div class="num"><?= $s['campaigns'] ?></div><div class="lab">Promo campaigns</div></div>
  </div>
  <div class="hint">The platform database contains <?= $s['staff'] ?> staff members and <?= $s['feedback'] ?> total ticket log threads.</div>

<!-- TAB SECTION 2: USERS & ROLES -->
<?php elseif ($tab === 'users'): ?>
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Email Address</th>
          <th>Phone</th>
          <th>Role Badge</th>
          <th>Modify Role</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= $u['id'] ?></td>
            <td><strong><?= esc($u['name']) ?></strong></td>
            <td><?= esc($u['email']) ?></td>
            <td><?= esc($u['phone']) ?></td>
            <td><span class="role-badge role-<?= esc($u['role']) ?>"><?= esc($u['role']) ?></span></td>
            <td>
              <?php if (isPrimaryAdmin($u['email'])): ?>
                <span style="font-size:12px; color:var(--muted)">🔒 Fixed Primary Admin</span>
              <?php else: ?>
                <form method="post" style="margin:0">
                  <input type="hidden" name="act" value="setRole">
                  <input type="hidden" name="back" value="users">
                  <input type="hidden" name="id" value="<?= $u['id'] ?>">
                  <select name="role" onchange="this.form.submit()" style="padding:4px; border-radius:6px; background:var(--card); color:var(--ink); border:1px solid var(--line);" <?= $u['id']===(int)$ME['id']?'disabled':'' ?>>
                    <?php foreach (['user','staff','admin'] as $r): ?>
                      <option value="<?= $r ?>" <?= $r===$u['role']?'selected':'' ?>><?= $r ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="hint">Only the primary admin account (<?= esc($CFG['primary_admin']) ?>) is fixed. Other accounts can be toggled.</div>

<!-- TAB SECTION 3: CAMPAIGN COMPOSER -->
<?php elseif ($tab === 'campaigns'): ?>
  <div class="acc-layout">
    <section class="acc-main" style="flex:2.2;">
      <div class="panel">
        <h3 class="sec-h" style="margin-top:0;">Active Campaigns</h3>
        <div class="table-scroll">
          <table>
            <thead>
              <tr>
                <th>Code</th>
                <th>Campaign Name</th>
                <th>Discount</th>
                <th>Target Segment</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($campaigns)): ?>
                <tr><td colspan="5" style="text-align:center; color:var(--muted)">No campaigns composed yet.</td></tr>
              <?php endif; ?>
              <?php foreach ($campaigns as $c): 
                $status_color = $c['active'] ? 'var(--green)' : 'var(--muted)';
              ?>
                <tr>
                  <td><code><?= esc($c['code']) ?></code></td>
                  <td><strong><?= esc($c['name']) ?></strong></td>
                  <td>
                    <?= $c['discount_pct'] > 0 ? (float)$c['discount_pct'] . '%' : gbp($c['discount_abs']) ?>
                  </td>
                  <td><span class="badge" style="font-size:9px;"><?= esc($c['target_segment']) ?></span></td>
                  <td>
                    <form method="post" style="margin:0;">
                      <input type="hidden" name="act" value="toggleCampaign">
                      <input type="hidden" name="back" value="campaigns">
                      <input type="hidden" name="id" value="<?= $c['id'] ?>">
                      <input type="hidden" name="active" value="<?= $c['active'] ? 0 : 1 ?>">
                      <button type="submit" class="btn small" style="background:none; border:1px solid <?= $status_color ?>; color:<?= $status_color ?>; padding:2px 6px;">
                        <?= $c['active'] ? 'Active' : 'Disabled' ?>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <aside class="acc-side" style="flex:1; min-width:240px;">
      <div class="panel" style="padding:15px;">
        <h3 class="sec-h" style="margin-top:0;">Compose Campaign</h3>
        <form method="post">
          <input type="hidden" name="act" value="addCampaign">
          <input type="hidden" name="back" value="campaigns">
          
          <div class="fg">
            <label>Campaign Name</label>
            <input name="name" required placeholder="e.g. Autumn Offer">
          </div>
          
          <div class="fg" style="margin-top:10px;">
            <label>Promo Code (Unique)</label>
            <input name="code" required placeholder="e.g. AUTUMN10">
          </div>

          <div class="fg" style="margin-top:10px;">
            <label>Discount Percentage (%)</label>
            <input name="discount_pct" type="number" step="0.01" value="0.00">
          </div>

          <div class="fg" style="margin-top:10px;">
            <label>Discount Value (£ Absolute)</label>
            <input name="discount_abs" type="number" step="0.01" value="0.00">
          </div>

          <div class="fg" style="margin-top:10px;">
            <label>Target Segment</label>
            <select name="target_segment" style="width:100%; padding:10px; border-radius:8px; background:var(--card); color:var(--ink); border:1px solid var(--line);">
              <option value="All">All Registered Customers</option>
              <option value="HeavyData">Heavy Data Users (Data > 20GB)</option>
              <option value="YoungUsers">Young Audience (Age 16-25)</option>
            </select>
          </div>

          <button class="btn block" type="submit" style="margin-top:15px;">Launch Campaign</button>
        </form>
      </div>
    </aside>
  </div>

<!-- TAB SECTION 4: AUDIT TRAILS LOGS -->
<?php else: ?>
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Timestamp</th>
          <th>Executor ID</th>
          <th>Executor Email</th>
          <th>Action Type</th>
          <th>Detail Audit Parameters</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
          <tr><td colspan="5" style="text-align:center; color:var(--muted)">No audit logs found.</td></tr>
        <?php endif; ?>
        <?php foreach ($logs as $l): ?>
          <tr>
            <td style="font-size:11px; color:var(--muted);"><?= esc($l['created_at']) ?></td>
            <td><?= $l['user_id'] ?></td>
            <td><code><?= esc($l['user_email']) ?></code></td>
            <td><strong style="color:var(--neon2);"><?= esc($l['action']) ?></strong></td>
            <td style="font-size:12px; font-family:monospace;"><?= esc($l['detail']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="lead" style="font-size:11px; text-align:right; color:var(--muted);">Logs display is limited to the last 100 entries. Logs are immutable and cannot be deleted.</p>
<?php endif; ?>

<?php require __DIR__ . '/../layout/footer.php'; ?>
