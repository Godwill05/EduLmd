/* Simule le comportement de la cascade sur la page /notes rendue par Laravel. */
const fs = require('fs');
const { JSDOM } = require('jsdom');

// page déjà rendue après un POST : les data-sel-* contiennent la sélection
const html = `<!DOCTYPE html><html><head><meta name="csrf-token" content="tok"></head><body>
<form action="/notes" method="POST" id="formFiltresNotes"
      data-sel-annee-etude="LP-3,4-GT" data-sel-annee-acad="10"
      data-sel-semestre="135" data-sel-ue="521" data-sel-ecue="1AMT1303">
  <select id="anneeEtude"><option value="LP-3,4-GT">LP3,4 GT</option></select>
  <select id="anneeAcademique"><option value="10">2026-2027</option></select>
  <select name="semestreEtude" id="semestreEtude" class="semestre form-control" disabled></select>
  <select name="ue" id="ue" class="ue form-control" disabled></select>
  <select name="ecue" id="ecue" class="form-control ecue" disabled></select>
  <div id="erreurSemestre" class="d-none"></div><div id="erreurUe" class="d-none"></div><div id="erreurEcue" class="d-none"></div>
</form>
<div class="modal" id="modalNote"><form method="POST" id="insererNotes" action="/notes/insererNotes">
<input type="hidden" name="numeroEvaluation" value="1924"></form></div>
</body></html>`;

const dom = new JSDOM(html, { runScripts: 'outside-only', pretendToBeVisual: true, url: 'http://localhost/notes' });
const w = dom.window;

// jQuery minimal (le script en utilise une petite partie)
const calls = [];
function jq() {
  return {
    on: () => jq(), ready: (fn) => { if (typeof fn === 'function') setTimeout(fn, 0); return jq(); },
    modal: () => jq(), html: () => jq(), val: () => jq(),
    prop: () => jq(), text: () => jq(), attr: () => jq(),
    find: () => jq(), first: () => jq(),
  };
}
w.$ = jq;
w.$.fn = {};
w.$.ajax = () => {};

// Réponses simulées des API
const reponses = {
  '/api/semestre-etude/LP-3,4-GT': [{ id_S_Gr_Spe: 135, Nom_Classe: 'LP3-GT' }, { id_S_Gr_Spe: 136, Nom_Classe: 'LP4-GT' }],
  '/api/ue/10/135': [{ Id_Attr_ue_Sem: 521, libelleUe: 'Aménagement du Territoire' }, { Id_Attr_ue_Sem: 524, libelleUe: 'Bâtiment' }],
  '/api/ecue/521': [{ ecue: '1AMT1303', libelle: 'Introduction' }, { ecue: '1AMT1304', libelle: 'Suite' }],
};
const appels = [];
w.fetch = (url, opts) => {
  appels.push({ url, ajax: opts && opts.headers && opts.headers['X-Requested-With'] === 'XMLHttpRequest' });
  /* Un vrai serveur décode %2C avant de router : on reproduit ce décodage. */
  let decode = url;
  try { decode = decodeURIComponent(url); } catch (e) {}
  const data = reponses[url] || reponses[decode];
  return Promise.resolve({ ok: !!data, status: data ? 200 : 404, json: () => Promise.resolve(data || []) });
};
w.alert = () => {};

// extrait le script de la page notes
const page = fs.readFileSync('/home/user/EduLmd/resources/views/notes.blade.php', 'utf8');
const js = page.split("@section('js')")[1].split('<script>')[1].split('</script>')[0];
w.eval(js);

setTimeout(() => {
  const res = [];
  res.push(['3 appels API émis', appels.length === 3]);
  res.push(["en-tête X-Requested-With envoyé", appels.every(a => a.ajax)]);
  const sem = w.document.getElementById('semestreEtude');
  const ue = w.document.getElementById('ue');
  const ecue = w.document.getElementById('ecue');
  res.push(['semestre réactivé', sem.disabled === false]);
  res.push(['semestre restauré = 135', sem.value === '135']);
  res.push(['semestre a 2 options + invite', sem.options.length === 3]);
  res.push(['UE réactivée', ue.disabled === false]);
  res.push(['UE restaurée = 521', ue.value === '521']);
  res.push(['ECUE réactivé', ecue.disabled === false]);
  res.push(['ECUE restauré = 1AMT1303', ecue.value === '1AMT1303']);
  console.log('   appels :', appels.map(a => a.url).join('\n            '));
  let ok = 0;
  for (const [n, r] of res) { console.log((r ? '✓' : '✗'), n); ok += r ? 1 : 0; }
  console.log(`\n${ok}/${res.length} contrôles`);
  process.exit(ok === res.length ? 0 : 1);
}, 200);
