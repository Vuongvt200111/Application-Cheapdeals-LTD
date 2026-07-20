/* usage.js — interactive consumption sliders + responsive chart */
(function(){
  const sliders = document.querySelectorAll('.usage-slider');
  if (!sliders.length) return;

  const canvas = document.getElementById('consumptionChart');
  if (!canvas) return;
  
  const ctx = canvas.getContext('2d');
  let chart = null;

  function drawFallbackChart(percent){
    const ratio = window.devicePixelRatio || 1;
    const width = canvas.clientWidth || 320;
    const height = canvas.clientHeight || 240;
    canvas.width = width * ratio;
    canvas.height = height * ratio;
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    ctx.clearRect(0, 0, width, height);
    const labels = ['Calls', 'SMS', 'Data'];
    const colors = ['#00e5ff', '#ff2bd6', '#9d4bff'];
    const gap = 26;
    const barWidth = Math.max(42, (width - gap * 4) / 3);
    const chartTop = 24;
    const chartBottom = height - 48;
    ctx.font = '13px sans-serif';
    ctx.textAlign = 'center';
    percent.forEach((value, i) => {
      const x = gap + i * (barWidth + gap);
      const barHeight = ((chartBottom - chartTop) * Math.min(100, value)) / 100;
      const y = chartBottom - barHeight;
      ctx.fillStyle = colors[i];
      ctx.globalAlpha = 0.78;
      ctx.fillRect(x, y, barWidth, barHeight);
      ctx.globalAlpha = 1;
      ctx.fillStyle = '#cde8ff';
      ctx.fillText(value + '%', x + barWidth / 2, Math.max(14, y - 7));
      ctx.fillText(labels[i], x + barWidth / 2, height - 18);
    });
  }

  function updateChart(){
    const mins = parseFloat(document.getElementById('slider-mins').value);
    const sms = parseFloat(document.getElementById('slider-sms').value);
    const data = parseFloat(document.getElementById('slider-data').value) / 10;

    const percent = [
      Math.round((mins / 500) * 100),
      Math.round((sms / 100) * 100),
      Math.round((data / 10) * 100)
    ];

    if (chart) chart.destroy();
    if (typeof Chart === 'undefined') {
      drawFallbackChart(percent);
      return;
    }
    
    chart = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Call Minutes', 'SMS Messages', 'Mobile Data (GB)'],
        datasets: [{
          data: percent,
          backgroundColor: [
            'rgba(0, 229, 255, 0.8)',
            'rgba(255, 43, 214, 0.8)',
            'rgba(157, 75, 255, 0.8)'
          ],
          borderColor: [
            'rgba(0, 229, 255, 1)',
            'rgba(255, 43, 214, 1)',
            'rgba(157, 75, 255, 1)'
          ],
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom', labels: { color: 'rgba(205, 232, 255, 0.9)', font: { size: 13, weight: '600' } } },
          tooltip: { backgroundColor: 'rgba(10, 6, 18, 0.9)', titleColor: '#eafcff', bodyColor: '#cde8ff', borderColor: '#00e5ff', borderWidth: 1 }
        }
      }
    });
  }

  sliders.forEach(slider => {
    slider.addEventListener('input', (e) => {
      const display = e.target.parentElement.querySelector('.usage-display');
      const bar = e.target.parentElement.querySelector('.usage-bar .fill');
      const val = parseFloat(e.target.value);
      const max = parseFloat(e.target.dataset.max);
      const unit = e.target.dataset.unit;
      
      const limit = e.target.id === 'slider-data' ? 10 : (e.target.id === 'slider-mins' ? 500 : 100);
      const displayVal = e.target.id === 'slider-data' ? (val / 10).toFixed(1) : Math.round(val);
      
      display.textContent = `${displayVal} / ${limit} ${unit}`;
      bar.style.setProperty('--w', `${Math.round((val / max) * 100)}%`);
      
      updateChart();
    });
  });

  // init chart
  updateChart();
})();
