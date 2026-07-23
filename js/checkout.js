/* checkout.js — card validation (expiry/CVV) + dynamic bill calculation + real-time feedback */
(function(){
  const sel = document.getElementById('co-pkg');
  if (!sel) return;
  const offer = document.getElementById('co-offer');
  const sum   = document.getElementById('co-sum');
  
  // Loyalty points elements
  const slider = document.getElementById('co-points-slider');
  const pointsVal = document.getElementById('co-points-val');
  const userPointsEl = document.getElementById('user-points');
  const fPointsRedeemed = document.getElementById('f-points-redeemed');
  const userPoints = userPointsEl ? parseInt(userPointsEl.textContent) || 0 : 0;
  
  const APP = 0.15;
  const dynCodes = {};
  const voucherState = {};
  let voucherTimer = null;
  const gbp = n => '£' + Number(n).toFixed(2);

  /* ========== BILL CALCULATION ========== */
  function calc(){
    const o = sel.options[sel.selectedIndex];
    const base = +o.dataset.price, name = o.dataset.name;
    
    // 15% discount only applies to first order (BUG-028)
    const isFirst = window.IS_FIRST_ORDER === true;
    const appD = isFirst ? (base * APP) : 0;
    
    const code = (offer.value || '').trim().toUpperCase();
    const rate = dynCodes[code] || 0;
    const offD = base * rate;
    const subtotal = Math.max(0, base - appD - offD);
    
    // Loyalty points calculation
    let pointsDiscount = 0;
    if (slider) {
      const maxRedeemable = Math.min(userPoints, Math.floor(subtotal));
      slider.max = maxRedeemable;
      const redeemed = parseInt(slider.value) || 0;
      pointsDiscount = redeemed;
      fPointsRedeemed.value = redeemed;
      pointsVal.textContent = `${redeemed} points (£${redeemed.toFixed(2)})`;
    }
    
    const total = Math.max(0, subtotal - pointsDiscount);
    
    let row = '';
    if (code) {
      const known = voucherState[code];
      if (rate) {
        row = `<div class="row"><span>Offer (${code})</span><span class="discount">- ${gbp(offD)}</span></div>`;
      } else if (known && known.pending) {
        row = `<div class="row"><span>Offer (${code})</span><span>checking...</span></div>`;
      } else if (known && known.message) {
        row = `<div class="row"><span>Offer (${code})</span><span style="color:#ff2e63">${known.message}</span></div>`;
      } else {
        row = `<div class="row"><span>Offer (${code})</span><span style="color:#ff2e63">invalid code</span></div>`;
      }
    }
    
    let pointsRow = '';
    if (pointsDiscount > 0) {
      pointsRow = `<div class="row"><span>Points discount</span><span class="discount">- ${gbp(pointsDiscount)}</span></div>`;
    }
    
    let appDiscountRow = '';
    if (isFirst) {
      appDiscountRow = `<div class="row"><span>First Order discount (15%)</span><span class="discount">- ${gbp(appD)}</span></div>`;
    }
    
    sum.innerHTML = `<div class="row"><span>${name}</span><span>${gbp(base)}</span></div>`
      + appDiscountRow
      + row + pointsRow + `<div class="row total"><span>Total to pay</span><span>${gbp(total)}</span></div>`;
    
    document.getElementById('f-total').value = total.toFixed(2);
    document.getElementById('f-name').value = name;
    document.getElementById('f-saved').value = (appD + offD + pointsDiscount).toFixed(2);
  }

  sel.addEventListener('change', calc);
  offer.addEventListener('input', () => { calc(); scheduleVoucherCheck(); });
  if (slider) {
    slider.addEventListener('input', calc);
  }
  calc();

  /* ========== VOUCHER CHECK ========== */
  async function checkDyn(){
    const code = (offer.value || '').trim().toUpperCase();
    if (!code || dynCodes[code] != null) return;
    voucherState[code] = { pending: true };
    calc();
    try {
      const r = await fetch('voucher_check.php?code=' + encodeURIComponent(code)).then(x => x.json());
      if (r && r.valid) {
        dynCodes[code] = r.discount / 100;
        voucherState[code] = { message: r.message || 'Voucher applied.' };
      } else {
        voucherState[code] = { message: (r && r.message) || 'invalid code' };
      }
    } catch (e) {
      voucherState[code] = { message: 'could not check code' };
    }
    calc();
  }
  function scheduleVoucherCheck(){
    clearTimeout(voucherTimer);
    voucherTimer = setTimeout(checkDyn, 300);
  }
  offer.addEventListener('change', checkDyn);
  offer.addEventListener('blur', checkDyn);

  /* ========== LIVE CARD PREVIEW ========== */
  const num = document.getElementById('co-num'), cn = document.getElementById('co-cn'),
        exp = document.getElementById('co-exp'), cvv = document.getElementById('co-cvv'),
        ccNum = document.getElementById('cc-num'), ccName = document.getElementById('cc-name'),
        ccExp = document.getElementById('cc-exp'), ccCvv = document.getElementById('cc-cvv-disp'),
        ccInner = document.getElementById('cc-inner');
  
  const fmt = v => v.replace(/\D/g, '').slice(0, 16).replace(/(.{4})/g, '$1 ').trim();
  const showNum = () => {
    const d = num.value.replace(/\D/g, '');
    let out = '';
    for (let i = 0; i < 16; i++) out += (i < d.length ? d[i] : '•') + ((i + 1) % 4 === 0 && i < 15 ? ' ' : '');
    ccNum.textContent = out;
  };
  
  cn.addEventListener('input', () => ccName.textContent = cn.value.trim() ? cn.value.toUpperCase() : 'FULL NAME');
  cvv.addEventListener('focus', () => ccInner.classList.add('flip'));
  cvv.addEventListener('blur', () => { ccInner.classList.remove('flip'); updateCardStatus(); });
  
  num.value = fmt(num.value);
  showNum();
  ccName.textContent = cn.value ? cn.value.toUpperCase() : 'FULL NAME';

  /* ========== CARD VALIDATION FUNCTIONS ========== */
  function validateExpiry(exp){
    const match = /^(0[1-9]|1[0-2])\/(\d{2})$/.exec((exp || '').trim());
    if (!match) return false;
    const month = parseInt(match[1], 10), year = parseInt(match[2], 10);
    const now = new Date();
    const currentYear = now.getFullYear() % 100;
    const currentMonth = now.getMonth() + 1;
    if (year < currentYear) return false;
    if (year === currentYear && month < currentMonth) return false;
    return true;
  }

  function validateCVV(cvv){
    return /^\d{3}$/.test(cvv);
  }

  function getCardStatus(){
    const num = document.getElementById('co-num').value.replace(/\D/g, '');
    const exp = document.getElementById('co-exp').value;
    const cvv = document.getElementById('co-cvv').value;
    
    const numOk = num.length >= 12;
    const expOk = validateExpiry(exp);
    const cvvOk = validateCVV(cvv);
    
    return { numOk, expOk, cvvOk, isValid: numOk && expOk && cvvOk };
  }

  function updateCardStatus(){
    const status = getCardStatus();
    const expInput = document.getElementById('co-exp');
    const cvvInput = document.getElementById('co-cvv');
    const numInput = document.getElementById('co-num');
    
    numInput.style.borderColor = status.numOk ? 'var(--brand)' : 'var(--red)';
    expInput.style.borderColor = status.expOk ? 'var(--brand)' : 'var(--red)';
    cvvInput.style.borderColor = status.cvvOk ? 'var(--brand)' : 'var(--red)';
    
    let msg = '';
    if (!status.numOk) msg = 'Card number incomplete';
    else if (!status.expOk) msg = 'Expiry date invalid or expired';
    else if (!status.cvvOk) msg = 'CVV must be 3 digits';
    else msg = '✓ Card valid';
    
    let statusEl = document.getElementById('card-status');
    if (!statusEl) {
      statusEl = document.createElement('div');
      statusEl.id = 'card-status';
      statusEl.style.cssText = 'font-size:12px;margin-top:8px;font-weight:600';
      expInput.parentElement.parentElement.appendChild(statusEl);
    }
    statusEl.textContent = msg;
    statusEl.style.color = status.isValid ? 'var(--green)' : 'var(--red)';
    
    return status;
  }
  
  // Caret-preserving Card Number and Expiry formatters (BUG-UX/Inputs)
  const handleCardInput = (e) => {
    let el = e.target;
    let cursor = el.selectionStart;
    let oldVal = el.value;
    let digits = oldVal.replace(/\D/g, '').slice(0, 16);
    
    let newVal = '';
    for (let i = 0; i < digits.length; i++) {
      if (i > 0 && i % 4 === 0) newVal += ' ';
      newVal += digits[i];
    }
    
    el.value = newVal;
    
    // Adjust cursor position
    let rawDigitsBeforeCursor = oldVal.slice(0, cursor).replace(/\D/g, '').length;
    let targetCursor = rawDigitsBeforeCursor;
    if (rawDigitsBeforeCursor > 4) targetCursor += 1;
    if (rawDigitsBeforeCursor > 8) targetCursor += 1;
    if (rawDigitsBeforeCursor > 12) targetCursor += 1;
    
    targetCursor = Math.min(targetCursor, newVal.length);
    el.setSelectionRange(targetCursor, targetCursor);
    
    showNum();
    updateCardStatus();
  };
  
  num.addEventListener('input', handleCardInput);
  
  num.addEventListener('keydown', (e) => {
    if (e.key === 'Backspace') {
      let el = e.target;
      let start = el.selectionStart;
      let end = el.selectionEnd;
      if (start === end && start > 0 && el.value[start - 1] === ' ') {
        e.preventDefault();
        let val = el.value;
        el.value = val.slice(0, start - 2) + val.slice(start);
        el.setSelectionRange(start - 2, start - 2);
        el.dispatchEvent(new Event('input'));
      }
    }
  });

  const handleExpiryInput = (e) => {
    let el = e.target;
    let cursor = el.selectionStart;
    let oldVal = el.value;
    let digits = oldVal.replace(/\D/g, '').slice(0, 4);
    
    let newVal = digits;
    if (digits.length >= 3) {
      newVal = digits.slice(0, 2) + '/' + digits.slice(2);
    }
    
    el.value = newVal;
    
    // Adjust cursor
    let targetCursor = cursor;
    let rawDigitsBeforeCursor = oldVal.slice(0, cursor).replace(/\D/g, '').length;
    if (rawDigitsBeforeCursor > 2) {
      targetCursor = rawDigitsBeforeCursor + 1;
    } else {
      targetCursor = rawDigitsBeforeCursor;
    }
    
    targetCursor = Math.min(targetCursor, newVal.length);
    el.setSelectionRange(targetCursor, targetCursor);
    
    ccExp.textContent = newVal || 'MM/YY';
    updateCardStatus();
  };
  
  exp.addEventListener('input', handleExpiryInput);
  
  exp.addEventListener('keydown', (e) => {
    if (e.key === 'Backspace') {
      let el = e.target;
      let start = el.selectionStart;
      let end = el.selectionEnd;
      if (start === end && start > 0 && el.value[start - 1] === '/') {
        e.preventDefault();
        let val = el.value;
        el.value = val.slice(0, start - 2) + val.slice(start);
        el.setSelectionRange(start - 2, start - 2);
        el.dispatchEvent(new Event('input'));
      }
    }
  });
  
  cvv.addEventListener('input', () => { ccCvv.textContent = cvv.value ? cvv.value.replace(/./g, '•') : '•••'; updateCardStatus(); });
  
  updateCardStatus();

  /* ========== PAYMENT PIN MODAL ========== */
  function showPaymentPinModal(onConfirm){
    const existing = document.getElementById('pin-modal');
    if (existing) existing.remove();

    const hasPin = window.HAS_PAYMENT_PIN;
    const title = hasPin ? 'Enter Payment PIN' : 'Create 6-Digit Payment PIN';
    const desc = hasPin 
      ? 'Enter your 6-digit payment PIN to authorise this transaction.' 
      : 'You have not set a payment PIN yet. Please create a new 6-digit PIN to secure your card for future checkouts.';
    const confirmText = hasPin ? 'Confirm & Pay' : 'Create & Pay';
    const label = hasPin ? 'Payment PIN' : 'New Payment PIN';

    const modal = document.createElement('div');
    modal.id = 'pin-modal';
    modal.className = 'pin-modal-backdrop';
    modal.innerHTML = `
      <div class="pin-modal" role="dialog" aria-labelledby="pin-modal-title">
        <h3 id="pin-modal-title">${title}</h3>
        <p class="pin-modal-desc">${desc}</p>
        <div class="fg">
          <label for="pin-modal-input">${label}</label>
          <input id="pin-modal-input" type="password" inputmode="numeric" maxlength="6" pattern="\\d{6}" placeholder="••••••" autocomplete="off">
        </div>
        ${!hasPin ? `
        <div class="fg" style="margin-top: 12px;">
          <label for="pin-modal-confirm-input">Confirm New PIN</label>
          <input id="pin-modal-confirm-input" type="password" inputmode="numeric" maxlength="6" pattern="\\d{6}" placeholder="••••••" autocomplete="off">
        </div>
        ` : ''}
        <p id="pin-modal-error" class="pin-modal-error" hidden></p>
        <div class="pin-modal-actions">
          <button type="button" class="btn sec" id="pin-modal-cancel">Cancel</button>
          <button type="button" class="btn" id="pin-modal-confirm">${confirmText}</button>
        </div>
      </div>
    `;

    document.body.appendChild(modal);
    const pinInput = document.getElementById('pin-modal-input');
    const pinError = document.getElementById('pin-modal-error');
    const confirmBtn = document.getElementById('pin-modal-confirm');
    const cancelBtn = document.getElementById('pin-modal-cancel');

    const closeModal = () => modal.remove();
    const showError = msg => {
      pinError.textContent = msg;
      pinError.hidden = false;
      pinInput.style.borderColor = 'var(--red)';
    };

    cancelBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

    pinInput.addEventListener('input', () => {
      pinInput.value = pinInput.value.replace(/\D/g, '').slice(0, 6);
      pinError.hidden = true;
      pinInput.style.borderColor = '';
    });
    
    const confirmPinInput = document.getElementById('pin-modal-confirm-input');
    if (confirmPinInput) {
      confirmPinInput.addEventListener('input', () => {
        confirmPinInput.value = confirmPinInput.value.replace(/\D/g, '').slice(0, 6);
        pinError.hidden = true;
        confirmPinInput.style.borderColor = '';
      });
    }

    const submitPin = () => {
      const pin = pinInput.value.trim();
      if (!/^\d{6}$/.test(pin)) {
        showError('Please enter a valid 6-digit PIN.');
        pinInput.focus();
        return;
      }
      
      if (!hasPin) {
        const confirmPinInput = document.getElementById('pin-modal-confirm-input');
        const confirmPin = confirmPinInput ? confirmPinInput.value.trim() : '';
        if (pin !== confirmPin) {
          showError('PINs do not match. Please verify.');
          if (confirmPinInput) confirmPinInput.focus();
          return;
        }
      }
      
      closeModal();
      onConfirm(pin);
    };

    confirmBtn.addEventListener('click', submitPin);
    pinInput.addEventListener('keydown', e => {
      if (e.key === 'Enter') { e.preventDefault(); submitPin(); }
      if (e.key === 'Escape') closeModal();
    });

    setTimeout(() => pinInput.focus(), 80);
  }

  /* ========== FORM SUBMIT WITH MODAL ========== */
  const checkoutForm = document.getElementById('co-form');
  const payButton = document.getElementById('pay-btn');
  const paymentPinField = document.getElementById('payment-pin');

  checkoutForm.addEventListener('submit', e => {
    e.preventDefault();
    const status = getCardStatus();
    
    if (!status.isValid) {
      toast('Card validation failed. Check number, expiry, and CVV.');
      return;
    }

    const cardNum = document.getElementById('co-num').value;
    const expiry = document.getElementById('co-exp').value;
    const cvv = document.getElementById('co-cvv').value;
    const amount = parseFloat(document.getElementById('f-total').value);

    const restorePayButton = () => {
      if (!payButton) return;
      payButton.disabled = false;
      payButton.textContent = 'Pay now';
    };

    const proceedWithPayment = (paymentPin) => {
      if (paymentPinField) paymentPinField.value = paymentPin;

      if (payButton) {
        payButton.disabled = true;
        payButton.textContent = 'Verifying...';
      }

      if (typeof window.showVisaGatewayModal !== 'function') {
        checkoutForm.submit();
        return;
      }

      window.showVisaGatewayModal(cardNum, expiry, cvv, amount, paymentPin,
        () => { checkoutForm.submit(); },
        (result) => {
          restorePayButton();
          if (paymentPinField) paymentPinField.value = '';
          toast('Payment declined: ' + (result.message || 'Please try again.'));
        }
      );
    };

    showPaymentPinModal(proceedWithPayment);
  });
})();


  // Auto-format Card Expiry MM/YY
  document.querySelectorAll('input[placeholder*="MM/YY"]').forEach(function(expInput){
    expInput.addEventListener('input', function(e){
      let v = e.target.value.replace(/\D/g, '');
      if (v.length >= 2) v = v.slice(0, 2) + '/' + v.slice(2, 4);
      e.target.value = v;
    });
  });
