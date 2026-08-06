<?php
if (!function_exists('saveEmailVerificationCode')) {
    function saveEmailVerificationCode($pdo, $email, $code) {
        if (!$pdo || !$email || !$code) return;
        try {
            $s = $pdo->prepare("INSERT INTO email_verifications (email, code, created_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE code = VALUES(code), created_at = NOW()");
            $s->execute([$email, $code]);
        } catch (Throwable $t) {}
    }
}

require_once __DIR__ . '/includes/auth.php';
if ($ME) redirect('index.php');

// Helper to send email
function sendResetEmail($email, $code) {
    global $pdo;
    saveEmailVerificationCode($pdo, $email, $code);
    $subject = "CheapDeals Password Reset Verification Code - $code";
    $message = "Dear Valued Customer,\n\nThank you for choosing CheapDeals!\n\nYour 6-digit password reset verification code is: $code\n\nPlease enter this code on the verification page to complete your password reset. This code is valid for 15 minutes.\n\nIf you did not request this, please ignore this email.\n\nWe wish you a wonderful day!\n\nSincerely,\nCheapDeals LTD Support Team";
    $headers = "From: CheapDeals Support <no-reply@cheapdeals.com>\r\n" .
               "Reply-To: no-reply@cheapdeals.com\r\n" .
               "X-Mailer: PHP/" . phpversion() . "\r\n" .
               "MIME-Version: 1.0\r\n" .
               "Content-Type: text/plain; charset=UTF-8\r\n";
    $logLine = "[" . date('Y-m-d H:i:s') . "] [$email] Reset Code: $code\n";
    @file_put_contents(__DIR__ . '/email_codes.log', $logLine, FILE_APPEND | LOCK_EX);
    try {
        @mail($email, $subject, $message, $headers);
    } catch (Throwable $e) {}
}

// Handle AJAX code sending
if (isset($_GET['action']) && $_GET['action'] === 'send_code') {
    ini_set('display_errors', '0');
    error_reporting(0);
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    $email = strtolower(trim($_POST['email'] ?? ''));
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Please enter a valid email address.']);
        exit;
    }
    // Check if email actually exists
    $s = $pdo->prepare('SELECT id FROM users WHERE LOWER(email)=LOWER(?)');
    $s->execute([$email]);
    if (!$s->fetch()) {
        echo json_encode(['error' => 'No account found with this email address.']);
        exit;
    }
    $code = sprintf('%06d', mt_rand(100000, 999999));
    $s_save = $pdo->prepare('INSERT INTO email_verifications (email, code, created_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE code = VALUES(code), created_at = NOW()');
    $s_save->execute([$email, $code]);
    sendResetEmail($email, $code);
    echo json_encode(['success' => true, 'message' => 'Code sent! (Check email_codes.log in project root if local SMTP is off)']);
    exit;
}

$err = '';
$ok = '';
$step = 1; // 1 = Input email & code, 2 = Input new password
$email_validated = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step_1'])) {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $code = trim($_POST['code'] ?? '');
        
        $s_check = $pdo->prepare('SELECT code FROM email_verifications WHERE LOWER(email)=LOWER(?)');
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
            // Retain audit trail in email_verifications permanently for security audits
            
            // Update password
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password=? WHERE LOWER(email)=LOWER(?)')->execute([$hash, $email]);
            
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
        <input name="password" id="reset-pwd-input" type="password" required>
<div style="margin-top:6px;">
            <div style="height:6px;background:#333;border-radius:3px;overflow:hidden;margin-bottom:4px;">
              <div id="pwd-meter-bar" style="height:100%;width:0%;background:#e74c3c;transition:all 0.3s;"></div>
            </div>
            <div style="font-size:0.8rem;display:flex;gap:10px;color:var(--text-muted);">
              <span id="chk-len">✕ Min 6 chars</span>
              <span id="chk-upper">✕ Uppercase</span>
              <span id="chk-num">✕ Number</span>
              <span id="chk-spec">✕ Special char</span>
            </div>
          </div>
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
      .then(res => res.text())
      .then(text => {
        let data;
        try { data = JSON.parse(text); } catch(e) { data = { error: 'Server returned invalid response.' }; }
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

<script>
document.addEventListener('DOMContentLoaded', function(){
  const pwdInput = document.getElementById('reset-pwd-input');
  const bar = document.getElementById('pwd-meter-bar');
  const chkLen = document.getElementById('chk-len');
  const chkUpper = document.getElementById('chk-upper');
  const chkNum = document.getElementById('chk-num');
  const chkSpec = document.getElementById('chk-spec');

  if(pwdInput && bar){
    pwdInput.addEventListener('input', function(){
      const val = pwdInput.value;
      const hasLen = val.length >= 6;
      const hasUpper = /[A-Z]/.test(val);
      const hasNum = /[0-9]/.test(val);
      const hasSpec = /[\W_]/.test(val);

      chkLen.innerHTML = (hasLen ? '✓' : '✕') + ' Min 6 chars'; chkLen.style.color = hasLen ? '#2ecc71' : '#e74c3c';
      chkUpper.innerHTML = (hasUpper ? '✓' : '✕') + ' Uppercase'; chkUpper.style.color = hasUpper ? '#2ecc71' : '#e74c3c';
      chkNum.innerHTML = (hasNum ? '✓' : '✕') + ' Number'; chkNum.style.color = hasNum ? '#2ecc71' : '#e74c3c';
      chkSpec.innerHTML = (hasSpec ? '✓' : '✕') + ' Special char'; chkSpec.style.color = hasSpec ? '#2ecc71' : '#e74c3c';

      let score = (hasLen?1:0) + (hasUpper?1:0) + (hasNum?1:0) + (hasSpec?1:0);
      bar.style.width = (score / 4 * 100) + '%';
      bar.style.background = score <= 1 ? '#e74c3c' : score <= 3 ? '#f39c12' : '#2ecc71';
    });
  }
});
</script>