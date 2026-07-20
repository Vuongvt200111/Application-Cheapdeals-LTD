<?php
$cur = 'build.php'; $pageTitle = 'Build your own';
$pageScripts = ['build.js'];
require __DIR__ . '/../layout/header.php';
?>
<h2 class="title">Build your own package</h2>
<p class="lead">Choose the services you need, tune the limits with sliders, then submit your custom plan requirement.</p>

<?php if (!empty($_GET['msg'])): ?>
  <div class="msg ok" style="max-width:800px; margin-bottom:15px;"><?= esc($_GET['msg']) ?></div>
<?php endif; ?>

<?php if ($ME && $ME['role'] === 'user'): ?>
  <!-- Tab switching only for logged-in Customers -->
  <div class="tabs" id="buildTabs" style="margin-bottom:20px">
    <a class="active" data-tab="simple">🛠️ Simple Builder</a>
    <a data-tab="advanced">⚡ Advanced Customizer</a>
  </div>
<?php endif; ?>

<!-- 1. SIMPLE BUILDER VIEW (Original Dropdown UI) -->
<div id="simpleBuilderView" class="build-view">
  <div class="panel" style="max-width:560px; margin: 0 auto;">
    <div class="acc-head">Plan Customizer (Simple)</div>
    <div class="fg">
      <label>Device</label>
      <select id="s-device">
        <option value="0" data-label="No device">No device</option>
        <option value="8" data-label="Mobile phone">Mobile phone (+£8.00)</option>
        <option value="10" data-label="Tablet">Tablet (+£10.00)</option>
        <option value="6" data-label="Router">Router (+£6.00)</option>
      </select>
    </div>
    <div class="fg">
      <label>Free minutes</label>
      <select id="s-minutes">
        <option value="3" data-label="100 minutes">100 minutes (+£3.00)</option>
        <option value="5" data-label="500 minutes">500 minutes (+£5.00)</option>
        <option value="9" data-label="Unlimited minutes">Unlimited (+£9.00)</option>
      </select>
    </div>
    <div class="fg">
      <label>Data allowance</label>
      <select id="s-data">
        <option value="4" data-label="5 GB data">5 GB (+£4.00)</option>
        <option value="7" data-label="20 GB data">20 GB (+£7.00)</option>
        <option value="12" data-label="100 GB data">100 GB (+£12.00)</option>
      </select>
    </div>
    <div class="summary" id="s-sum" style="margin-top:15px;"></div>
    
    <?php if ($ME && $ME['role'] === 'user'): ?>
      <button class="btn block" id="btn-submit-simple" type="button" style="margin-top:15px;">Buy Now</button>
    <?php else: ?>
      <a class="btn block" href="login.php" style="margin-top:15px; text-align:center; text-decoration:none;">Log in as Customer to Submit Requirement</a>
    <?php endif; ?>
  </div>
</div>

<!-- 2. ADVANCED BUILDER VIEW (Sliders & Canvas Chart UI) -->
<div id="advancedBuilderView" class="build-view" style="<?= ($ME && $ME['role'] === 'user') ? 'display:none;' : 'display:none;' ?>">
  <div class="customizer-layout">
    <section class="panel customizer-main">
      <div class="acc-head">Plan allowances</div>
      <div class="usage-controls">
        <div class="usage-card">
          <div class="usage-label">Mobile data</div>
          <div class="usage-display" id="c-data-display">20 GB</div>
          <input type="range" class="usage-slider custom-slider" id="c-data" min="0" max="100" value="20" step="1" data-unit="GB">
          <div class="usage-bar"><span class="fill" style="--w:20%"></span></div>
        </div>

        <div class="usage-card">
          <div class="usage-label">Call minutes</div>
          <div class="usage-display" id="c-minutes-display">300 min</div>
          <input type="range" class="usage-slider custom-slider" id="c-minutes" min="0" max="1000" value="300" step="50" data-unit="min">
          <div class="usage-bar"><span class="fill" style="--w:30%"></span></div>
        </div>

        <div class="usage-card">
          <div class="usage-label">SMS messages</div>
          <div class="usage-display" id="c-sms-display">100 SMS</div>
          <input type="range" class="usage-slider custom-slider" id="c-sms" min="0" max="500" value="100" step="25" data-unit="SMS">
          <div class="usage-bar"><span class="fill" style="--w:20%"></span></div>
        </div>
      </div>

      <div class="acc-head">Optional add-ons</div>
      <div class="addon-grid">
        <label class="addon-option"><input type="checkbox" class="custom-addon" data-label="International roaming" data-price="5"> International roaming <span>+<?= gbp(5) ?></span></label>
        <label class="addon-option"><input type="checkbox" class="custom-addon" data-label="Netflix" data-price="7"> Netflix <span>+<?= gbp(7) ?></span></label>
        <label class="addon-option"><input type="checkbox" class="custom-addon" data-label="Spotify" data-price="4"> Spotify <span>+<?= gbp(4) ?></span></label>
        <label class="addon-option"><input type="checkbox" class="custom-addon" data-label="Device insurance" data-price="3"> Device insurance <span>+<?= gbp(3) ?></span></label>
      </div>

      <div class="fg device-choice">
        <label>Optional device</label>
        <select id="c-device">
          <option value="0" data-label="No device">No device</option>
          <option value="8" data-label="Mobile phone">Mobile phone (+<?= gbp(8) ?>)</option>
          <option value="10" data-label="Tablet">Tablet (+<?= gbp(10) ?>)</option>
          <option value="6" data-label="5G router">5G router (+<?= gbp(6) ?>)</option>
        </select>
      </div>
    </section>

    <aside class="panel customizer-summary">
      <div class="acc-head">Live price</div>
      <div class="summary" id="c-sum"></div>
      <div class="chart-container custom-chart">
        <canvas id="customPlanChart"></canvas>
      </div>
      <?php if ($ME && $ME['role'] === 'user'): ?>
        <button class="btn block" id="c-order" type="button">Requirement this button package</button>
      <?php else: ?>
        <a class="btn block" href="login.php" style="text-align:center; text-decoration:none;">Log in as Customer to Submit Requirement</a>
      <?php endif; ?>
    </aside>
  </div>
