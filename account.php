<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/AccountController.php';
$controller = new AccountController($pdo, $ME);
$controller->dashboard();
