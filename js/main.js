/* main.js — shared: theme toggle, toast, ripple, flash messages */
(function(){
  // theme: default (no attr) = Dark mode, data-theme=light = Light mode (BUG-009)
  if (localStorage.getItem('cd_theme') === 'light') {
    document.documentElement.setAttribute('data-theme', 'light');
  }
  const btn = document.getElementById('themeBtn');
  const sync = () => { if(btn) btn.textContent = document.documentElement.getAttribute('data-theme')==='light' ? '☀️' : '🌙'; };
  sync();
  if (btn) btn.addEventListener('click', () => {
    const light = document.documentElement.getAttribute('data-theme') === 'light';
    if (light) {
      document.documentElement.removeAttribute('data-theme');
      localStorage.setItem('cd_theme', 'dark');
    } else {
      document.documentElement.setAttribute('data-theme', 'light');
      localStorage.setItem('cd_theme', 'light');
    }
    sync();
  });
})();

function toast(t){
  let e = document.getElementById('toast');
  if (!e) { e = document.createElement('div'); e.id = 'toast'; document.body.appendChild(e); }
  e.textContent = t; e.classList.add('show');
  clearTimeout(window._tt); window._tt = setTimeout(() => e.classList.remove('show'), 3200);
}

/* show a flash message passed as ?msg=... */
(function(){ const m = new URLSearchParams(location.search).get('msg'); if (m) setTimeout(() => toast(m), 200); })();

/* button ripple */
document.addEventListener('click', e => {
  const b = e.target.closest('.btn'); if (!b) return;
  const r = b.getBoundingClientRect(); const d = Math.max(r.width, r.height);
  const s = document.createElement('span'); s.className = 'ripple';
  s.style.width = s.style.height = d + 'px';
  s.style.left = (e.clientX - r.left - d/2) + 'px';
  s.style.top = (e.clientY - r.top - d/2) + 'px';
  b.appendChild(s); setTimeout(() => s.remove(), 600);
});
