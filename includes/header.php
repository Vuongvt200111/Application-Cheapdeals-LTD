<?php
require_once __DIR__ . '/auth.php';   // $pdo, $CFG, $ME, helpers

/* nav items by role: [file, label, icon] */
function navItems($me){
  if (!$me) return [['index.php','Packages','📦'],['build.php','Build','🛠️'],['offers.php','Offers','🏷️'],['login.php','Login','🔑'],['register.php','Sign up','✍️']];
  if ($me['role']==='user')  return [['index.php','Packages','📦'],['build.php','Build','🛠️'],['offers.php','Offers','🏷️'],['support.php','Support','💬'],['account.php','Account','👤']];
  if ($me['role']==='staff') return [['index.php','Packages','📦'],['staff.php','Staff','🧰']];
  return [['index.php','Packages','📦'],['admin.php','Admin','🛡️'],['staff.php','Staff','🧰']];
}

$view      = currentView();
$cur       = isset($cur) ? $cur : basename($_SERVER['PHP_SELF']);
$pageTitle = isset($pageTitle) ? $pageTitle : 'CheapDeals.com v3';
$items     = navItems($ME);
$other     = ($view === 'mobile') ? 'desktop' : 'mobile';
$tp = $_GET; $tp['view'] = $other;
$toggleHref  = $cur . '?' . http_build_query($tp);
$toggleLabel = ($view === 'mobile') ? '🖥️ Desktop' : '📱 Mobile';

/* right-side cluster: theme + view toggle + (avatar/role/sign out) */
ob_start(); ?>
  <button class="theme-btn" id="themeBtn" type="button" title="Theme">🌙</button>
  <a class="theme-btn" href="<?= esc($toggleHref) ?>" title="Switch interface"><?= $toggleLabel ?></a>
  <?php if ($ME): ?>
    <span class="avatar"><?= esc(strtoupper(substr($ME['name'],0,1))) ?></span>
    <?php if ($ME['role']!=='user'): ?><span class="role-badge role-<?= esc($ME['role']) ?>"><?= esc($ME['role']) ?></span><?php endif; ?>
    <a class="btn sec small" href="logout.php">Sign out</a>
  <?php endif;
$navRight = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
<title><?= esc($pageTitle) ?></title>
<meta name="theme-color" content="#070b16" />
<meta name="color-scheme" content="dark" />
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700;900&family=Share+Tech+Mono&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="css/base.css" />
<link rel="stylesheet" href="css/<?= $view==='mobile' ? 'mobile.css' : 'desktop.css' ?>" />
</head>
<body class="view-<?= esc($view) ?>">
<?php if ($view === 'mobile'): ?>
<div class="device">
  <div class="screen" id="screen">
    <header class="nav">
      <div class="brand" onclick="location.href='index.php'">Cheap<span>Deals</span>.com</div>
      <div class="nav-right"><?= $navRight ?></div>
    </header>
    <main class="wrap">
<?php else: ?>
    <header class="nav">
      <div class="brand" onclick="location.href='index.php'">Cheap<span>Deals</span>.com</div>
      <div class="nav-links">
        <?php foreach ($items as $it): ?>
          <a class="<?= $it[0]===$cur ? 'active' : '' ?>" href="<?= esc($it[0]) ?>"><?= esc($it[1]) ?></a>
        <?php endforeach; ?>
      </div>
      <div class="nav-right"><?= $navRight ?></div>
    </header>
    <main class="wrap">
<?php endif; ?>