</div>

<!-- HIDDEN REQUIREMENT POST FORM -->
<form id="requirement-form" method="post" action="build.php" style="display:none">
  <input type="hidden" name="act" value="submit_requirement">
  <input type="hidden" name="name" id="req-name" value="Custom Package">
  <input type="hidden" name="device" id="req-device" value="">
  <input type="hidden" name="minutes" id="req-minutes" value="">
  <input type="hidden" name="data" id="req-data" value="">
  <input type="hidden" name="sms" id="req-sms" value="">
  <input type="hidden" name="addons" id="req-addons" value="">
  <input type="hidden" name="price" id="req-price" value="">
</form>

<script>
(function(){
  // Tab switching
  const tabs = document.querySelectorAll('#buildTabs a');
  if (tabs.length > 0) {
    tabs.forEach(t => t.addEventListener('click', (e) => {
      e.preventDefault();
      tabs.forEach(x => x.classList.remove('active'));
      t.classList.add('active');
      
      document.querySelectorAll('.build-view').forEach(v => v.style.display = 'none');
      const viewId = t.dataset.tab === 'simple' ? 'simpleBuilderView' : 'advancedBuilderView';
      document.getElementById(viewId).style.display = 'block';
    }));
  }

  // Simple builder calculation
  const sDevice = document.getElementById('s-device');
  const sMinutes = document.getElementById('s-minutes');
  const sData = document.getElementById('s-data');
  const sSum = document.getElementById('s-sum');
  const BASE = 10;
  const gbpVal = n => '£' + Number(n).toFixed(2);

  function calcSimple(){
    if (!sDevice || !sMinutes || !sData || !sSum) return;
    const dVal = +sDevice.value;
    const mVal = +sMinutes.value;
    const aVal = +sData.value;
    const total = BASE + dVal + mVal + aVal;

    const dLabel = sDevice.options[sDevice.selectedIndex].dataset.label;
    const mLabel = sMinutes.options[sMinutes.selectedIndex].dataset.label;
    const aLabel = sData.options[sData.selectedIndex].dataset.label;

    sSum.innerHTML = 
      `<div class="row"><span>Base subscription</span><span>${gbpVal(BASE)}</span></div>` +
      `<div class="row"><span>Device (${dLabel})</span><span>${gbpVal(dVal)}</span></div>` +
      `<div class="row"><span>Minutes (${mLabel})</span><span>${gbpVal(mVal)}</span></div>` +
      `<div class="row"><span>Data (${aLabel})</span><span>${gbpVal(aVal)}</span></div>` +
      `<div class="row total"><span>Custom price / month</span><span>${gbpVal(total)}</span></div>`;
  }

  if (sDevice) {
    [sDevice, sMinutes, sData].forEach(el => el.addEventListener('change', calcSimple));
    calcSimple();
  }

  // Submission handler
  const form = document.getElementById('requirement-form');
  const btnSimple = document.getElementById('btn-submit-simple');
  const btnAdvanced = document.getElementById('c-order');

  if (btnSimple) {
    btnSimple.addEventListener('click', () => {
      const dVal = +sDevice.value;
      const mVal = +sMinutes.value;
      const aVal = +sData.value;
      const total = BASE + dVal + mVal + aVal;
      
      const dLabel = sDevice.options[sDevice.selectedIndex].dataset.label;
      const mLabel = sMinutes.options[sMinutes.selectedIndex].dataset.label;
      const aLabel = sData.options[sData.selectedIndex].dataset.label;
      
      const name = `Custom Plan (Simple): ${dLabel}, ${mLabel}, ${aLabel}`;
      
      // Redirect directly to checkout (BUG-011)
      location.href = `checkout.php?custom=${encodeURIComponent(name)}&cprice=${total}&code=custom`;
    });
  }

  if (btnAdvanced) {
    btnAdvanced.addEventListener('click', () => {
      const dGb = document.getElementById('c-data').value;
      const callMinutes = document.getElementById('c-minutes').value;
      const smsCount = document.getElementById('c-sms').value;
      const cDevice = document.getElementById('c-device');
      
      const deviceLabel = cDevice.options[cDevice.selectedIndex].dataset.label || 'No device';
      const devicePrice = +cDevice.value;

      const addons = Array.from(document.querySelectorAll('.custom-addon:checked')).map(el => el.dataset.label).join(', ');
      const addonsPrice = Array.from(document.querySelectorAll('.custom-addon:checked')).reduce((sum, el) => sum + (+el.dataset.price), 0);

      const dataPrice = dGb * 0.18;
      const minutesPrice = callMinutes * 0.015;
      const smsPrice = smsCount * 0.01;
      const total = BASE + dataPrice + minutesPrice + smsPrice + devicePrice + addonsPrice;

      document.getElementById('req-name').value = 'Custom Plan (Advanced)';
      document.getElementById('req-device').value = deviceLabel;
      document.getElementById('req-minutes').value = callMinutes + ' minutes';
      document.getElementById('req-data').value = dGb + ' GB data';
      document.getElementById('req-sms').value = smsCount + ' SMS';
      document.getElementById('req-addons').value = addons;
      document.getElementById('req-price').value = total.toFixed(2);
      form.submit();
    });
  }
})();
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
