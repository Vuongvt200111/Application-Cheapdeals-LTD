<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/SupportController.php';
$controller = new SupportController($pdo, $ME);
$controller->chat();
