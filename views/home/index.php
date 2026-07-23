<?php
/* FR7 — Package Comparison Matrix helpers */
if (!function_exists('cd_render_illustration')) {
    function cd_render_illustration($category, $name = '') {
      $cat = strtolower($category);
      $iconColor = 'var(--brand)';
      if ($cat === 'mobile') {
        return '
        <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="'.$iconColor.'" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
          <line x1="12" y1="18" x2="12.01" y2="18"></line>
        </svg>';
      } elseif ($cat === 'broadband') {
        return '
        <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="'.$iconColor.'" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M5 12.55a11 11 0 0 1 14.08 0"></path>
          <path d="M1.42 9a16 16 0 0 1 21.16 0"></path>
          <path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path>
          <line x1="12" y1="20" x2="12.01" y2="20"></line>
        </svg>';
      } elseif ($cat === 'tablet') {
        return '
        <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="'.$iconColor.'" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <rect x="4" y="3" width="16" height="18" rx="2" ry="2"></rect>
          <line x1="12" y1="18" x2="12.01" y2="18"></line>
        </svg>';
      } elseif ($cat === 'bundles') {
        return '
        <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="'.$iconColor.'" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 12V8H4v4"></path>
          <path d="M2 4h20v4H2z"></path>
          <path d="M12 22V8"></path>
          <path d="M12 8H4v8a2 2 0 0 0 2 2h6"></path>
          <path d="M12 8h8v8a2 2 0 0 1-2 2h-6"></path>
          <path d="M12 2a4 4 0 0 1 4 4v2H8V6a4 4 0 0 1 4-4z"></path>
        </svg>';
      } else {
        $nm = strtolower($name);
        if (strpos($nm, 'keyboard') !== false) {
          return '
          <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="'.$iconColor.'" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="4" width="20" height="16" rx="2" ry="2"></rect>
            <line x1="6" y1="8" x2="6.01" y2="8"></line>
            <line x1="10" y1="8" x2="10.01" y2="8"></line>
            <line x1="14" y1="8" x2="14.01" y2="8"></line>
            <line x1="18" y1="8" x2="18.01" y2="8"></line>
            <line x1="7" y1="16" x2="17" y2="16"></line>
          </svg>';
        } elseif (strpos($nm, 'mouse') !== false) {
          return '
          <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="'.$iconColor.'" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="5" y="2" width="14" height="20" rx="7" ry="7"></rect>
            <line x1="12" y1="2" x2="12" y2="10"></line>
            <line x1="5" y1="10" x2="19" y2="10"></line>
          </svg>';
        } elseif (strpos($nm, 'laptop') !== false) {
          return '
          <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="'.$iconColor.'" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="4" y="4" width="16" height="12" rx="2" ry="2"></rect>
            <line x1="2" y1="20" x2="22" y2="20"></line>
            <line x1="5" y1="16" x2="19" y2="16"></line>
          </svg>';
        } elseif (strpos($nm, 'phone') !== false || strpos($nm, 'iphone') !== false || strpos($nm, 'samsung') !== false || strpos($nm, 'oppo') !== false) {
          return '
          <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="'.$iconColor.'" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
            <circle cx="12" cy="18" r="1"></circle>
            <circle cx="12" cy="5" r="0.5"></circle>
          </svg>';
        } else {
          return '
          <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="'.$iconColor.'" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
          </svg>';
        }
      }
    }
}

