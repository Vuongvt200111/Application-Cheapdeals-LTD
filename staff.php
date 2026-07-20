<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/StaffController.php';
$controller = new StaffController($pdo, $ME);
$controller->dashboard();
