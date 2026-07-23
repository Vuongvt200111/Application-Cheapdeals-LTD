<?php

if (isset($_GET['action']) && $_GET['action'] === 'check_unread_count') {
  header('Content-Type: application/json');
  $count = 0;
  if ($ME) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM support_messages WHERE user_id=? AND sender_role='staff' AND is_read=0");
    $s->execute([$ME['id']]);
    $count = (int)$s->fetchColumn();
  }
  echo json_encode(['unread_count' => $count]);
  exit;
}

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/SupportController.php';
$controller = new SupportController($pdo, $ME);
$controller->chat();
