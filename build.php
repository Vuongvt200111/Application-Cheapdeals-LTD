<?php
require_once __DIR__ . '/includes/auth.php';

// Handle custom requirement POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['act'] ?? '') === 'submit_requirement') {
  requireLogin($ME);
  $device  = trim($_POST['device'] ?? 'No device');
  $minutes = trim($_POST['minutes'] ?? 'None');
  $data    = trim($_POST['data'] ?? 'None');
  $sms     = trim($_POST['sms'] ?? 'None');
  $addons  = trim($_POST['addons'] ?? '');
  $price   = (float)($_POST['price'] ?? 10.00);
  $name    = trim($_POST['name'] ?? 'Custom Package') ?: 'Custom Package';
  
  $s = $pdo->prepare('INSERT INTO custom_requirements(user_id, name, device, minutes, data, sms, addons, price, status) VALUES (?,?,?,?,?,?,?,?,?)');
  $s->execute([$ME['id'], $name, $device, $minutes, $data, $sms, $addons, $price, 'Pending']);
  
  // Log audit
  audit($pdo, $ME['email'], 'custom_build_requirement', "Submitted custom requirement: $name (£$price/mo)");
  
  redirect('build.php?msg=' . urlencode('Your custom requirement has been submitted to Staff for confirmation.'));
}

$cur = 'build.php'; $pageTitle = 'Build your own';
require __DIR__ . '/views/product/build.php';
