<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Package.php';
require_once __DIR__ . '/models/Product.php';

$code = trim($_GET['code'] ?? '');
$p = Package::getByCode($code) ?: Product::getByCode($code);
if (!$p && strpos($code, 'data-') === 0) {
  // Support Data Packages fallback
  $dataFallback = [
    'data-hourly-3gb' => ['name'=>'Data Plan - 3GB', 'price'=>2.50, 'unit'=>'Hourly', 'category'=>'Data', 'features'=>1, 'rating_score'=>0, 'rating_count'=>0],
    'data-1day-1.2gb' => ['name'=>'Data Plan - 1.2GB', 'price'=>1.00, 'unit'=>'1-Day', 'category'=>'Data', 'features'=>1, 'rating_score'=>0, 'rating_count'=>0],
    'data-1day-7gb' => ['name'=>'5G Data - 7GB', 'price'=>2.00, 'unit'=>'1-Day', 'category'=>'Data', 'features'=>1, 'rating_score'=>0, 'rating_count'=>0],
    'data-3day-25gb' => ['name'=>'5G Data - 25GB', 'price'=>4.00, 'unit'=>'3-Day', 'category'=>'Data', 'features'=>1, 'rating_score'=>0, 'rating_count'=>0],
    'data-7day-65gb' => ['name'=>'5G Data - 65GB', 'price'=>9.00, 'unit'=>'7-Day', 'category'=>'Data', 'features'=>1, 'rating_score'=>0, 'rating_count'=>0],
    'data-30day-66gb' => ['name'=>'30-Day Deal - 66GB', 'price'=>15.00, 'unit'=>'30-Day', 'category'=>'Data', 'features'=>1, 'rating_score'=>0, 'rating_count'=>0]
  ];
  if (isset($dataFallback[$code])) {
    $p = $dataFallback[$code];
  }
}

if (!$p) {
    redirect('index.php');
}

// Fetch reviews for this item
$s = $pdo->prepare("
    SELECT r.rating, r.comment, r.created_at, u.name as user_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.item_code = ? 
    ORDER BY r.created_at DESC
");
$s->execute([$code]);
$reviews = $s->fetchAll();

$isPkg = isset($p['features']);
$cur = 'index.php'; 
$pageTitle = esc($p['name']) . ' - Reviews';
require __DIR__ . '/includes/header.php';
?>

<div class="wrap">
  <div style="margin-bottom:20px;">
    <a href="index.php" style="font-weight:bold; color:var(--brand);">&larr; Back to Catalog</a>
  </div>
  
  <div class="panel" style="margin-bottom:30px;">
    <h2 class="title" style="margin-bottom:10px;"><?= esc($p['name']) ?></h2>
    <div style="font-size:24px; font-weight:800; color:var(--heading); margin-bottom:15px;">
        <?= gbp($p['price']) ?><?php if ($isPkg): ?> <small style="font-size:13px; font-weight:500; color:var(--muted)">/ month</small><?php endif; ?>
    </div>
    
    <div style="display:flex; align-items:center; gap:8px; font-size:18px;">
        <span style="color:var(--brand); font-weight:bold;">⭐ <?= number_format($p['rating_score'], 1) ?></span>
        <span style="color:var(--muted);">(<?= $p['rating_count'] ?> customer reviews)</span>
    </div>
  </div>
  
  <h3 class="sec-h">Customer Reviews</h3>
  
  <?php if (empty($reviews)): ?>
    <p class="lead">No reviews yet for this product. Be the first to purchase and review it!</p>
  <?php else: ?>
    <div style="display:grid; gap:16px;">
      <?php foreach ($reviews as $r): ?>
        <div class="panel" style="border:1px solid var(--line); background:rgba(255,255,255,0.01); padding:16px; border-radius:10px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
            <strong style="color:var(--heading);"><?= esc($r['user_name']) ?></strong>
            <span style="font-size:12px; color:var(--muted);"><?= esc(substr($r['created_at'], 0, 10)) ?></span>
          </div>
          <div style="color:var(--brand); margin-bottom:8px; font-size:14px;">
            <?= str_repeat('⭐', $r['rating']) ?>
          </div>
          <p style="font-size:15px; color:var(--ink); line-height:1.5;"><?= esc($r['comment']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
