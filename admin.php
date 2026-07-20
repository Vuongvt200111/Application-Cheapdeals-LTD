<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/AdminController.php';
$controller = new AdminController($pdo, $ME);
$controller->dashboard();
