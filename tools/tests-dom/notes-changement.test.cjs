/* Changement d'année d'étude : les niveaux enfants doivent être remis à zéro. */
const fs = require('fs'); const { JSDOM } = require('jsdom');
const html = `<!DOCTYPE html><html><head><meta name="csrf-token" content="t"></head><body>
<form id="formFiltresNotes" action="/notes" method="POST"
 data-sel-annee-etude="LP-3,4-GT" data-sel-annee-acad="10" data-sel-semestre="135" data-sel-ue="521" data-sel-ecue="1AMT1303">
 <select id="anneeEtude"><option value="LP-3,4-GT">A</option><option value="LP1,2-GC">B</option></select>
 <select id="anneeAcademique"><option value="10">10</option></select>
 <select id="semestreEtude" disabled></select><select id="ue" disabled></select><select id="ecue" disabled></select>
 <div id="erreurSemestre" class="d-none"></div><div id="erreurUe" class="d-none"></div><div id="erreurEcue" class="d-none"></div>
</form></body></html>`;
const dom = new JSDOM(html,{runScripts:'outside-only',pretendToBeVisual:true,url:'http://localhost/notes'});
const w = dom.window;
function jq(){return{on:()=>jq(),ready:fn=>{if(typeof fn==='function')setTimeout(fn,0);return jq()},modal:()=>jq(),
 html:()=>jq(),val:()=>jq(),prop:()=>jq(),text:()=>jq(),attr:()=>jq(),find:()=>jq(),first:()=>jq()};}
w.$=jq; w.$.fn={}; w.alert=()=>{};
w.fetch=(url)=>{
  let u=url; try{u=decodeURIComponent(url)}catch(e){}
  if(u.startsWith('/api/semestre-etude/LP1,2-GC')) return Promise.resolve({ok:true,json:()=>Promise.resolve([{id_S_Gr_Spe:900,Nom_Classe:'LP1-GC'}])});
  if(u.startsWith('/api/semestre-etude/LP-3,4-GT')) return Promise.resolve({ok:true,json:()=>Promise.resolve([{id_S_Gr_Spe:135,Nom_Classe:'LP3-GT'}])});
  if(u==='/api/ue/10/135') return Promise.resolve({ok:true,json:()=>Promise.resolve([{Id_Attr_ue_Sem:521,libelleUe:'X'}])});
  if(u==='/api/ecue/521') return Promise.resolve({ok:true,json:()=>Promise.resolve([{ecue:'E1',libelle:'E1'}])});
  return Promise.resolve({ok:true,json:()=>Promise.resolve([])});
};
const page=fs.readFileSync('/home/user/EduLmd/resources/views/notes.blade.php','utf8');
w.eval(page.split("@section('js')")[1].split('<script>')[1].split('</script>')[0]);
setTimeout(()=>{
  const sem=w.document.getElementById('semestreEtude'), ue=w.document.getElementById('ue'), ecue=w.document.getElementById('ecue');
  const res=[];
  res.push(['après restauration : sem=135', sem.value==='135']);
  res.push(['après restauration : ue=521', ue.value==='521']);
  // l'utilisateur change l'année d'étude
  const ae=w.document.getElementById('anneeEtude');
  ae.value='LP1,2-GC';
  ae.dispatchEvent(new w.Event('change',{bubbles:true}));
  setTimeout(()=>{
    /* Comportement attendu : un changement d'année remet l'invite, ne
       resélectionne pas le semestre de l'ancienne année. */
    res.push(['nouveaux semestres chargés (900 présent)', [...sem.options].some(o => o.value === '900')]);
    res.push(['sélection remise sur l\'invite', sem.value === '']);
    console.log('   semestre options :', [...sem.options].map(o=>o.value+'='+o.text).join(', '));
    res.push(['UE vidée', ue.value==='']);
    res.push(['ECUE vidé', ecue.value==='']);
    res.push(['UE désactivée', ue.disabled===true]);
    res.push(['ECUE désactivé', ecue.disabled===true]);
    let ok=0; for(const [n,r] of res){console.log((r?'✓':'✗'),n); ok+=r?1:0;}
    console.log(`\n${ok}/${res.length}`);
    process.exit(ok===res.length?0:1);
  },150);
},200);
