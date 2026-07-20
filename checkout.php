<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/CheckoutController.php';
$controller = new CheckoutController($pdo, $ME);
$controller->checkout();
