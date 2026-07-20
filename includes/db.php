<?php
/* PDO connection. Provides $pdo and $CFG to whatever includes this. */
$CFG = require __DIR__ . '/config.php';
try {
  $dsn = "mysql:host={$CFG['host']};dbname={$CFG['db']};charset={$CFG['charset']}";
  $pdo = new PDO($dsn, $CFG['user'], $CFG['pass'], [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  die('<div style="font-family:Arial,sans-serif;background:#0a0612;color:#ff7d7d;padding:24px;font-size:15px">'
    . 'Cannot connect to the database. Start <b>MySQL</b> in XAMPP and import <b>db/cheapdeals_v2.sql</b> in phpMyAdmin.</div>');
}
