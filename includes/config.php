<?php
/* ============================================================
   CheapDeals v3 — database settings.
   Reuses the SAME database as v2 ("cheapdeals_v2"), so you do
   NOT need to import again. Defaults are for XAMPP (root / no pass).
   ============================================================ */
return [
  'host'    => '127.0.0.1',
  'db'      => 'cheapdeals_v2',
  'user'    => 'root',
  'pass'    => '',
  'charset' => 'utf8mb4',
  // The ONE admin account that is permanently fixed (cannot be demoted).
  'primary_admin' => 'admin@cheapdeals.com',
];
