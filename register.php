<?php
require_once __DIR__ . '/includes/auth.php';
if ($ME) redirect('index.php');

// Helper to send email (simulated & backup log for local testing)
function sendVerificationEmail($email, $code) {
    global $pdo;
    saveEmailVerificationCode($pdo, $email, $code);
    $subject = "CheapDeals Account Verification Code";
    $message = "Your 6-digit verification code is: $code";
    $headers = "From: no-reply@cheapdeals.com";
    $logPath = __DIR__ . '/email_codes.log';
    @file_put_contents($logPath, "[" . date('Y-m-d H:i:s') . "] [$email] Code: $code\n", FILE_APPEND);
    try {
        @mail($email, $subject, $message, $headers);
    } catch (Throwable $e) {}
}

// Handle AJAX code sending
if (isset($_GET['action']) && $_GET['action'] === 'send_code') {
    if (ob_get_length()) ob_clean();
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
    $s_save = $pdo->prepare('INSERT INTO email_verifications (email, code, created_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE code = VALUES(code), created_at = NOW()');
    $s_save->execute([$email, $code]);
    sendVerificationEmail($email, $code);
    echo json_encode(['success' => true, 'message' => 'Code sent! (Check email_codes.log in project root if local SMTP is off)']);
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
          // Retain audit trail in email_verifications permanently for security audits
          
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
      <div class="fg"><label>Password</label><input name="password" id="reg-password" type="password" required>
        <div id="pwd-meter" style="margin-top:6px;display:none;">
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
    </div>
    
    <div class="fg"><label>Address</label><input name="address" required></div>
    <div class="fg"><label>Telephone</label><input name="phone" type="tel" maxlength="10" pattern="[0-9]{10}" placeholder="e.g. 0912345678" oninput="this.value=this.value.replace(/\D/g,'').slice(0,10)" required></div>
    
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

<script>
document.addEventListener('DOMContentLoaded', function(){
  const pwdInput = document.getElementById('reg-password');
  const meter = document.getElementById('pwd-meter');
  const bar = document.getElementById('pwd-meter-bar');
  const chkLen = document.getElementById('chk-len');
  const chkUpper = document.getElementById('chk-upper');
  const chkNum = document.getElementById('chk-num');
  const chkSpec = document.getElementById('chk-spec');

  if(pwdInput && meter){
    pwdInput.addEventListener('input', function(){
      const val = pwdInput.value;
      if(!val){ meter.style.display = 'none'; return; }
      meter.style.display = 'block';

      const hasLen = val.length >= 6;
      const hasUpper = /[A-Z]/.test(val);
      const hasNum = /[0-9]/.test(val);
      const hasSpec = /[\W_]/.test(val);

      chkLen.innerHTML = (hasLen ? '✓' : '✕') + ' Min 6 chars'; chkLen.style.color = hasLen ? '#2ecc71' : '#e74c3c';
      chkUpper.innerHTML = (hasUpper ? '✓' : '✕') + ' Uppercase'; chkUpper.style.color = hasUpper ? '#2ecc71' : '#e74c3c';
      chkNum.innerHTML = (hasNum ? '✓' : '✕') + ' Number'; chkNum.style.color = hasNum ? '#2ecc71' : '#e74c3c';
      chkSpec.innerHTML = (hasSpec ? '✓' : '✕') + ' Special char'; chkSpec.style.color = hasSpec ? '#2ecc71' : '#e74c3c';

      let score = (hasLen?1:0) + (hasUpper?1:0) + (hasNum?1:0) + (hasSpec?1:0);
      let pct = (score / 4) * 100;
      bar.style.width = pct + '%';
      bar.style.background = score <= 1 ? '#e74c3c' : score <= 3 ? '#f39c12' : '#2ecc71';
    });
  }

  // 60s OTP Cooldown
  const btnSend = document.getElementById('btn-send-code');
  if(btnSend){
    btnSend.addEventListener('click', function(){
      let cd = 60;
      btnSend.disabled = true;
      const origText = btnSend.innerText;
      const timer = setInterval(function(){
        cd--;
        btnSend.innerText = 'Resend in ' + cd + 's';
        if(cd <= 0){
          clearInterval(timer);
          btnSend.disabled = false;
          btnSend.innerText = origText;
        }
      }, 1000);
    });
  }
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
