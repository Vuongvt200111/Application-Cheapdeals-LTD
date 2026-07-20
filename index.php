<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/HomeController.php';
$controller = new HomeController($pdo, $ME);
$controller->index();
