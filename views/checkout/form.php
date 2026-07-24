<?php
$cur = 'checkout.php'; $pageTitle = 'Checkout'; $pageScripts = ['checkout.js', 'visa_gateway.js'];
require __DIR__ . '/../layout/header.php';
?>
<script>
  window.ACTIVE_CAMPAIGNS = <?= json_encode($campaigns) ?>;
  window.IS_FIRST_ORDER = <?= count($orders) === 0 ? 'true' : 'false' ?>;
  window.HAS_PAYMENT_PIN = <?= !empty($ME['payment_pin_hash']) ? 'true' : 'false' ?>;
</script>

<h2 class="title">Checkout</h2>

<?php if ($err): ?>
  <div class="msg err" style="max-width:600px; margin: 0 auto 15px; text-align:center;"><?= esc($err) ?></div>
<?php endif; ?>

<form class="panel" id="co-form" method="post" style="max-width:600px; margin:0 auto;">
  <input type="hidden" name="act" value="pay">
  <input type="hidden" name="payment_pin" id="payment-pin">
  <input type="hidden" name="f_name" id="f-name">
  <input type="hidden" name="f_total" id="f-total">
  <input type="hidden" name="f_saved" id="f-saved">
  
  <div class="fg">
    <label>Choose a package / deal / hardware</label>
    <select id="co-pkg" name="pkg_code">
      <?php foreach ($opts as $o): 
        $type_label = isset($o['features']) ? 'Package' : 'Hardware';
      ?>
        <option value="<?= esc($o['code']) ?>" data-name="<?= esc($o['name']) ?>" data-price="<?= $o['price'] ?>" data-inventory="<?= $o['inventory'] ?>" <?= $o['code']===$pre?'selected':'' ?>>
          [<?= $type_label ?>] <?= esc($o['name']) ?> - <?= gbp($o['price']) ?> (Stock: <?= $o['inventory'] ?>)
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  
  <div class="fg">
    <label>Special offer code (optional)</label>
    <input id="co-offer" name="promo_code" placeholder="e.g. SAVE10">
  </div>
  
  <!-- Loyalty Points Slider -->
  <div class="fg" style="margin-top: 15px;">
    <label>Use Loyalty Reward Points - You have: <strong id="user-points"><?= $points ?></strong> points</label>
    <div style="display:flex; align-items:center; gap:12px; margin-top:5px;">
      <input type="range" id="co-points-slider" min="0" max="<?= $points ?>" value="0" style="flex:1;">
      <span id="co-points-val" style="font-weight:bold; min-width:120px;">0 points (£0.00)</span>
    </div>
    <small class="muted" style="display:block; margin-top:2px;">1 point = £1.00 discount. Points redeemed cannot exceed your balance or the order total.</small>
    <input type="hidden" name="points_redeemed" id="f-points-redeemed" value="0">
  </div>
  
  <div class="summary" id="co-sum" style="padding:12px; background:rgba(255,255,255,0.02); border-radius:8px; margin-bottom:15px;"></div>

  <div class="acc-head" style="font-size:16px">💳 Pay by card <span class="badge">VISACheck secured</span></div>
  
  <div class="cc-wrap" style="margin-bottom:20px;">
    <div class="cc-inner" id="cc-inner">
      <div class="cc front">
        <div class="cc-top"><span class="cc-chip"></span><span class="cc-brand">VISA</span></div>
        <div class="cc-num" id="cc-num">•••• •••• •••• ••••</div>
        <div class="cc-bot">
          <div><div class="cc-lab">Card holder</div><div class="val" id="cc-name">FULL NAME</div></div>
          <div><div class="cc-lab">Expires</div><div class="val" id="cc-exp">MM/YY</div></div>
        </div>
      </div>
      <div class="cc back">
        <div class="cc-strip"></div>
        <div class="cc-cvv-row"><span class="cc-lab">CVV</span><span class="cc-cvv" id="cc-cvv-disp">•••</span></div>
        <div class="back-brand">VISA</div>
      </div>
    </div>
  </div>

  <div class="fg">
    <label>Name on card</label>
    <input id="co-cn" required value="<?= esc($ME['name']) ?>">
  </div>
  
  <div class="fg">
    <label>Card number (VISACheck checked)</label>
    <input id="co-num" name="co_num" required maxlength="19" inputmode="numeric" value="<?= esc($ME['card']) ?>" placeholder="4242 4242 4242 4242">
  </div>
  
  <div class="two-col">
    <div class="fg"><label>Expiry (MM/YY)</label><input id="co-exp" required maxlength="5" placeholder="08/27"></div>
    <div class="fg"><label>CVV</label><input id="co-cvv" name="co_cvv" required maxlength="3" inputmode="numeric" placeholder="123"></div>
  </div>
  
  <button class="btn block" type="submit">Pay now</button>
  <div class="hint">Only credit card payment is accepted. The card validation check is verified by VISACheck.</div>
</form>


<?php require __DIR__ . '/../layout/footer.php'; ?>
