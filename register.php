<?php
require_once __DIR__ . '/includes/auth.php';
if ($ME) redirect('index.php');

// Helper to send email (simulated & backup log for local testing)
function sendVerificationEmail($email, $code) {
    $subject = "CheapDeals Account Verification Code";
    $message = "Your 6-digit verification code is: $code";
    $headers = "From: no-reply@cheapdeals.com";
    $logPath = 'C:/Users/admin/.gemini/antigravity/brain/bdc7f455-ab25-4e53-9789-454d163d6bb2/scratch/email_codes.log';
    @file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] [$email] Code: $code\n", FILE_APPEND);
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
    $s = $pdo->prepare('SELECT id FROM users WHERE email=?');
    $s->execute([$email]);
    if ($s->fetch()) {
        echo json_encode(['error' => 'An account with this email already exists.']);
        exit;
    }
    $code = sprintf('%06d', mt_rand(100000, 999999));
    $s_save = $pdo->prepare('INSERT INTO email_verifications (email, code) VALUES (?, ?) ON DUPLICATE KEY UPDATE code = VALUES(code)');
    $s_save->execute([$email, $code]);
    sendVerificationEmail($email, $code);
    echo json_encode(['success' => true, 'message' => 'Code sent! Check scratch/email_codes.log if SMTP is not configured.']);
    exit;
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $f = [];
  foreach (['name','email','password','address','phone','email_code'] as $k) {
      $f[$k] = trim($_POST[$k] ?? '');
  }
  
  $empty = false; 
  foreach ($f as $v) if ($v === '') $empty = true;
  
  if ($empty) {
    $err = 'Please complete all fields.';
  } elseif (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) {
    $err = 'Please enter a valid email address.';
  } else {
    // Validate verification code
    $s_code = $pdo->prepare('SELECT code FROM email_verifications WHERE email=?');
    $s_code->execute([$f['email']]);
    $saved_code = $s_code->fetchColumn();
    
    if (!$saved_code || $saved_code !== $f['email_code']) {
        $err = 'Invalid or expired email verification code.';
    } else {
        $s = $pdo->prepare('SELECT id FROM users WHERE email=?'); 
        $s->execute([$f['email']]);
        if ($s->fetch()) {
          $err = 'An account with this email already exists.';
        } else {
          // Delete code after use
          $pdo->prepare('DELETE FROM email_verifications WHERE email=?')->execute([$f['email']]);
          
          $pdo->prepare('INSERT INTO users(name,email,password,role,address,phone) VALUES (?,?,?,?,?,?)')
              ->execute([$f['name'], $f['email'], password_hash($f['password'], PASSWORD_DEFAULT), 'user', $f['address'], $f['phone']]);
          
          $_SESSION['uid'] = (int)$pdo->lastInsertId();
          redirect('account.php?msg=' . urlencode('Welcome to CheapDeals.com! Please complete your card details.'));
        }
    }
  }
}
$cur = 'register.php'; $pageTitle = 'Create account';
require __DIR__ . '/includes/header.php';
?>
<h2 class="title" style="text-align:center">Create your account</h2>
<div class="panel form-box">
  <?php if ($err): ?><div class="msg err"><?= esc($err) ?></div><?php endif; ?>
  <form method="post" id="registerForm">
    <div class="fg"><label>Full name</label><input name="name" required></div>
    
    <div class="fg">
      <label>Email</label>
      <div style="display:flex; gap:8px;">
        <input name="email" id="emailField" type="email" style="flex:1;" required>
        <button type="button" class="btn small" id="sendCodeBtn" style="white-space:nowrap;">Send Code</button>
      </div>
    </div>
    
    <div class="two-col">
      <div class="fg"><label>Verification Code</label><input name="email_code" maxlength="6" placeholder="6-digit code" required></div>
      <div class="fg"><label>Password</label><input name="password" type="password" required></div>
    </div>
    
    <div class="fg"><label>Address</label><input name="address" required></div>
    <div class="fg"><label>Telephone</label><input name="phone" required></div>
    
    <button class="btn block" type="submit" style="margin-top:15px;">Register</button>
  </form>
  <p class="foot">Already have an account? <a href="login.php">Log in</a></p>
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
      
      fetch('register.php?action=send_code', {
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
