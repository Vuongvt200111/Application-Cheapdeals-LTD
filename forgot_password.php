<?php
require_once __DIR__ . '/includes/auth.php';
if ($ME) redirect('index.php');

// Helper to send email
function sendResetEmail($email, $code) {
    $subject = "CheapDeals Password Reset Verification Code";
    $message = "Your 6-digit password reset verification code is: $code";
    $headers = "From: no-reply@cheapdeals.com";
    $logPath = 'C:/Users/admin/.gemini/antigravity/brain/bdc7f455-ab25-4e53-9789-454d163d6bb2/scratch/email_codes.log';
    @file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] [$email] Reset Code: $code\n", FILE_APPEND);
    try {
        @mail($email, $subject, $message, $headers);
    } catch (Throwable $e) {}
}

// Handle AJAX code sending
if (isset($_GET['action']) && $_GET['action'] === 'send_code') {
    header('Content-Type: application/json');
    $email = trim($_POST['email'] ?? '');
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Please enter a valid email address.']);
        exit;
    }
    // Check if email actually exists
    $s = $pdo->prepare('SELECT id FROM users WHERE email=?');
    $s->execute([$email]);
    if (!$s->fetch()) {
        echo json_encode(['error' => 'No account found with this email address.']);
        exit;
    }
    $code = sprintf('%06d', mt_rand(100000, 999999));
    $s_save = $pdo->prepare('INSERT INTO email_verifications (email, code) VALUES (?, ?) ON DUPLICATE KEY UPDATE code = VALUES(code)');
    $s_save->execute([$email, $code]);
    sendResetEmail($email, $code);
    echo json_encode(['success' => true, 'message' => 'Code sent! Check scratch/email_codes.log if SMTP is not configured.']);
    exit;
}

$err = '';
$ok = '';
$step = 1; // 1 = Input email & code, 2 = Input new password
$email_validated = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step_1'])) {
        $email = trim($_POST['email'] ?? '');
        $code = trim($_POST['code'] ?? '');
        
        $s_check = $pdo->prepare('SELECT code FROM email_verifications WHERE email=?');
        $s_check->execute([$email]);
        $saved_code = $s_check->fetchColumn();
        
        if (!$saved_code || $saved_code !== $code) {
            $err = 'Invalid verification code.';
        } else {
            $step = 2;
            $email_validated = $email;
        }
    } elseif (isset($_POST['step_2'])) {
        $email = trim($_POST['validated_email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');
        
        if ($password !== $confirm) {
            $err = 'Passwords do not match.';
            $step = 2;
            $email_validated = $email;
        } elseif (strlen($password) < 6) {
            $err = 'Password must be at least 6 characters.';
            $step = 2;
            $email_validated = $email;
        } else {
            // Delete verification code
            $pdo->prepare('DELETE FROM email_verifications WHERE email=?')->execute([$email]);
            
            // Update password
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password=? WHERE email=?')->execute([$hash, $email]);
            
            $ok = 'Password reset successfully! You can now log in.';
            $step = 3;
        }
    }
}

$cur = 'forgot_password.php'; $pageTitle = 'Forgot Password';
require __DIR__ . '/includes/header.php';
?>
<h2 class="title" style="text-align:center">Forgot Password</h2>
<div class="panel form-box">
  <?php if ($err): ?><div class="msg err"><?= esc($err) ?></div><?php endif; ?>
  <?php if ($ok): ?><div class="msg ok"><?= esc($ok) ?></div><?php endif; ?>
  
  <?php if ($step === 1): ?>
    <form method="post">
      <input type="hidden" name="step_1" value="1">
      <div class="fg">
        <label>Email Address</label>
        <div style="display:flex; gap:8px;">
          <input name="email" id="emailField" type="email" style="flex:1;" required>
          <button type="button" class="btn small" id="sendCodeBtn" style="white-space:nowrap;">Send Code</button>
        </div>
      </div>
      <div class="fg">
        <label>Verification Code</label>
        <input name="code" maxlength="6" placeholder="Enter 6-digit code" required>
      </div>
      <button class="btn block" type="submit" style="margin-top:15px;">Verify Code</button>
    </form>
    
  <?php elseif ($step === 2): ?>
    <form method="post">
      <input type="hidden" name="step_2" value="1">
      <input type="hidden" name="validated_email" value="<?= esc($email_validated) ?>">
      <div class="fg">
        <label>New Password</label>
        <input name="password" type="password" required>
      </div>
      <div class="fg">
        <label>Confirm New Password</label>
        <input name="confirm_password" type="password" required>
      </div>
      <button class="btn block" type="submit" style="margin-top:15px;">Reset Password</button>
    </form>
    
  <?php else: ?>
    <div style="text-align:center;">
      <a href="login.php" class="btn">Proceed to Login</a>
    </div>
  <?php endif; ?>
  
  <?php if ($step === 1): ?>
    <p class="foot">Remember your password? <a href="login.php">Log in</a></p>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const sendBtn = document.getElementById('sendCodeBtn');
  const emailField = document.getElementById('emailField');
  
  if (sendBtn && emailField) {
    sendBtn.addEventListener('click', () => {
      const email = emailField.value.trim();
      if (!email) {
        toast('Please enter your email address first.');
        return;
      }
      
      sendBtn.disabled = true;
      sendBtn.textContent = 'Sending...';
      
      const formData = new FormData();
      formData.append('email', email);
      
      fetch('forgot_password.php?action=send_code', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.error) {
          toast(data.error);
          sendBtn.disabled = false;
          sendBtn.textContent = 'Send Code';
        } else {
          toast(data.message || 'Verification code sent!');
          let seconds = 60;
          const timer = setInterval(() => {
            seconds--;
            if (seconds <= 0) {
              clearInterval(timer);
              sendBtn.disabled = false;
              sendBtn.textContent = 'Send Code';
            } else {
              sendBtn.textContent = `Retry in ${seconds}s`;
            }
          }, 1000);
        }
      })
      .catch(err => {
        console.error(err);
        toast('Failed to send verification code.');
        sendBtn.disabled = false;
        sendBtn.textContent = 'Send Code';
      });
    });
  }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
