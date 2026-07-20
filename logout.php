<?php
require_once __DIR__ . '/includes/auth.php';
$_SESSION = [];
session_destroy();
redirect('index.php?msg=' . urlencode('Signed out.'));
