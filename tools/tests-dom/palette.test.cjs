const fs = require('fs');
const { JSDOM } = require('jsdom');

const js = fs.readFileSync('public/assets/js/edulmd-raccourcis.js', 'utf8');

const html = `<!DOCTYPE html><html><head>
<meta name="csrf-token" content="x">
</head><body>
<ul id="sidebar-nav">
  <li class="nav-item"><a class="nav-link" href="/home"><i class="ri-dashboard-line"></i><span>Accueil</span></a></li>
  <li class="nav-heading">Paramétrage</li>
  <li class="nav-item">
    <a class="nav-link" data-bs-target="#c" href="#"><i class="ri-settings-3-line"></i><span>Paramètres globaux</span></a>
    <ul id="c" class="nav-content">
      <li><a href="/mentions">Mentions</a></li>
      <li><a href="/specialites">Spécialités</a></li>
      <li><a href="/grades">Grades</a></li>
      <li><a href="/semestres">Semestres</a></li>
    </ul>
  </li>
  <li class="nav-item">
    <a class="nav-link" data-bs-target="#p" href="#"><i class="ri-contacts-line"></i><span>Pédagogie</span></a>
    <ul id="p" class="nav-content">
      <li><a href="/etudiants">Étudiants</a></li>
      <li><a href="/inscriptions">Inscriptions</a></li>
      <li><a href="/notes">Gestion des notes</a></li>
      <li><a href="/paiements">Paiements</a></li>
    </ul>
  </li>
</ul>
<button id="edSearchTrigger">Rechercher</button>
</body></html>`;

const dom = new JSDOM(html, { runScripts: 'outside-only', pretendToBeVisual: true, url: 'http://localhost/home' });
const { window } = dom;
// dimensions simulées : jsdom renvoie 0 partout, on les force
Object.defineProperty(window.HTMLElement.prototype, 'offsetHeight', {
  get() { return this.classList && this.classList.contains('ed-cmdk-head') ? 64 : (this.classList && this.classList.contains('ed-cmdk-foot') ? 36 : 1000); }
});
window.HTMLElement.prototype.scrollIntoView = function () {};
window.innerHeight = 900;
window.fetch = () => Promise.resolve({ ok: true, json: () => Promise.resolve({ results: [] }) });

window.eval(js);

setTimeout(() => {
  const results = [];
  const press = (key, opts = {}) => {
    const ev = new window.KeyboardEvent('keydown', Object.assign({ key, bubbles: true, cancelable: true }, opts));
    window.document.dispatchEvent(ev);
  };

  press('k', { ctrlKey: true });
  const overlay = window.document.querySelector('.ed-cmdk-overlay');
  results.push(['palette ouverte', overlay && !overlay.hidden]);

  const panel = overlay.querySelector('.ed-cmdk-panel');
  results.push(['panneau présent', !!panel]);
  results.push(['en-tête présent', !!overlay.querySelector('.ed-cmdk-head')]);
  results.push(['champ focus', window.document.activeElement && window.document.activeElement.id === 'edCmdkInput2']);

  const liste = overlay.querySelector('#edCmdkList2');
  results.push(['liste présente', !!liste]);
  results.push(['liste a un maxHeight calculé', !!liste.style.maxHeight && parseInt(liste.style.maxHeight) > 0]);
  results.push(['liste overflowY auto', liste.style.overflowY === 'auto']);
  console.log('   maxHeight =', liste.style.maxHeight, '(fenêtre 900px)');

  const items = overlay.querySelectorAll('.ed-cmdk-item');
  results.push(['items rendus (>5)', items.length > 5]);
  console.log('   ', items.length, 'items');
  results.push(['1er item actif', items[0] && items[0].classList.contains('is-active')]);

  // navigation clavier
  // la frappe doit partir du champ focalisé, comme un vrai utilisateur
  const champ = overlay.querySelector('#edCmdkInput2');
  champ.dispatchEvent(new window.KeyboardEvent('keydown', { key: 'ArrowDown', bubbles: true, cancelable: true }));
  const actifs = overlay.querySelectorAll('.ed-cmdk-item.is-active');
  results.push(['flèche bas déplace', actifs.length === 1 && actifs[0] === overlay.querySelectorAll('.ed-cmdk-item')[1]]);

  // filtre
  const input = overlay.querySelector('#edCmdkInput2');
  input.value = 'notes';
  input.dispatchEvent(new window.Event('input', { bubbles: true }));
  const apres = [...overlay.querySelectorAll('.ed-cmdk-item')].filter(i => !i.hidden);
  results.push(['filtre réduit la liste', apres.length > 0 && apres.length < items.length]);
  results.push(['filtre trouve "Gestion des notes"', [...overlay.querySelectorAll('.ed-cmdk-title')].some(t => /notes/i.test(t.textContent))]);

  // échap ferme
  overlay.querySelector('#edCmdkInput2').dispatchEvent(new window.KeyboardEvent('keydown', { key: 'Escape', bubbles: true, cancelable: true }));
  results.push(['Échap ferme', overlay.hidden]);

  // aide ?
  press('?');
  const aide = window.document.querySelector('.ed-keys-overlay');
  results.push(['aide ? s\'ouvre', aide && !aide.hidden]);

  let ok = 0;
  for (const [nom, res] of results) { console.log((res ? '✓' : '✗'), nom); ok += res ? 1 : 0; }
  console.log(`\n${ok}/${results.length} contrôles`);
  process.exit(ok === results.length ? 0 : 1);
}, 120);
