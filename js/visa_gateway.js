/* visa_gateway.js — VISA gateway verification modal with real-time step display */
(function(){
  window.showVisaGatewayModal = async function(cardNum, expiry, cvv, amount, paymentPin, onSuccess, onFail){
    const existing = document.getElementById('visa-modal');
    if (existing) existing.remove();

    /* Create modal backdrop + content */
    const modal = document.createElement('div');
    modal.id = 'visa-modal';
    modal.style.cssText = `
      position:fixed;top:0;left:0;right:0;bottom:0;
      background:rgba(0,0,0,.75);z-index:999;
      display:flex;align-items:center;justify-content:center;
      padding:20px;
    `;

    const modalContent = document.createElement('div');
    modalContent.style.cssText = `
      background:var(--card);border:1px solid var(--neon);
      border-radius:16px;padding:32px;
      max-width:420px;width:100%;
      box-shadow:0 0 40px rgba(0,229,255,.35);
    `;

    modalContent.innerHTML = `
      <div style="text-align:center">
        <h2 style="color:var(--heading);margin:0 0 8px;font-family:var(--font-display)">🔐 VISA Gateway Verification</h2>
        <p style="color:var(--muted);font-size:13px;margin:0 0 24px">Processing your payment securely...</p>
        
        <div id="visa-steps" style="text-align:left;margin:20px 0">
          <!-- Steps will be injected here -->
        </div>
        
        <div id="visa-result" style="display:none;text-align:center">
          <div id="visa-result-icon" style="font-size:48px;margin-bottom:12px"></div>
          <p id="visa-result-msg" style="color:var(--heading);font-weight:600;margin:0 0 8px"></p>
          <p id="visa-result-time" style="color:var(--muted);font-size:12px;margin:0"></p>
        </div>
        
        <div id="visa-spinner" style="text-align:center;margin:20px 0">
          <div style="display:inline-block;width:40px;height:40px;border:3px solid var(--line);border-top-color:var(--brand);border-radius:50%;animation:spin .8s linear infinite"></div>
        </div>
      </div>
    `;

    const style = document.createElement('style');
    style.textContent = `
      @keyframes spin { to { transform:rotate(360deg) } }
      .visa-step{display:flex;align-items:center;gap:10px;padding:10px;margin:6px 0;border-radius:8px;background:rgba(0,229,255,.08);border-left:3px solid var(--line);transition:.3s}
      .visa-step.pass{border-left-color:var(--green);background:rgba(57,255,20,.12)}
      .visa-step.fail{border-left-color:var(--red);background:rgba(255,46,99,.12)}
      .visa-step-icon{font-size:18px;min-width:20px;text-align:center}
      .visa-step-text{flex:1;font-size:13px;font-weight:600;color:var(--ink)}
      .visa-step-time{font-size:11px;color:var(--muted)}
    `;
    document.head.appendChild(style);

    modal.appendChild(modalContent);
    document.body.appendChild(modal);

    /* Initialize steps display */
    const stepsContainer = document.getElementById('visa-steps');
    const initialSteps = ['Payment PIN Verification', 'Card Number Length', 'Luhn Check', 'Expiry Check', 'CVV Check', 'Amount Verification', 'Bank Approval'];
    
    initialSteps.forEach(step => {
      const stepEl = document.createElement('div');
      stepEl.className = 'visa-step';
      stepEl.id = `step-${step.toLowerCase().replace(/ /g, '-')}`;
      stepEl.innerHTML = `
        <span class="visa-step-icon">⏳</span>
        <span class="visa-step-text">${step}</span>
        <span class="visa-step-time">pending</span>
      `;
      stepsContainer.appendChild(stepEl);
    });

    /* Call backend gateway */
    try {
      const formData = new FormData();
      formData.append('card_num', cardNum);
      formData.append('expiry', expiry);
      formData.append('cvv', cvv);
      formData.append('amount', amount);
      formData.append('payment_pin', paymentPin);

      const response = await fetch('visa_gateway.php', { method: 'POST', body: formData });
      const result = await response.json();

      /* Update step displays */
      if (result.steps && Array.isArray(result.steps)) {
        result.steps.forEach(step => {
          const stepKey = step.step.toLowerCase().replace(/ /g, '-');
          const stepEl = document.getElementById(`step-${stepKey}`);
          if (stepEl) {
            stepEl.classList.add(step.status);
            stepEl.innerHTML = `
              <span class="visa-step-icon">${step.status === 'pass' ? '✓' : '✗'}</span>
              <span class="visa-step-text">${step.step}</span>
              <span class="visa-step-time">${step.time}ms</span>
            `;
          }
        });
      }

      /* Show result */
      document.getElementById('visa-spinner').style.display = 'none';
      document.getElementById('visa-result').style.display = 'block';
      const resultIcon = document.getElementById('visa-result-icon');
      const resultMsg = document.getElementById('visa-result-msg');
      const resultTime = document.getElementById('visa-result-time');

      if (result.success) {
        resultIcon.textContent = '✓';
        resultMsg.textContent = 'Payment Approved!';
        resultMsg.style.color = 'var(--green)';
        resultTime.textContent = `Card: ••••${result.card_last4} | ${result.total_ms}ms`;
        
        setTimeout(() => {
          modal.remove();
          if (onSuccess) onSuccess(result);
        }, 2000);
      } else {
        resultIcon.textContent = '✗';
        resultMsg.textContent = result.message || result.error || 'Payment Declined';
        resultMsg.style.color = 'var(--red)';
        resultTime.textContent = `Failed after ${result.total_ms}ms`;
        
        setTimeout(() => {
          modal.remove();
          if (onFail) onFail(result);
        }, 3000);
      }
    } catch (error) {
      console.error('Gateway error:', error);
      document.getElementById('visa-spinner').style.display = 'none';
      document.getElementById('visa-result').style.display = 'block';
      document.getElementById('visa-result-icon').textContent = '!';
      document.getElementById('visa-result-msg').textContent = 'Network Error';
      document.getElementById('visa-result-msg').style.color = 'var(--red)';
      
      setTimeout(() => {
        modal.remove();
        if (onFail) onFail({ error: 'Network error' });
      }, 2000);
    }
  };
})();