if (!function_exists('cd_spec_value')) {
    function cd_spec_value($text){
      if ($text === null) return null;
      if (stripos($text, 'unlimited') !== false) return INF;
      if (preg_match('/(\d+(?:\.\d+)?)/', $text, $m)) return (float)$m[1];
      return null;
    }
}
if (!function_exists('cd_pkg_specs')) {
    function cd_pkg_specs($feat){
      $out = ['device'=>null,'minutes'=>null,'sms'=>null,'data'=>null,'speed'=>null];
      foreach ($feat as $f) {
        if ($out['minutes'] === null && preg_match('/minute/i', $f)) { $out['minutes'] = $f; continue; }
        if ($out['sms']     === null && preg_match('/sms/i', $f))    { $out['sms']     = $f; continue; }
        if ($out['speed']   === null && preg_match('/mbps|speed/i', $f)) { $out['speed'] = $f; continue; }
        if ($out['data']    === null && preg_match('/\bdata\b/i', $f))   { $out['data']  = $f; continue; }
        if ($out['device']  === null) { $out['device'] = $f; }
      }
      return $out;
    }
}
if (!function_exists('cd_best_spec')) {
    function cd_best_spec($packages, $key){
      $bestText = null; $bestValue = null;
      foreach ($packages as $p) {
        $features_arr = [];
        if (isset($p['features'])) {
            $features_arr = json_decode($p['features'], true) ?: [];
        } else if (isset($p['description'])) {
            $features_arr = [$p['description']];
        }
        $specs = cd_pkg_specs($features_arr);
        $text = $specs[$key] ?? null;
        if ($text === null) continue;
        $value = cd_spec_value($text);
        if ($bestText === null || ($value !== null && ($bestValue === null || $value > $bestValue))) {
          $bestText = $text;
          $bestValue = $value;
        }
      }
      return ['text' => $bestText ?: 'Not listed', 'value' => $bestValue];
    }
}
if (!function_exists('cd_category_summary')) {
    function cd_category_summary($packages){
      $cheapest = null;
      foreach ($packages as $p) {
        if ($cheapest === null || (float)$p['price'] < (float)$cheapest['price']) $cheapest = $p;
      }
      return [
        'count' => ['text' => count($packages) . ' options', 'value' => count($packages)],
        'price' => ['text' => $cheapest ? gbp($cheapest['price']) : 'Not listed', 'value' => $cheapest ? (float)$cheapest['price'] : null],
        'data' => cd_best_spec($packages, 'data'),
        'minutes' => cd_best_spec($packages, 'minutes'),
        'sms' => cd_best_spec($packages, 'sms'),
        'speed' => cd_best_spec($packages, 'speed'),
        'recommended' => ['text' => $cheapest ? $cheapest['name'] : 'Not listed', 'value' => $cheapest ? (float)$cheapest['price'] : null],
      ];
    }
}
$primaryCats = ['Data', 'Mobile', 'Broadband', 'Tablet', 'Bundles', 'Hardware'];

