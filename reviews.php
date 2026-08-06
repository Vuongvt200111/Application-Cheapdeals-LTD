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
  
  <?php
    $img = function_exists('cd_product_card_image') ? cd_product_card_image($code, $p['category'] ?? '') : '';
    $descText = '';
    if (!empty($p['description'])) {
        $descText = $p['description'];
    } elseif (!empty($p['features'])) {
        if (is_array($p['features'])) {
            $descText = implode(' • ', $p['features']);
        } else {
            $descText = str_replace(["\r\n", "\n"], ' • ', trim($p['features']));
        }
    }
    $unitLabel = function_exists('cd_get_unit_label') ? cd_get_unit_label($p) : ($isPkg ? 'month' : '');
  ?>

  <div class="panel" style="margin-bottom:30px; display:flex; gap:24px; align-items:stretch; flex-wrap:wrap; border:1px solid rgba(0,229,255,0.25); background:linear-gradient(135deg, rgba(7,11,22,0.9), rgba(15,23,42,0.9)); padding:24px; border-radius:14px; box-shadow:0 8px 32px rgba(0,0,0,0.4);">
    <?php if ($img !== ''): ?>
      <div style="flex:0 0 280px; width:280px; min-height:260px; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.4); border-radius:12px; padding:18px; border:1px solid rgba(0,229,255,0.2); align-self:stretch;">
        <img class="card-photo" src="<?= htmlspecialchars($img, ENT_QUOTES) ?>" alt="<?= esc($p['name']) ?>" style="width:100%; max-height:260px; height:100%; object-fit:contain; display:block; margin:auto;" />
      </div>
    <?php endif; ?>

    <div style="flex:1; min-width:280px;">
      <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
        <span class="role-badge" style="background:rgba(0,229,255,0.15); color:var(--cyan); padding:3px 10px; border-radius:6px; font-size:11px; font-weight:700; text-transform:uppercase; border:1px solid rgba(0,229,255,0.3);">
          <?= esc($p['category'] ?? 'Product') ?>
        </span>
      </div>

      <h2 class="title" style="margin-bottom:8px; font-size:24px; color:var(--heading);"><?= esc($p['name']) ?></h2>
      
      <div style="font-size:26px; font-weight:800; color:var(--cyan); margin-bottom:12px;">
          <?= gbp($p['price']) ?><?php if ($unitLabel !== ''): ?> <small style="font-size:13px; font-weight:500; color:var(--muted)">/ <?= esc($unitLabel) ?></small><?php endif; ?>
      </div>

      <div style="display:flex; align-items:center; gap:8px; font-size:16px; margin-bottom:16px;">
          <span style="color:var(--brand); font-weight:bold;">⭐ <?= number_format($p['rating_score'], 1) ?></span>
          <span style="color:var(--muted);">(<?= $p['rating_count'] ?> customer reviews)</span>
      </div>

      <?php if (!empty($descText)): ?>
        <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); padding:14px 18px; border-radius:10px; margin-bottom:18px;">
          <div style="font-size:11px; font-weight:700; text-transform:uppercase; color:var(--cyan); margin-bottom:6px; letter-spacing:0.5px;">✓ Product Description &amp; Specifications</div>
          <p style="font-size:14px; color:var(--ink); line-height:1.6; margin:0;">
            <?= esc($descText) ?>
          </p>
        </div>
      <?php endif; ?>

      <div style="display:flex; gap:12px; flex-wrap:wrap;">
        <a class="btn co-btn" href="checkout.php?code=<?= urlencode($code) ?>" style="padding:10px 22px; text-decoration:none; font-weight:bold; font-size:13px; border-radius:8px;">Order This</a>
        <a class="btn" href="support.php?inquire_pkg=<?= urlencode($p['name']) ?>" style="padding:10px 22px; text-decoration:none; font-weight:bold; font-size:13px; border-radius:8px; background:linear-gradient(135deg, #00f2fe 0%, #4facfe 100%); color:#000;">Ask about this</a>
      </div>
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
