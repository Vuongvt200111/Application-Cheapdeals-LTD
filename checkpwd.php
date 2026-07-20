<?php
/* FR22 - Secure Payment Password Authentication Modal
   Verifies the logged-in user's account password for the checkout modal.
   Always returns clean JSON {ok:true|false} — any stray output or error
   from the includes it pulls in is swallowed here instead of leaking an
   HTML/warning page that would break the modal's fetch().json() call
   (this endpoint exists purely for instant feedback; checkout.php's
   "pay" action re-checks the password server-side regardless). */
ob_start();
$ok = false;
try {
  require_once __DIR__ . '/includes/auth.php';
  if ($ME && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw = $_POST['password'] ?? '';
    if ($pw !== '') {
      /* Don't rely on $ME already containing the password hash — look it
         up fresh so this works regardless of which columns auth.php put
         into $ME. */
      $s = $pdo->prepare('SELECT password FROM users WHERE id=?');
      $s->execute([$ME['id']]);
      $hash = $s->fetchColumn();
      $ok = $hash !== false && $hash !== null && password_verify($pw, $hash);
    }
  }
} catch (Throwable $e) {
  $ok = false;
}
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => $ok]);
