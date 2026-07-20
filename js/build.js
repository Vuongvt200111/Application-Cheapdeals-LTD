/* build.js - FR16 custom package sliders, live pricing, and chart redraw */
(function(){
  const data = document.getElementById('c-data');
  const minutes = document.getElementById('c-minutes');
  const sms = document.getElementById('c-sms');
  const device = document.getElementById('c-device');
  const summary = document.getElementById('c-sum');
  const order = document.getElementById('c-order');
  const canvas = document.getElementById('customPlanChart');
  if (!data || !minutes || !sms || !device || !summary || !order) return;

  const BASE = 10;
  const GBP = String.fromCharCode(163);
  const gbp = n => GBP + Number(n).toFixed(2);
  let chart = null;

  function sliderValue(input){
    return Number(input.value || 0);
  }

  function setSliderDisplay(input, value, suffix){
    const display = document.getElementById(input.id + '-display');
    const fill = input.parentElement.querySelector('.usage-bar .fill');
    if (display) display.textContent = value + ' ' + suffix;
    if (fill) fill.style.setProperty('--w', Math.round((Number(input.value) / Number(input.max)) * 100) + '%');
  }

  function selectedAddons(){
    return Array.from(document.querySelectorAll('.custom-addon:checked')).map(el => ({
      label: el.dataset.label,
      price: Number(el.dataset.price || 0)
    }));
  }

  function calculate(){
    const dataGb = sliderValue(data);
    const callMinutes = sliderValue(minutes);
    const smsCount = sliderValue(sms);
    const devicePrice = Number(device.value || 0);
    const deviceLabel = device.options[device.selectedIndex].dataset.label || 'No device';
    const addons = selectedAddons();

    const dataPrice = dataGb * 0.18;
    const minutesPrice = callMinutes * 0.015;
    const smsPrice = smsCount * 0.01;
    const addonsPrice = addons.reduce((sum, item) => sum + item.price, 0);
    const total = BASE + dataPrice + minutesPrice + smsPrice + devicePrice + addonsPrice;

    setSliderDisplay(data, dataGb, 'GB');
    setSliderDisplay(minutes, callMinutes, 'min');
    setSliderDisplay(sms, smsCount, 'SMS');

    const addonRows = addons.length
      ? addons.map(item => `<div class="row"><span>${item.label}</span><span>${gbp(item.price)}</span></div>`).join('')
      : '<div class="row"><span>Add-ons</span><span>None</span></div>';

    summary.innerHTML =
      `<div class="row"><span>Base subscription</span><span>${gbp(BASE)}</span></div>` +
      `<div class="row"><span>${dataGb} GB data</span><span>${gbp(dataPrice)}</span></div>` +
      `<div class="row"><span>${callMinutes} call minutes</span><span>${gbp(minutesPrice)}</span></div>` +
      `<div class="row"><span>${smsCount} SMS</span><span>${gbp(smsPrice)}</span></div>` +
      `<div class="row"><span>${deviceLabel}</span><span>${gbp(devicePrice)}</span></div>` +
      addonRows +
      `<div class="row total"><span>Custom price / month</span><span>${gbp(total)}</span></div>`;

    const selectedAddonNames = addons.map(item => item.label).join(', ');
    const name = [
      'Custom Plan',
      dataGb + 'GB data',
      callMinutes + ' minutes',
      smsCount + ' SMS',
      deviceLabel !== 'No device' ? deviceLabel : '',
      selectedAddonNames
    ].filter(Boolean).join(' + ');

    order.href = 'checkout.php?custom=' + encodeURIComponent(name) + '&cprice=' + encodeURIComponent(total.toFixed(2));
    redrawChart({ dataPrice, minutesPrice, smsPrice, devicePrice, addonsPrice });
  }

  function redrawFallbackBars(parts){
    if (!canvas) return;
    const ratio = window.devicePixelRatio || 1;
    const width = canvas.clientWidth || 320;
    const height = canvas.clientHeight || 220;
    canvas.width = width * ratio;
    canvas.height = height * ratio;
    const ctx = canvas.getContext('2d');
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    ctx.clearRect(0, 0, width, height);

    const labels = ['Base', 'Data', 'Calls', 'SMS', 'Device', 'Add-ons'];
    const values = [BASE, parts.dataPrice, parts.minutesPrice, parts.smsPrice, parts.devicePrice, parts.addonsPrice];
    const max = Math.max(1, ...values);
    const gap = 10;
    const chartTop = 18;
    const chartBottom = height - 42;
    const barWidth = Math.max(18, (width - gap * (values.length + 1)) / values.length);

    ctx.font = '12px sans-serif';
    ctx.textAlign = 'center';
    values.forEach((value, i) => {
      const x = gap + i * (barWidth + gap);
      const barHeight = ((chartBottom - chartTop) * value) / max;
      const y = chartBottom - barHeight;
      ctx.fillStyle = ['#00e5ff', '#ff2bd6', '#9d4bff', '#39ff14', '#ffb84d', '#60a5fa'][i];
      ctx.globalAlpha = 0.78;
      ctx.fillRect(x, y, barWidth, barHeight);
      ctx.globalAlpha = 1;
      ctx.fillStyle = '#cde8ff';
      ctx.fillText(gbp(value), x + barWidth / 2, Math.max(12, y - 5));
      ctx.fillText(labels[i], x + barWidth / 2, height - 16);
    });
  }

  function redrawChart(parts){
    if (!canvas) return;
    if (typeof Chart === 'undefined') {
      redrawFallbackBars(parts);
      return;
    }
    const ctx = canvas.getContext('2d');
    if (chart) chart.destroy();
    chart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Base', 'Data', 'Calls', 'SMS', 'Device', 'Add-ons'],
        datasets: [{
          label: 'Monthly price',
          data: [BASE, parts.dataPrice, parts.minutesPrice, parts.smsPrice, parts.devicePrice, parts.addonsPrice],
          backgroundColor: [
            'rgba(0, 229, 255, 0.70)',
            'rgba(255, 43, 214, 0.70)',
            'rgba(157, 75, 255, 0.70)',
            'rgba(57, 255, 20, 0.60)',
            'rgba(255, 184, 77, 0.70)',
            'rgba(96, 165, 250, 0.70)'
          ],
          borderColor: '#00e5ff',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: ctx => gbp(ctx.raw) } }
        },
        scales: {
          x: { ticks: { color: '#cde8ff' }, grid: { color: 'rgba(255,255,255,.08)' } },
          y: { beginAtZero: true, ticks: { color: '#cde8ff', callback: value => GBP + value }, grid: { color: 'rgba(255,255,255,.08)' } }
        }
      }
    });
  }

  [data, minutes, sms].forEach(input => input.addEventListener('input', calculate));
  device.addEventListener('change', calculate);
  document.querySelectorAll('.custom-addon').forEach(input => input.addEventListener('change', calculate));
  calculate();
})();