// Seed Data Packages into $pkgs for Data Chip filtering
$hasDataPkg = false;
foreach ($pkgs as $p) { if ($p['category'] === 'Data') { $hasDataPkg = true; break; } }
if (!$hasDataPkg) {
    $dataPkgsSeed = [
        ['id'=>101, 'name'=>'Hourly Data Plan - 3GB', 'category'=>'Data', 'tier'=>'Lite', 'price'=>2.50, 'purchased_count'=>1420, 'stock'=>25, 'features'=>"3GB High-Speed Data
Valid for 6 hours
Instant Activation"],
        ['id'=>102, 'name'=>'1-Day Data Plan - 1.2GB', 'category'=>'Data', 'tier'=>'Lite', 'price'=>1.00, 'purchased_count'=>3850, 'stock'=>50, 'features'=>"1.2GB High-Speed Data
Valid for 24 hours
Auto-Renew Option"],
        ['id'=>103, 'name'=>'1-Day 5G Data - 7GB', 'category'=>'Data', 'tier'=>'Standard', 'price'=>2.00, 'purchased_count'=>2100, 'stock'=>30, 'features'=>"7GB Ultra 5G Data
Valid for 24 hours
Unrestricted Tethering"],
        ['id'=>104, 'name'=>'3-Day 5G Data - 25GB', 'category'=>'Data', 'tier'=>'Standard', 'price'=>4.00, 'purchased_count'=>1890, 'stock'=>40, 'features'=>"25GB Ultra 5G Data
Valid for 3 days
Priority Bandwidth"],
        ['id'=>105, 'name'=>'7-Day 5G Data - 65GB', 'category'=>'Data', 'tier'=>'Premium', 'price'=>9.00, 'purchased_count'=>4210, 'stock'=>18, 'features'=>"65GB 5G Super Data
Valid for 7 days
Free Wi-Fi Hotspots"],
        ['id'=>106, 'name'=>'30-Day Deal - 66GB', 'category'=>'Data', 'tier'=>'Premium', 'price'=>15.00, 'purchased_count'=>5600, 'stock'=>60, 'features'=>"66GB Data (2.2GB/day)
Valid for 30 days
Cashback reward: £1.50"]
    ];
    $pkgs = array_merge($pkgs, $dataPkgsSeed);
}

$byCat = [];
foreach ($pkgs as $p) { $byCat[$p['category']][] = $p; }
foreach ($prods as $p) { $byCat['Hardware'][] = $p; }
$cmpCats = array_values(array_filter($primaryCats, fn($c) => !empty($byCat[$c])));
$serviceSummaries = [];
foreach ($cmpCats as $c) $serviceSummaries[$c] = cd_category_summary($byCat[$c]);
$comparisonRows = [
  ['key'=>'count', 'label'=>'Packages available', 'best'=>'max'],
  ['key'=>'price', 'label'=>'Starting price', 'best'=>'min'],
  ['key'=>'data', 'label'=>'Highest data allowance', 'best'=>'max'],
  ['key'=>'minutes', 'label'=>'Most call minutes', 'best'=>'max'],
  ['key'=>'sms', 'label'=>'Best text allowance', 'best'=>'max'],
  ['key'=>'speed', 'label'=>'Fastest speed', 'best'=>'max'],
  ['key'=>'recommended', 'label'=>'Best price package', 'best'=>'min'],
];
$cur = 'index.php';
$pageTitle = 'Packages & Hardware — CheapDeals.com';
$pageScripts = ['packages.js', 'wishlist.js'];
require __DIR__ . '/../layout/header.php';
?>
<div class="hero">
  <h1>Affordable Mobile, Tablet &amp; Broadband Deals</h1>
  <p>Get automatic <strong>15% off</strong> your first order, plus thousands of exclusive deals.</p>
  <div class="pill">Special codes: SAVE10 &middot; WELCOME5</div>
</div>

<?php if (!empty($recs)): ?>
<div class="recommendations-block" id="recommendationsBlock">
  <h2 class="title" style="display:flex; justify-content:space-between; align-items:center; font-size:18px; margin-bottom:10px;">
    Recommended for You
    <button class="btn sec small" id="clearHistoryBtn" style="font-size:11px; padding:4px 8px;">Clear</button>
  </h2>
  <div class="grid">
    <?php foreach ($recs as $p): 
      $feat = isset($p['features']) ? (json_decode($p['features'], true) ?: []) : [$p['description']]; 
      $is_pkg = isset($p['features']);
      $wish_active = in_array($p['code'], $wishlist, true) ? 'active' : '';
    ?>
      <div class="card" data-cat="<?= esc($p['category'] ?? 'Hardware') ?>" data-tier="<?= esc($p['tier'] ?? 'Hardware') ?>" data-price="<?= $p['price'] ?>" data-name="<?= esc($p['name']) ?>" data-sales="<?= $p['sales_count'] ?>" data-rating="<?= $p['rating_score'] ?>" data-id="<?= $p['id'] ?>">
        <div class="tags">
          <span class="tag cat"><?= esc($p['category'] ?? 'Hardware') ?></span>
          <span class="tag tier-<?= esc($p['tier'] ?? 'Deal') ?>"><?= esc($p['tier'] ?? 'Hardware') ?></span>
          <?php 
            $wish_icon = $wish_active ? '❤️' : '🤍';
            $wish_attr = '';
            if (!$ME || in_array($ME['role'], ['admin', 'staff'], true)) {
                $wish_attr = 'disabled style="opacity: 0.4; cursor: not-allowed; filter: grayscale(100%);"';
            }
          ?>
          <button class="wishlist-btn <?= $wish_active ?>" data-code="<?= esc($p['code']) ?>" title="Save Deal" <?= $wish_attr ?>><?= $wish_icon ?></button>
        </div>
        <div class="card-visual cat-<?= strtolower($p['category'] ?? 'hardware') ?>" style="height:120px; border-radius:8px; margin-bottom:12px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg, rgba(0,229,255,0.06), rgba(255,43,214,0.06)); overflow:hidden; border:1px solid rgba(0,229,255,0.12); position:relative;">
          <?= cd_render_illustration($p['category'] ?? 'Hardware', $p['name']) ?>
        </div>
        <h3><?= esc($p['name']) ?></h3>
        <div class="price"><?= gbp($p['price']) ?><?php if ($is_pkg): ?> <small>/ month</small><?php endif; ?></div>
        
        <div class="card-meta">
          <span class="meta-sales">👤.<?= $p['sales_count'] ?> purchased</span>
          <?php if ($p['rating_count'] > 0): ?>
<span class="meta-rating"><a href="reviews.php?code=<?= urlencode($p['code']) ?>" style="color:var(--brand); text-decoration:underline;">⭐ <?= number_format($p['rating_score'], 1) ?> (👤.<?= $p['rating_count'] ?> reviews)</a></span>
<?php endif; ?>
          <span class="meta-stock">📦 <?= $p['inventory'] ?> left</span>
        </div>

        <ul><?php foreach ($feat as $f): ?><li><?= esc($f) ?></li><?php endforeach; ?></ul>
        <?php if (!$ME || !in_array($ME['role'], ['staff', 'admin'], true)): ?>
      <div class="pkg-actions-row" style="display:flex; flex-direction:column; gap:8px; margin-top:10px;">
        <div style="display:flex; gap:8px; width:100%;">
          <?php if ((int)($p['inventory'] ?? 0) <= 0): ?>
            <a class="btn" style="flex:2; background:#333 !important; color:#777 !important; border: 1px solid #444 !important; cursor:not-allowed; pointer-events:none; opacity:0.7; text-decoration:none; display:flex; align-items:center; justify-content:center; text-transform:uppercase; font-size:12px; font-weight:bold;">Order this</a>
            <a class="btn sec" style="flex:1; background:#222 !important; color:#555 !important; border: 1px solid #333 !important; cursor:not-allowed; pointer-events:none; opacity:0.5; display:flex; align-items:center; justify-content:center; text-decoration:none;">🛒</a>
          <?php else: ?>
            <a class="btn co-btn" style="flex:2;" href="checkout.php?code=<?= urlencode($p['code']) ?>">Order this</a>
            <a class="btn sec cart-btn" href="cart.php?add=<?= urlencode($p['code']) ?>" title="Add to cart" style="flex:1; display:flex; align-items:center; justify-content:center; text-decoration:none;">🛒</a>
          <?php endif; ?>
        </div>
        <a class="btn" href="support.php?inquiry_code=<?= urlencode($p['code']) ?>" style="display:flex; align-items:center; justify-content:center; gap:6px; background: linear-gradient(135deg, #00f2fe 0%, #4facfe 100%); color: #000; font-weight:bold; text-decoration:none; padding:8px; font-size:12px; border-radius:8px; text-transform:uppercase;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
          Ask about this
        </a>
      </div>
    <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php if ($cmpCats): ?>
<div class="tabs" id="viewTabs" style="margin-bottom:20px">
  <a class="active" data-view="grid">📦 Grid view</a>
  <a data-view="compare">⚖️ Compare packages</a>
</div>
<?php endif; ?>

<div id="gridView">
<h2 class="title">Browse packages &amp; deals</h2>
<div class="toolbar">
  <div class="chips" id="chips">
    <?php foreach (['All','Data','Mobile','Broadband','Tablet','Bundles','Hardware'] as $c): ?>
      <button class="chip <?= $c==='All'?'active':'' ?>" data-cat="<?= $c ?>"><?= $c ?></button>
    <?php endforeach; ?>
  </div>
  <div class="sortsel"><span>Sort:</span>
    <select id="sort">
      <option value="price-asc">Price: Low to High</option>
      <option value="price-desc">Price: High to Low</option>
      <option value="name">Name: A to Z</option>
      <option value="popular">Most Popular</option>
      <option value="rating">Highest Rated</option>
      <option value="newest">Newest</option>
    </select>
  </div>
</div>
<div class="search"><input id="search" placeholder="Search packages..."></div>

<div class="grid" id="plist">
<?php 
foreach ($pkgs as $p): 
  $feat = json_decode($p['features'], true) ?: []; 
  $wish_active = in_array($p['code'], $wishlist, true) ? 'active' : '';
?>
  <div class="card" data-cat="<?= esc($p['category']) ?>" data-tier="<?= esc($p['tier']) ?>" data-price="<?= $p['price'] ?>" data-name="<?= esc($p['name']) ?>" data-sales="<?= $p['sales_count'] ?>" data-rating="<?= $p['rating_score'] ?>" data-id="<?= $p['id'] ?>" onclick="logView('<?= esc($p['code']) ?>')">
    <div class="tags">
      <span class="tag cat"><?= esc($p['category']) ?></span>
      <span class="tag tier-<?= esc($p['tier']) ?>"><?= esc($p['tier']) ?></span>
      <?php 
        $wish_icon = $wish_active ? '❤️' : '🤍';
        $wish_attr = '';
        if (!$ME || in_array($ME['role'], ['admin', 'staff'], true)) {
            $wish_attr = 'disabled style="opacity: 0.4; cursor: not-allowed; filter: grayscale(100%);"';
        }
      ?>
      <button class="wishlist-btn <?= $wish_active ?>" data-code="<?= esc($p['code']) ?>" title="Save Deal" <?= $wish_attr ?>><?= $wish_icon ?></button>
    </div>
    <div class="card-visual cat-<?= strtolower($p['category'] ?? 'hardware') ?>" style="height:120px; border-radius:8px; margin-bottom:12px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg, rgba(0,229,255,0.06), rgba(255,43,214,0.06)); overflow:hidden; border:1px solid rgba(0,229,255,0.12); position:relative;">
      <?= cd_render_illustration($p['category'] ?? 'Hardware', $p['name']) ?>
    </div>
    <h3><?= esc($p['name']) ?></h3>
    <div class="price"><?= gbp($p['price']) ?> <small>/ month</small></div>
    
    <div class="card-meta">
      <span class="meta-sales">👤.<?= $p['sales_count'] ?> purchased</span>
      <?php if ($p['rating_count'] > 0): ?>
<span class="meta-rating"><a href="reviews.php?code=<?= urlencode($p['code']) ?>" style="color:var(--brand); text-decoration:underline;">⭐ <?= number_format($p['rating_score'], 1) ?> (👤.<?= $p['rating_count'] ?> reviews)</a></span>
<?php endif; ?>
      <span class="meta-stock">📦 <?= $p['inventory'] ?> left</span>
    </div>

    <ul><?php foreach ($feat as $f): ?><li><?= esc($f) ?></li><?php endforeach; ?></ul>
    <?php if (!$ME || !in_array($ME['role'], ['staff', 'admin'], true)): ?>
      <div class="pkg-actions-row" style="display:flex; flex-direction:column; gap:8px; margin-top:10px;">
        <div style="display:flex; gap:8px; width:100%;">
          <?php if ((int)($p['inventory'] ?? 0) <= 0): ?>
            <a class="btn" style="flex:2; background:#333 !important; color:#777 !important; border: 1px solid #444 !important; cursor:not-allowed; pointer-events:none; opacity:0.7; text-decoration:none; display:flex; align-items:center; justify-content:center; text-transform:uppercase; font-size:12px; font-weight:bold;">Order this</a>
            <a class="btn sec" style="flex:1; background:#222 !important; color:#555 !important; border: 1px solid #333 !important; cursor:not-allowed; pointer-events:none; opacity:0.5; display:flex; align-items:center; justify-content:center; text-decoration:none;">🛒</a>
          <?php else: ?>
            <a class="btn co-btn" style="flex:2;" href="checkout.php?code=<?= urlencode($p['code']) ?>">Order this</a>
            <a class="btn sec cart-btn" href="cart.php?add=<?= urlencode($p['code']) ?>" title="Add to cart" style="flex:1; display:flex; align-items:center; justify-content:center; text-decoration:none;">🛒</a>
          <?php endif; ?>
        </div>
        <a class="btn" href="support.php?inquiry_code=<?= urlencode($p['code']) ?>" style="display:flex; align-items:center; justify-content:center; gap:6px; background: linear-gradient(135deg, #00f2fe 0%, #4facfe 100%); color: #000; font-weight:bold; text-decoration:none; padding:8px; font-size:12px; border-radius:8px; text-transform:uppercase;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
          Ask about this
        </a>
      </div>
    <?php endif; ?>
  </div>
<?php endforeach; ?>

<?php 
foreach ($prods as $p): 
  $wish_active = in_array($p['code'], $wishlist, true) ? 'active' : '';
?>
  <div class="card" data-cat="Hardware" data-tier="Hardware" data-price="<?= $p['price'] ?>" data-name="<?= esc($p['name']) ?>" data-sales="<?= $p['sales_count'] ?>" data-rating="<?= $p['rating_score'] ?>" data-id="<?= $p['id'] ?>" onclick="logView('<?= esc($p['code']) ?>')">
    <div class="tags">
      <span class="tag cat">Hardware</span>
      <span class="tag tier-Deal">Device</span>
      <?php 
        $wish_icon = $wish_active ? '❤️' : '🤍';
        $wish_attr = '';
        if (!$ME || in_array($ME['role'], ['admin', 'staff'], true)) {
            $wish_attr = 'disabled style="opacity: 0.4; cursor: not-allowed; filter: grayscale(100%);"';
        }
      ?>
      <button class="wishlist-btn <?= $wish_active ?>" data-code="<?= esc($p['code']) ?>" title="Save Deal" <?= $wish_attr ?>><?= $wish_icon ?></button>
    </div>
    <div class="card-visual cat-hardware" style="height:120px; border-radius:8px; margin-bottom:12px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg, rgba(0,229,255,0.06), rgba(255,43,214,0.06)); overflow:hidden; border:1px solid rgba(0,229,255,0.12); position:relative;">
      <?= cd_render_illustration('Hardware', $p['name']) ?>
    </div>
    <h3><?= esc($p['name']) ?></h3>
    <div class="price"><?= gbp($p['price']) ?></div>
    
    <div class="card-meta">
      <span class="meta-sales">👤.<?= $p['sales_count'] ?> purchased</span>
      <?php if ($p['rating_count'] > 0): ?>
<span class="meta-rating"><a href="reviews.php?code=<?= urlencode($p['code']) ?>" style="color:var(--brand); text-decoration:underline;">⭐ <?= number_format($p['rating_score'], 1) ?> (👤.<?= $p['rating_count'] ?> reviews)</a></span>
<?php endif; ?>
      <span class="meta-stock">📦 <?= $p['inventory'] ?> left</span>
    </div>

    <ul>
      <li><?= esc($p['description']) ?></li>
    </ul>
    <?php if (!$ME || !in_array($ME['role'], ['staff', 'admin'], true)): ?>
      <div class="pkg-actions-row" style="display:flex; flex-direction:column; gap:8px; margin-top:10px;">
        <div style="display:flex; gap:8px; width:100%;">
          <?php if ((int)($p['inventory'] ?? 0) <= 0): ?>
            <a class="btn" style="flex:2; background:#333 !important; color:#777 !important; border: 1px solid #444 !important; cursor:not-allowed; pointer-events:none; opacity:0.7; text-decoration:none; display:flex; align-items:center; justify-content:center; text-transform:uppercase; font-size:12px; font-weight:bold;">Order this</a>
            <a class="btn sec" style="flex:1; background:#222 !important; color:#555 !important; border: 1px solid #333 !important; cursor:not-allowed; pointer-events:none; opacity:0.5; display:flex; align-items:center; justify-content:center; text-decoration:none;">🛒</a>
          <?php else: ?>
            <a class="btn co-btn" style="flex:2;" href="checkout.php?code=<?= urlencode($p['code']) ?>">Order this</a>
            <a class="btn sec cart-btn" href="cart.php?add=<?= urlencode($p['code']) ?>" title="Add to cart" style="flex:1; display:flex; align-items:center; justify-content:center; text-decoration:none;">🛒</a>
          <?php endif; ?>
        </div>
        <a class="btn" href="support.php?inquiry_code=<?= urlencode($p['code']) ?>" style="display:flex; align-items:center; justify-content:center; gap:6px; background: linear-gradient(135deg, #00f2fe 0%, #4facfe 100%); color: #000; font-weight:bold; text-decoration:none; padding:8px; font-size:12px; border-radius:8px; text-transform:uppercase;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
          Ask about this
        </a>
      </div>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
</div>
<p class="lead" id="empty" style="display:none">No packages or hardware match your filters.</p>
</div> <!-- #gridView -->

<?php if ($cmpCats): ?>
<div id="compareView" style="display:none">
  <p class="lead">Side-by-side comparison of allowances and pricing and the best value in each row is highlighted.</p>
  <div class="table-scroll cmp-table service-matrix">
    <table>
      <thead><tr>
        <th>Comparison point</th>
        <?php foreach ($cmpCats as $c): ?><th><?= esc($c) ?></th><?php endforeach; ?>
      </tr></thead>
      <tbody>
        <?php foreach ($comparisonRows as $row):
          $values = [];
          foreach ($cmpCats as $c) {
            $value = $serviceSummaries[$c][$row['key']]['value'];
            if ($value !== null) $values[] = $value;
          }
          $bestValue = null;
          if ($values && count(array_unique($values, SORT_REGULAR)) > 1) {
            $bestValue = $row['best'] === 'min' ? min($values) : max($values);
          }
        ?>
          <tr>
            <th><?= esc($row['label']) ?></th>
            <?php foreach ($cmpCats as $c):
              $cell = $serviceSummaries[$c][$row['key']];
              $isBest = $bestValue !== null && $cell['value'] !== null && $cell['value'] === $bestValue;
            ?>
              <td class="<?= $isBest ? 'spec-best' : '' ?>"><?= esc($cell['text']) ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="lead">Open a category below for a package-by-package breakdown.</p>
  <div class="tabs" id="compareCatTabs">
    <?php foreach ($cmpCats as $i => $c): ?>
      <a class="<?= $i===0 ? 'active' : '' ?>" data-cmpcat="<?= esc($c) ?>"><?= esc($c) ?></a>
    <?php endforeach; ?>
  </div>
  <?php foreach ($cmpCats as $i => $c):
    $rowLabels = ['device'=>'Includes','minutes'=>'Calls','data'=>'Data','speed'=>'Speed','sms'=>'Texts'];
    $specsByPkg = [];
    foreach ($byCat[$c] as $p) {
      $features_arr = [];
      if (isset($p['features'])) {
          $features_arr = json_decode($p['features'], true) ?: [];
      } else if (isset($p['description'])) {
          $features_arr = [$p['description']];
      }
      $specsByPkg[] = cd_pkg_specs($features_arr);
    }
    $activeRows = [];
    foreach ($rowLabels as $key => $label) {
      foreach ($specsByPkg as $s) { if ($s[$key] !== null) { $activeRows[$key] = $label; break; } }
    }
    $prices = array_map(fn($p) => (float)$p['price'], $byCat[$c]);
    $minPrice = min($prices);
  ?>
    <div class="table-scroll cmp-table" data-cmpcat="<?= esc($c) ?>" style="<?= $i===0 ? '' : 'display:none' ?>">
      <table>
        <thead><tr><th>Details</th><?php foreach ($byCat[$c] as $p): ?><th><?= esc($p['name']) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
          <tr><th>Monthly price</th><?php foreach ($byCat[$c] as $p): $isBest = (float)$p['price']===$minPrice; ?>
            <td class="<?= $isBest?'spec-best':'' ?>"><?= gbp($p['price']) ?></td><?php endforeach; ?></tr>
          <?php foreach ($activeRows as $key => $label):
            $rowValues = [];
            foreach ($specsByPkg as $s) { if ($s[$key] !== null) $rowValues[] = cd_spec_value($s[$key]); }
            $bestVal = null;
            if ($rowValues && count(array_unique($rowValues, SORT_REGULAR)) > 1) {
              $bestVal = $key === 'device' ? null : max($rowValues);
            }
          ?>
            <tr><th><?= esc($label) ?></th>
              <?php foreach ($byCat[$c] as $j => $p):
                $valText = $specsByPkg[$j][$key] ?? 'Not listed';
                $valNum = cd_spec_value($valText);
                $isBest = $bestVal !== null && $valNum !== null && $valNum === $bestVal;
              ?>
                <td class="<?= $isBest?'spec-best':'' ?>"><?= esc($valText) ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
          <tr><th>Order now</th><?php foreach ($byCat[$c] as $p): ?><td>
            <?php if ((int)($p['inventory'] ?? 0) <= 0): ?>
              <a class="btn small" style="background:#555 !important; color:#aaa !important; border:1px solid #444 !important; cursor:not-allowed; pointer-events:none; opacity:0.6; text-decoration:none; display:inline-block; padding:4px 8px; font-size:11px; font-weight:bold;">Out of stock</a>
            <?php else: ?>
              <a class="btn small" href="checkout.php?code=<?= urlencode($p['code']) ?>">Order</a>
            <?php endif; ?>
          </td><?php endforeach; ?></tr>
        </tbody>
      </table>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../layout/footer.php'; ?>
