<?php
require_once __DIR__ . '/includes/auth.php';
if ($ME) redirect('index.php');
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? ''); $pass = $_POST['password'] ?? '';
  $s = $pdo->prepare('SELECT * FROM users WHERE email=?'); $s->execute([$email]); $u = $s->fetch();
  if (!$u || !password_verify($pass, $u['password'])) {
    $err = 'Wrong email or password.';
  } else {
    $_SESSION['uid'] = (int)$u['id'];
    redirect($u['role']==='admin' ? 'admin.php' : ($u['role']==='staff' ? 'staff.php' : 'account.php'));
  }
}
$cur = 'login.php'; $pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<h2 class="title" style="text-align:center">Log in</h2>
<div class="panel form-box">
  <?php if ($err): ?><div class="msg err"><?= esc($err) ?></div><?php endif; ?>
  <form method="post">
    <div class="fg"><label>Email</label><input name="email" type="email" required></div>
    <div class="fg"><label>Password <a href="forgot_password.php" style="float:right;font-size:0.85rem;color:var(--cyan);">Forgot Password?</a></label><input name="password" type="password" required></div>
    <div style="text-align:right; margin:-10px 0 15px;"><a href="forgot_password.php" style="font-size:13px; color:var(--brand);">Forgot password?</a></div>
    <button class="btn block" type="submit">Log in</button>
  </form>
  <p class="foot">No account yet? <a href="register.php">Register here</a></p>
  <div class="hint">Demo logins &mdash; Admin: <b>admin@cheapdeals.com / admin123</b> &middot; Staff: <b>staff@cheapdeals.com / staff123</b>.</div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
