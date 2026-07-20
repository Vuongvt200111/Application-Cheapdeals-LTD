/* packages.js — client-side filter / sort / search on the Packages page.
   Cards are rendered by PHP with data-cat, data-tier, data-price, data-name, data-sales, data-rating, data-id. */
(function(){
  const grid = document.getElementById('plist');
  if (!grid) return;
  const cards = Array.from(grid.querySelectorAll('.card'));
  const chips = document.querySelectorAll('#chips .chip');
  const sortSel = document.getElementById('sort');
  const search = document.getElementById('search');
  let cat = 'All', q = '';

  function apply(){
    const sort = sortSel ? sortSel.value : 'price-asc';
    let visible = cards.filter(c => {
      const okCat = (cat === 'All') || c.dataset.cat === cat;
      const hay = (c.dataset.name + ' ' + c.dataset.cat + ' ' + c.dataset.tier + ' ' + c.dataset.price).toLowerCase();
      const okQ = !q || hay.includes(q);
      return okCat && okQ;
    });
    
    visible.sort((a,b) => {
      if (sort === 'price-asc') return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
      if (sort === 'price-desc') return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
      if (sort === 'name') return a.dataset.name.localeCompare(b.dataset.name);
      if (sort === 'popular') return parseInt(b.dataset.sales || 0) - parseInt(a.dataset.sales || 0);
      if (sort === 'rating') return parseFloat(b.dataset.rating || 0) - parseFloat(a.dataset.rating || 0);
      if (sort === 'newest') return parseInt(b.dataset.id || 0) - parseInt(a.dataset.id || 0);
      return 0;
    });
    
    cards.forEach(c => c.style.display = 'none');
    visible.forEach(c => { c.style.display = ''; grid.appendChild(c); });
    const empty = document.getElementById('empty');
    if (empty) empty.style.display = visible.length ? 'none' : 'block';
  }
  chips.forEach(ch => ch.addEventListener('click', () => {
    cat = ch.dataset.cat; chips.forEach(x => x.classList.toggle('active', x===ch)); apply();
  }));
  if (sortSel) sortSel.addEventListener('change', apply);
  if (search) search.addEventListener('input', e => { q = e.target.value.trim().toLowerCase(); apply(); });
  apply();
})();

/* FR7 — Package Comparison Matrix: grid/compare toggle + per-category tabs */
(function(){
  const viewTabs = document.querySelectorAll('#viewTabs a');
  if (!viewTabs.length) return;
  const gridView = document.getElementById('gridView');
  const compareView = document.getElementById('compareView');
  viewTabs.forEach(t => t.addEventListener('click', () => {
    viewTabs.forEach(x => x.classList.toggle('active', x === t));
    const v = t.dataset.view;
    if (gridView) gridView.style.display = v === 'grid' ? '' : 'none';
    if (compareView) compareView.style.display = v === 'compare' ? '' : 'none';
  }));
  const catTabs = document.querySelectorAll('#compareCatTabs a');
  const cmpTables = document.querySelectorAll('.cmp-table');
  catTabs.forEach(t => t.addEventListener('click', () => {
    catTabs.forEach(x => x.classList.toggle('active', x === t));
    cmpTables.forEach(tbl => { tbl.style.display = tbl.dataset.cmpcat === t.dataset.cmpcat ? '' : 'none'; });
  }));
})();
