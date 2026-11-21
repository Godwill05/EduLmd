/* Interface /notes : rechargement auto des filtres + console de saisie. */
const fs = require('fs');
const { JSDOM } = require('jsdom');

/* page telle que rendue APRÈS un POST de filtres : elle contient la liste */
const html = fs.readFileSync('/tmp/notes-debug/apres-post2.html', 'utf8');
/* On charge la page entière avec runScripts:'outside-only' : jsdom ne
   l'exécute pas, mais conserve les <script> dans le DOM. Un nettoyage par
   expression régulière est à proscrire ici : un « </script> » présent dans
   une chaîne JavaScript avalerait tout le reste du document. */
const dom = new JSDOM(html, {
  runScripts: 'outside-only', pretendToBeVisual: true, url: 'http://localhost/notes'
});
const w = dom.window;
w.eval(fs.readFileSync('public/assets/js/jquery-3.7.0.js', 'utf8'));

const appels = [];
const soumissions = [];
const reponses = {
  'api/semestre-etude': [{ id_S_Gr_Spe: 135, Nom_Classe: 'LP3-GT' }],
  'api/ue/': [{ Id_Attr_ue_Sem: 521, libelleUe: 'Aménagement' }],
  'api/ecue/': [{ ecue: '1AMT1303', libelle: 'Introduction' }],
  'notes/console': {
    evaluation: { numero: 1924, ecue: '1AMT1303', libelle: 'Introduction', type: 'Examen', date: '2026-01-10', heure: '08:00', duree: 2 },
    anneeAcademique: '2026-2027', anneeEtude: 'LP-3,4-GT',
    etudiants: [
      { matricule: 'M001', nom: 'ADJOVI', prenoms: 'Aline', note: 14.5 },
      { matricule: 'M002', nom: 'BOSSOU', prenoms: 'Karl', note: null },
    ],
    statistiques: { total: 2, saisies: 1, restantes: 1, moyenne: 14.5 },
  },
  'notes/insererNotes': { ok: true, enregistrees: 1, ignorees: 0, message: '1 note(s) enregistrée(s).' },
};

w.fetch = (url, opts = {}) => {
  const propre = url.replace(/^https?:\/\/[^/]+/, '');
  appels.push({ url: propre, method: (opts.method || 'GET'), ajax: opts.headers && opts.headers['X-Requested-With'] === 'XMLHttpRequest' });
  for (const cle of Object.keys(reponses)) {
    if (propre.indexOf(cle) !== -1) {
      return Promise.resolve({ ok: true, status: 200, text: () => Promise.resolve(JSON.stringify(reponses[cle])) });
    }
  }
  return Promise.resolve({ ok: true, status: 200, text: () => Promise.resolve('{}') });
};
w.HTMLElement.prototype.scrollIntoView = function () {};

// interception de form.requestSubmit (jsdom l'implémente mais on veut l'observer)
const proto = w.HTMLFormElement.prototype;
proto.requestSubmit = function () { soumissions.push(this.id); };

// extrait et exécute le script de la page
/* jsdom garde les <script> dans le DOM : on sélectionne le bloc de la page
   (le plus gros qui contient le marqueur), pas les scripts du layout. */
const blocs = [...w.document.querySelectorAll('script:not([src])')]
  .map(s => s.textContent)
  .filter(t => t && t.length > 100);
const scriptPage = blocs.find(t => t.indexOf('consoleCorps') !== -1);
if (!scriptPage) { console.log('script de page introuvable, blocs :', blocs.map(b => b.length)); process.exit(1); }
w.eval(scriptPage);

const res = [];
const attendre = ms => new Promise(r => setTimeout(r, ms));

(async () => {
  await attendre(120);

  // 1. cascade : choisir l'année d'étude charge les semestres
  const selEtude = w.document.getElementById('anneeEtude');
  selEtude.value = 'LP-3,4-GT';
  selEtude.dispatchEvent(new w.Event('change', { bubbles: true }));
  await attendre(80);
  const selSem = w.document.getElementById('semestreEtude');
  res.push(['semestres chargés', selSem.options.length === 2 && !selSem.disabled]);

  // 2. choisir semestre + année académique charge les UE
  selSem.value = '135';
  selSem.dispatchEvent(new w.Event('change', { bubbles: true }));
  await attendre(80);
  const selUe = w.document.getElementById('ue');
  res.push(['UE chargées', selUe.options.length === 2 && !selUe.disabled]);

  // 3. choisir l'UE charge les ECUE
  selUe.value = '521';
  selUe.dispatchEvent(new w.Event('change', { bubbles: true }));
  await attendre(80);
  const selEcue = w.document.getElementById('ecue');
  res.push(['ECUE chargés', selEcue.options.length === 2 && !selEcue.disabled]);

  // 4. dès que le 4e filtre est choisi → soumission automatique (pas de clic)
  selEcue.value = '1AMT1303';
  selEcue.dispatchEvent(new w.Event('change', { bubbles: true }));
  await attendre(400);
  res.push(['rechargement automatique sans clic', soumissions.includes('formFiltresNotes')]);

  // 5. clic sur une évaluation → console chargée
  const eval1 = w.document.querySelector('.ed-eval-item');
  res.push(['liste des évaluations rendue', !!eval1]);
  if (eval1) eval1.click();
  await attendre(150);

  const champs = w.document.querySelectorAll('.ed-note-input');
  res.push(['console : 2 champs de notes', champs.length === 2]);
  res.push(['note existante pré-remplie', champs[0] && champs[0].value === '14.5']);
  res.push(['compteur "1 / 2 notes saisies"', /1 \/ 2/.test(w.document.getElementById('consoleEtat').textContent)]);
  res.push(['en-tête ECUE affiché', /Introduction/.test(w.document.getElementById('consoleCorps').textContent)]);
  res.push(['appel X-Requested-With envoyé', appels.some(a => a.ajax)]);

  // 6. saisie d'une note + enregistrement
  champs[1].value = '9.75';
  champs[1].dispatchEvent(new w.Event('input', { bubbles: true }));
  res.push(['saisie valide non signalée', !champs[1].classList.contains('is-invalid')]);

  const formNotes = w.document.getElementById('formNotes');
  formNotes.dispatchEvent(new w.Event('submit', { bubbles: true, cancelable: true }));
  await attendre(200);
  const envoi = appels.find(a => a.url.indexOf('notes/insererNotes') !== -1);
  res.push(['notes envoyées en POST', !!envoi && envoi.method === 'POST']);
  res.push(['toast de confirmation affiché', !!w.document.querySelector('.ed-toast')]);

  // 7. note hors bornes signalée
  const c = w.document.querySelectorAll('.ed-note-input')[0];
  if (c) { c.value = '25'; c.dispatchEvent(new w.Event('input', { bubbles: true })); }
  res.push(['note > 20 signalée', c && c.classList.contains('is-invalid')]);

  let ok = 0;
  for (const [nom, r] of res) { console.log((r ? '✓' : '✗'), nom); ok += r ? 1 : 0; }
  console.log(`\n${ok}/${res.length} contrôles`);
  process.exit(ok === res.length ? 0 : 1);
})();
