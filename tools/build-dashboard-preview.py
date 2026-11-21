#!/usr/bin/env python3
"""
Construit public/preview-dashboard.html : une maquette HTML statique
reproduisant fidèlement le dashboard, avec des données simulées.
Sert uniquement à la vérification visuelle (non déployé).
"""
import json

NAV = [
    ("nav-heading", "Paramétrage"),
    ("link", "Paramètres globaux", "settings-3-line"),
    ("link", "Paramètres spécifiques", "building-line"),
    ("nav-heading", "Gestion"),
    ("link", "Enregistrements", "book-read-line"),
    ("link", "Opérations", "flashlight-line"),
    ("nav-heading", "Délibération"),
    ("link", "Délibération", "verified-badge-line"),
    ("link2", "À propos", "information-line"),
]

ANNEES = ["2021-2022", "2022-2023", "2023-2024", "2024-2025", "2025-2026"]
INS = [812, 968, 1104, 1187, 1248]

FILIERES = [
    ("Licence 1", 486, 18400000),
    ("Licence 2", 342, 11200000),
    ("Licence 3", 268, 6300000),
    ("Master 1", 104, 2800000),
    ("Master 2", 48, 900000),
]
MOIS = [("sept. 25", 18500000), ("oct. 25", 32100000), ("nov. 25", 24750000),
        ("déc. 25", 15200000), ("janv. 26", 26800000), ("févr. 26", 19400000),
        ("mars 26", 12650000), ("avr. 26", 8200000)]
SEXE = [("Filles", 673), ("Garçons", 561), ("Non renseigné", 14)]
MODES = [("Espèces", 92400000), ("Mobile Money", 48600000),
         ("Virement bancaire", 28300000), ("Chèque", 5860000)]
RECOUV = [("Licence 1", 49600000, 18400000), ("Licence 2", 40800000, 11200000),
          ("Licence 3", 34700000, 6300000), ("Master 1", 21200000, 2800000),
          ("Master 2", 13100000, 900000)]
PAIEMENTS = [
    ("20/11/2026", "DOSSOU Awa", "ETU-2025-0842", "Mobile Money", 150000),
    ("20/11/2026", "HOUNKPATIN Joseph", "ETU-2025-0418", "Espèces", 200000),
    ("19/11/2026", "AKPLOGAN Mariam", "ETU-2024-1203", "Virement bancaire", 450000),
    ("19/11/2026", "ADJOVI Romuald", "ETU-2025-0977", "Mobile Money", 75000),
    ("18/11/2026", "KPADE Sylvie", "ETU-2023-0156", "Espèces", 300000),
    ("17/11/2026", "AGOSSOU Bertrand", "ETU-2025-1120", "Espèces", 125000),
    ("16/11/2026", "ZINSOU Estelle", "ETU-2024-0688", "Mobile Money", 175000),
    ("15/11/2026", "SOSSOU Pascal", "ETU-2023-1445", "Chèque", 500000),
]


def n(v):
    return f"{v:,}".replace(",", " ")


def nav_html():
    out = ['<li class="nav-item"><a class="nav-link active" href="#home" title="Accueil">'
           '<i class="ri-dashboard-line"></i><span>Accueil</span></a></li>']
    for item in NAV:
        if item[0] == "nav-heading":
            out.append(f'<li class="nav-heading">{item[1]}</li>')
        elif item[0] == "link":
            out.append(f'<li class="nav-item"><a class="nav-link collapsed" data-bs-toggle="collapse" '
                       f'data-bs-target="#nav{abs(hash(item[1])) % 9999}" href="#">'
                       f'<i class="ri-{item[2]}"></i><span>{item[1]}</span>'
                       f'<i class="ri-arrow-down-s-line ms-auto"></i></a></li>')
        else:
            out.append(f'<li class="nav-item"><a class="nav-link" href="#">'
                       f'<i class="ri-{item[2]}"></i><span>{item[1]}</span></a></li>')
    return "\n".join(out)


FILIERES_ROWS = "\n".join(
    f'''<tr>
      <td class="font-semibold text-encre">{lib}</td>
      <td class="num" data-value="{eff}">{n(eff)}</td>
      <td class="num {'num-neg' if reste else 'num-pos'}" data-value="{reste}">{n(reste)}</td>
      <td><div class="prog"><span style="width: {round(eff / 486 * 100)}%"></span></div></td>
    </tr>''' for lib, eff, reste in FILIERES)

PAIEMENTS_ROWS = "\n".join(
    f'''<tr>
      <td data-value="{d[6:10]}-{d[3:5]}-{d[0:2]}">{d}</td>
      <td class="font-semibold text-encre">{etu}</td>
      <td class="font-mono text-[0.78rem]">{mat}</td>
      <td><span class="pill pill-info">{mode}</span></td>
      <td class="num text-encre" data-value="{m}">{n(m)}</td>
    </tr>''' for d, etu, mat, mode, m in PAIEMENTS)

HTML = f'''<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>Tableau de bord — EduLMD</title>
<link href="favicon.ico" rel="icon">
<link href="/assets/css/edulmd.css" rel="stylesheet">
<link href="/assets/vendor/remixicon/remixicon.css" rel="stylesheet">
<style>
  .dash-kpi {{ position: relative; background:#fff; border:1px solid var(--ed-ligne); border-radius:1rem;
    padding:1.1rem 1.15rem; box-shadow:var(--ed-ombre); transition:box-shadow 200ms, transform 200ms;
    border-color:200ms; height:100%; overflow:hidden; }}
  .dash-kpi:hover {{ box-shadow:0 8px 24px rgba(16,24,40,.09); transform:translateY(-2px); border-color:#E9D5DA; }}
  .dash-kpi::after {{ content:""; position:absolute; right:-28px; top:-28px; width:96px; height:96px;
    border-radius:50%; background:currentColor; opacity:.045; }}
  .dash-kpi .dk-top {{ display:flex; align-items:flex-start; justify-content:space-between; gap:.6rem; }}
  .dash-kpi .dk-icon {{ width:2.4rem; height:2.4rem; border-radius:.7rem; flex:none; display:flex;
    align-items:center; justify-content:center; font-size:1.1rem; }}
  .dash-kpi .dk-value {{ font-size:1.6rem; font-weight:800; letter-spacing:-.025em; color:#0B0B0C;
    line-height:1.1; margin-top:.75rem; }}
  .dash-kpi .dk-label {{ font-size:.76rem; font-weight:600; color:#5C5F66; margin-top:.15rem; }}
  .dash-kpi .dk-sub {{ font-size:.7rem; color:#8A8D94; margin-top:.4rem; }}
  .dk-bordeaux {{ color:#822A40; }} .dk-bordeaux .dk-icon {{ background:#F9E8EB; color:#822A40; }}
  .dk-bleu {{ color:#1D38D8; }} .dk-bleu .dk-icon {{ background:#EFF4FF; color:#1D38D8; }}
  .dk-sombre {{ color:#0B0B0C; }} .dk-sombre .dk-icon {{ background:#F4F4F6; color:#0B0B0C; }}
  .dk-vert {{ color:#15803D; }} .dk-vert .dk-icon {{ background:#F0FDF4; color:#15803D; }}
  .dk-alerte {{ color:#DC2626; }} .dk-alerte .dk-icon {{ background:#FEF2F2; color:#DC2626; }}
  .chart-card {{ background:#fff; border:1px solid var(--ed-ligne); border-radius:1rem;
    box-shadow:var(--ed-ombre); height:100%; display:flex; flex-direction:column; }}
  .chart-card-head {{ display:flex; align-items:flex-start; justify-content:space-between; gap:.8rem;
    padding:1rem 1.15rem .25rem; }}
  .chart-card-title {{ font-size:.92rem; font-weight:700; color:#0B0B0C; line-height:1.3; }}
  .chart-card-sub {{ font-size:.74rem; color:#8A8D94; margin-top:.1rem; }}
  .chart-body {{ padding:.4rem .9rem 1rem; flex:1 1 auto; position:relative; min-height:0; }}
  .chart-h {{ height:260px; }}
  .seg {{ display:inline-flex; background:#F4F4F6; border-radius:.55rem; padding:2px; gap:2px; }}
  .seg button {{ border:0; background:transparent; padding:.28rem .6rem; border-radius:.42rem;
    font-size:.72rem; font-weight:600; color:#5C5F66; cursor:pointer; transition:all 140ms; }}
  .seg button.on {{ background:#fff; color:#822A40; box-shadow:0 1px 3px rgba(16,24,40,.12); }}
  .filter-bar {{ background:#fff; border:1px solid var(--ed-ligne); border-radius:1rem;
    box-shadow:var(--ed-ombre); padding:.9rem 1.1rem; }}
  .filter-bar .fs-label {{ display:block; font-size:.68rem; font-weight:700; text-transform:uppercase;
    letter-spacing:.07em; color:#8A8D94; margin-bottom:.3rem; }}
  .filter-bar .form-control, .filter-bar .form-select {{ padding:.42rem .7rem; font-size:.82rem; border-radius:.6rem; }}
  .sortable {{ cursor:pointer; user-select:none; white-space:nowrap; }}
  .sortable:hover {{ color:#822A40; }}
  .sortable .sort-ind {{ display:inline-block; margin-left:.3rem; opacity:.35; font-size:.85em; }}
  .sortable.asc .sort-ind, .sortable.desc .sort-ind {{ opacity:1; color:#822A40; }}
  .mini-table {{ width:100%; border-collapse:collapse; font-size:.82rem; }}
  .mini-table thead th {{ padding:.5rem .7rem; text-align:left; font-size:.68rem; font-weight:700;
    text-transform:uppercase; letter-spacing:.05em; color:#5C5F66; background:#FAFAFB;
    border-bottom:1px solid var(--ed-ligne); }}
  .mini-table tbody td {{ padding:.52rem .7rem; border-bottom:1px solid #F1F1F4; color:#3F4147; }}
  .mini-table tbody tr:last-child td {{ border-bottom:0; }}
  .mini-table tbody tr:hover {{ background:#FCFAFA; }}
  .mini-table .num {{ text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }}
  .mini-table .num-neg {{ color:#B91C1C; }}
  .mini-table .num-pos {{ color:#15803D; }}
  .prog {{ height:6px; background:#F0F0F3; border-radius:99px; overflow:hidden; min-width:70px; }}
  .prog > span {{ display:block; height:100%; border-radius:99px; background:#822A40; }}
  .pill {{ display:inline-flex; align-items:center; gap:.25rem; padding:.16rem .5rem;
    border-radius:99px; font-size:.68rem; font-weight:700; }}
  .pill-ok {{ background:#F0FDF4; color:#14532D; }}
  .pill-warn {{ background:#FFFBEB; color:#78350F; }}
  .pill-bad {{ background:#FEF2F2; color:#7F1D1D; }}
  .pill-info {{ background:#EFF4FF; color:#1E2F8A; }}
</style>
</head>
<body>

<header id="header" class="header">
  <i class="ri-menu-line toggle-sidebar-btn" role="button" tabindex="0" aria-label="Menu"></i>
  <div class="header-context min-w-0">
    <div>
      <div class="header-title truncate">Tableau de bord</div>
      <nav aria-label="Fil d'Ariane"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#">Accueil</a></li>
        <li class="breadcrumb-item active">Tableau de bord</li>
      </ol></nav>
    </div>
  </div>
  <nav class="header-nav"><ul><li class="nav-item dropdown">
    <a class="nav-link nav-profile flex items-center gap-2" href="#" data-bs-toggle="dropdown">
      <span class="avatar-initials">AD</span>
      <span class="profile-name hidden md:block">A. DOSSOU</span>
      <i class="ri-arrow-down-s-line hidden md:block text-[0.8rem] text-encre-faint"></i>
    </a>
    <ul class="dropdown-menu dropdown-menu-end profile">
      <li class="dropdown-header"><h6>Awa DOSSOU</h6><span class="text-muted text-[0.75rem]">awa.dossou@univ.bj</span></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="#"><i class="ri-user-line"></i><span>Mon profil</span></a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="#"><i class="ri-logout-box-line"></i><span>Se déconnecter</span></a></li>
    </ul>
  </li></ul></nav>
</header>

<aside id="sidebar" class="sidebar" aria-label="Menu de navigation">
  <div class="sidebar-brand">
    <a href="#" class="brand-logo"><img src="logo.png" alt="Logo EduLMD"></a>
    <div class="brand-text"><div class="brand-name">EduLMD</div><div class="brand-sub">LMD Web Solutions</div></div>
  </div>
  <ul class="sidebar-nav" id="sidebar-nav">
    {nav_html()}
  </ul>
  <div class="sidebar-user">
    <span class="avatar-initials">AD</span>
    <div class="su-text min-w-0"><div class="su-name">Awa DOSSOU</div><div class="su-role">Connecté(e)</div></div>
  </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<main id="main" class="main">
<section class="px-6 pb-3 pt-5">

  <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
    <div>
      <h1 class="text-[1.35rem] font-extrabold tracking-tight text-encre leading-tight">Tableau de bord</h1>
      <p class="text-[0.82rem] text-encre-muted mt-1">Vue d'ensemble de l'activité académique et financière
        — <span class="font-semibold text-bordeaux-800">2025-2026</span></p>
    </div>
    <div class="flex items-center gap-2">
      <button type="button" class="ed-btn ed-btn-secondary ed-btn-sm" id="btnExport">
        <i class="ri-download-2-line"></i>Exporter
      </button>
      <a href="#" class="ed-btn ed-btn-primary ed-btn-sm"><i class="ri-refresh-line"></i>Actualiser</a>
    </div>
  </div>

  <form class="filter-bar mb-5" onsubmit="return false">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
      <div><label class="fs-label">Année académique</label>
        <select class="form-select">{''.join(f'<option {"selected" if a == "2025-2026" else ""}>{a}</option>' for a in reversed(ANNEES))}</select></div>
      <div><label class="fs-label">Année d'étude</label>
        <select class="form-select"><option>Toutes les années d'étude</option>
        <option>Licence 1</option><option>Licence 2</option><option>Licence 3</option>
        <option>Master 1</option><option>Master 2</option></select></div>
      <div><label class="fs-label">Statut</label>
        <select class="form-select"><option>Tous les statuts</option><option>Nouveau</option>
        <option>Redoublant</option><option>Boursier</option></select></div>
      <div class="flex items-center gap-2">
        <button type="button" class="ed-btn ed-btn-primary ed-btn-sm flex-1"><i class="ri-filter-3-line"></i>Filtrer</button>
        <button type="button" class="ed-btn ed-btn-secondary ed-btn-sm"><i class="ri-refresh-line"></i></button>
      </div>
    </div>
  </form>

  <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
    <div class="dash-kpi dk-bordeaux">
      <div class="dk-top"><span class="dk-icon"><i class="ri-team-line"></i></span>
        <span class="pill pill-info">1 248 au total</span></div>
      <div class="dk-value">1 248</div><div class="dk-label">Inscriptions</div>
      <div class="dk-sub">sur le périmètre sélectionné</div>
    </div>
    <div class="dash-kpi dk-vert">
      <div class="dk-top"><span class="dk-icon"><i class="ri-hand-coin-line"></i></span>
        <span class="pill pill-ok">75.1%</span></div>
      <div class="dk-value">149 500 000</div><div class="dk-label">FCFA encaissés</div>
      <div class="dk-sub">sur 199 000 000 attendus</div>
    </div>
    <div class="dash-kpi dk-alerte">
      <div class="dk-top"><span class="dk-icon"><i class="ri-error-warning-line"></i></span>
        <span class="pill pill-bad">214 étudiant(s)</span></div>
      <div class="dk-value">49 500 000</div><div class="dk-label">FCFA restant à recouvrer</div>
      <div class="dk-sub">reste à payer cumulé</div>
    </div>
    <div class="dash-kpi dk-sombre">
      <div class="dk-top"><span class="dk-icon"><i class="ri-alarm-warning-line"></i></span>
        <span class="pill pill-warn">à relancer</span></div>
      <div class="dk-value">38</div><div class="dk-label">Sans aucun paiement</div>
      <div class="dk-sub">1 260 paiements enregistrés</div>
    </div>
  </div>

  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    <div class="dash-kpi dk-bleu"><div class="dk-top"><span class="dk-icon"><i class="ri-book-read-line"></i></span></div>
      <div class="dk-value text-[1.3rem]">142</div><div class="dk-label">Unités d'enseignement</div></div>
    <div class="dash-kpi dk-bleu"><div class="dk-top"><span class="dk-icon"><i class="ri-file-text-line"></i></span></div>
      <div class="dk-value text-[1.3rem]">386</div><div class="dk-label">ECUE</div></div>
    <div class="dash-kpi dk-sombre"><div class="dk-top"><span class="dk-icon"><i class="ri-id-card-line"></i></span></div>
      <div class="dk-value text-[1.3rem]">87</div><div class="dk-label">Enseignants</div></div>
    <div class="dash-kpi dk-bordeaux"><div class="dk-top"><span class="dk-icon"><i class="ri-bar-chart-box-line"></i></span></div>
      <div class="dk-value text-[1.3rem]">12,86</div><div class="dk-label">Moyenne générale /20</div></div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
    <div class="chart-card lg:col-span-2">
      <div class="chart-card-head"><div>
        <div class="chart-card-title">Évolution des inscriptions</div>
        <div class="chart-card-sub">Nombre d'inscriptions par année académique</div></div>
        <div class="seg"><button type="button" class="on" data-chart-mode="bar">Barres</button>
          <button type="button" data-chart-mode="line">Courbe</button></div>
      </div>
      <div class="chart-body chart-h"><canvas id="chartInscriptions"></canvas></div>
    </div>
    <div class="chart-card">
      <div class="chart-card-head"><div>
        <div class="chart-card-title">Répartition par sexe</div>
        <div class="chart-card-sub">Sur le périmètre sélectionné</div></div></div>
      <div class="chart-body chart-h"><canvas id="chartSexe"></canvas></div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
    <div class="chart-card lg:col-span-2">
      <div class="chart-card-head"><div>
        <div class="chart-card-title">Encaissements mensuels</div>
        <div class="chart-card-sub">Montants perçus mois par mois (FCFA)</div></div></div>
      <div class="chart-body chart-h"><canvas id="chartMois"></canvas></div>
    </div>
    <div class="chart-card">
      <div class="chart-card-head"><div>
        <div class="chart-card-title">Modes de paiement</div>
        <div class="chart-card-sub">Répartition des montants</div></div></div>
      <div class="chart-body chart-h"><canvas id="chartMode"></canvas></div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 mb-4">
    <div class="chart-card lg:col-span-2">
      <div class="chart-card-head"><div>
        <div class="chart-card-title">Recouvrement par année d'étude</div>
        <div class="chart-card-sub">Encaissé vs reste à payer</div></div></div>
      <div class="chart-body chart-h"><canvas id="chartRecouvrement"></canvas></div>
    </div>
    <div class="chart-card lg:col-span-3">
      <div class="chart-card-head"><div>
        <div class="chart-card-title">Effectifs et recouvrement</div>
        <div class="chart-card-sub">Cliquez sur un en-tête pour trier</div></div>
        <span class="pill pill-info">5 ligne(s)</span></div>
      <div class="chart-body p-0"><div class="overflow-x-auto">
        <table class="mini-table" id="tblFilieres" data-sortable>
          <thead><tr>
            <th class="sortable" data-sort="text" data-col="0">Année d'étude <span class="sort-ind">↕</span></th>
            <th class="sortable num" data-sort="num" data-col="1">Effectif <span class="sort-ind">↕</span></th>
            <th class="sortable num" data-sort="num" data-col="2">Reste (FCFA) <span class="sort-ind">↕</span></th>
            <th style="min-width:110px;">Part</th>
          </tr></thead>
          <tbody>{FILIERES_ROWS}</tbody>
        </table>
      </div></div>
    </div>
  </div>

  <div class="chart-card mb-2">
    <div class="chart-card-head"><div>
      <div class="chart-card-title">Derniers paiements enregistrés</div>
      <div class="chart-card-sub">15 opérations les plus récentes du périmètre</div></div>
      <a href="#" class="ed-btn ed-btn-secondary ed-btn-sm">Voir tout <i class="ri-arrow-right-line"></i></a></div>
    <div class="chart-body p-0"><div class="overflow-x-auto">
      <table class="mini-table" data-sortable>
        <thead><tr>
          <th class="sortable" data-sort="text" data-col="0">Date <span class="sort-ind">↕</span></th>
          <th class="sortable" data-sort="text" data-col="1">Étudiant <span class="sort-ind">↕</span></th>
          <th class="sortable" data-sort="text" data-col="2">Matricule <span class="sort-ind">↕</span></th>
          <th class="sortable" data-sort="text" data-col="3">Mode <span class="sort-ind">↕</span></th>
          <th class="sortable num" data-sort="num" data-col="4">Montant <span class="sort-ind">↕</span></th>
        </tr></thead>
        <tbody>{PAIEMENTS_ROWS}</tbody>
      </table>
    </div></div>
  </div>

</section>
</main>

<footer id="footer" class="footer">
  <div class="copyright">&copy; Copyright 2026 | <strong>LMD Web Solutions</strong> | Tous droits réservés.</div>
</footer>
<a href="#" class="back-to-top"><i class="ri-arrow-up-line"></i></a>

<script src="/assets/js/jquery-3.7.0.js"></script>
<script src="/assets/js/edulmd-ui.js"></script>
<script src="/assets/js/edulmd-shell.js"></script>
<script src="/assets/vendor/chart.js/chart.umd.js"></script>
<script>
(function () {{
  var C = {{ bordeaux:'#822A40', bordeauxLight:'#B55168', bordeauxSoft:'#E4ACB8',
            bleu:'#1D38D8', bleuLight:'#608CFA', noir:'#0B0B0C', gris:'#8A8D94',
            vert:'#15803D', ambre:'#D97706' }};
  var PALETTE = [C.bordeaux, C.bleu, C.bordeauxLight, C.bleuLight, C.noir, C.ambre];
  Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
  Chart.defaults.font.size = 11;
  Chart.defaults.color = '#5C5F66';
  var money = function (v) {{ return new Intl.NumberFormat('fr-FR').format(v) + ' F'; }};
  var tt = {{ backgroundColor:'#0B0B0C', padding:10, cornerRadius:8,
             titleFont:{{size:12,weight:'700'}}, bodyFont:{{size:11}} }};

  var ins = new Chart(document.getElementById('chartInscriptions'), {{
    type:'bar',
    data:{{ labels:{json.dumps(ANNEES)}, datasets:[{{ label:'Inscriptions', data:{json.dumps(INS)},
      backgroundColor:C.bordeaux, hoverBackgroundColor:'#6B2435', borderRadius:6, maxBarThickness:54 }}] }},
    options:{{ responsive:true, maintainAspectRatio:false,
      plugins:{{ legend:{{display:false}}, tooltip:Object.assign({{}}, tt, {{
        callbacks:{{ label:function(c){{ return c.parsed.y + ' inscription(s)'; }} }} }}) }},
      scales:{{ x:{{ grid:{{display:false}}, ticks:{{font:{{size:11,weight:'600'}}}} }},
                y:{{ beginAtZero:true, grid:{{color:'#EFEFF3'}}, ticks:{{precision:0}} }} }} }}
  }});

  document.querySelectorAll('[data-chart-mode]').forEach(function (b) {{
    b.addEventListener('click', function () {{
      document.querySelectorAll('[data-chart-mode]').forEach(function (x) {{ x.classList.remove('on'); }});
      b.classList.add('on');
      var line = b.getAttribute('data-chart-mode') === 'line';
      ins.config.type = line ? 'line' : 'bar';
      var ds = ins.data.datasets[0];
      ds.backgroundColor = line ? 'rgba(130,42,64,.12)' : C.bordeaux;
      ds.borderColor = C.bordeaux;
      ds.borderWidth = line ? 2.5 : 0;
      ds.pointBackgroundColor = C.bordeaux;
      ds.pointRadius = line ? 3.5 : 0;
      ds.tension = 0.35; ds.fill = line; ds.borderRadius = line ? 0 : 6;
      ins.update();
    }});
  }});

  new Chart(document.getElementById('chartSexe'), {{
    type:'doughnut',
    data:{{ labels:{json.dumps([s[0] for s in SEXE])},
      datasets:[{{ data:{json.dumps([s[1] for s in SEXE])},
        backgroundColor:[C.bordeaux, C.bleu, C.gris], borderWidth:3, borderColor:'#fff', hoverOffset:6 }}] }},
    options:{{ responsive:true, maintainAspectRatio:false, cutout:'62%',
      plugins:{{ legend:{{position:'bottom', labels:{{padding:14,usePointStyle:true,pointStyle:'circle',boxWidth:8}}}},
        tooltip:Object.assign({{}}, tt, {{ callbacks:{{ label:function(c){{
          var t = c.dataset.data.reduce(function(a,b){{return a+b;}},0);
          return ' ' + c.label + ' : ' + c.parsed + ' (' + Math.round(c.parsed/t*100) + '%)'; }} }} }}) }} }}
  }});

  new Chart(document.getElementById('chartMois'), {{
    type:'line',
    data:{{ labels:{json.dumps([m[0] for m in MOIS])},
      datasets:[{{ label:'Encaissements', data:{json.dumps([m[1] for m in MOIS])},
        borderColor:C.bleu, backgroundColor:'rgba(29,56,216,.10)', borderWidth:2.5, fill:true,
        tension:.35, pointBackgroundColor:C.bleu, pointBorderColor:'#fff', pointBorderWidth:2,
        pointRadius:3.5, pointHoverRadius:6 }}] }},
    options:{{ responsive:true, maintainAspectRatio:false,
      plugins:{{ legend:{{display:false}}, tooltip:Object.assign({{}}, tt, {{
        callbacks:{{ label:function(c){{ return money(c.parsed.y); }} }} }}) }},
      scales:{{ x:{{ grid:{{display:false}} }},
        y:{{ beginAtZero:true, grid:{{color:'#EFEFF3'}},
             ticks:{{ callback:function(v){{ return v >= 1000 ? (v/1000)+'k' : v; }} }} }} }} }}
  }});

  new Chart(document.getElementById('chartMode'), {{
    type:'doughnut',
    data:{{ labels:{json.dumps([m[0] for m in MODES])},
      datasets:[{{ data:{json.dumps([m[1] for m in MODES])}, backgroundColor:PALETTE,
        borderWidth:3, borderColor:'#fff', hoverOffset:6 }}] }},
    options:{{ responsive:true, maintainAspectRatio:false, cutout:'58%',
      plugins:{{ legend:{{position:'bottom', labels:{{padding:12,usePointStyle:true,pointStyle:'circle',
        boxWidth:8, font:{{size:10}}}}}},
        tooltip:Object.assign({{}}, tt, {{ callbacks:{{ label:function(c){{
          return ' ' + c.label + ' : ' + money(c.parsed); }} }} }}) }} }}
  }});

  new Chart(document.getElementById('chartRecouvrement'), {{
    type:'bar',
    data:{{ labels:{json.dumps([r[0] for r in RECOUV])},
      datasets:[
        {{ label:'Encaissé', data:{json.dumps([r[1] for r in RECOUV])}, backgroundColor:C.bordeaux,
           borderRadius:{{bottomLeft:6,bottomRight:6}}, maxBarThickness:42 }},
        {{ label:'Reste à payer', data:{json.dumps([r[2] for r in RECOUV])}, backgroundColor:C.bordeauxSoft,
           borderRadius:{{topLeft:6,topRight:6}}, maxBarThickness:42 }}
      ] }},
    options:{{ responsive:true, maintainAspectRatio:false,
      plugins:{{ legend:{{position:'bottom', labels:{{padding:12,usePointStyle:true,
        pointStyle:'rectRounded', boxWidth:8, font:{{size:10}}}} }},
        tooltip:Object.assign({{}}, tt, {{ callbacks:{{ label:function(c){{
          return ' ' + c.dataset.label + ' : ' + money(c.parsed.y); }} }} }}) }},
      scales:{{ x:{{ stacked:true, grid:{{display:false}}, ticks:{{font:{{size:10}}}} }},
        y:{{ stacked:true, beginAtZero:true, grid:{{color:'#EFEFF3'}},
             ticks:{{ callback:function(v){{ return v >= 1000 ? (v/1000)+'k' : v; }} }} }} }} }}
  }});

  // Tri
  document.querySelectorAll('table[data-sortable]').forEach(function (table) {{
    var tbody = table.querySelector('tbody');
    table.querySelectorAll('th.sortable').forEach(function (th) {{
      th.setAttribute('role', 'button');
      th.setAttribute('tabindex', '0');
      var sort = function () {{
        var col = parseInt(th.getAttribute('data-col'), 10);
        var isNum = th.getAttribute('data-sort') === 'num';
        var dir = th.classList.contains('asc') ? 'desc' : 'asc';
        table.querySelectorAll('th.sortable').forEach(function (h) {{
          h.classList.remove('asc', 'desc');
          var i = h.querySelector('.sort-ind'); if (i) i.textContent = '↕';
        }});
        th.classList.add(dir);
        var ind = th.querySelector('.sort-ind');
        if (ind) ind.textContent = dir === 'asc' ? '↑' : '↓';
        var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr')).filter(function (r) {{
          return !r.querySelector('td[colspan]');
        }});
        rows.sort(function (a, b) {{
          var ca = a.children[col], cb = b.children[col];
          if (!ca || !cb) return 0;
          var va, vb;
          if (isNum) {{
            va = parseFloat(ca.getAttribute('data-value') || ca.textContent.replace(/[^\\d.-]/g,'')) || 0;
            vb = parseFloat(cb.getAttribute('data-value') || cb.textContent.replace(/[^\\d.-]/g,'')) || 0;
            return dir === 'asc' ? va - vb : vb - va;
          }}
          va = (ca.getAttribute('data-value') || ca.textContent).trim().toLowerCase();
          vb = (cb.getAttribute('data-value') || cb.textContent).trim().toLowerCase();
          return dir === 'asc' ? va.localeCompare(vb,'fr') : vb.localeCompare(va,'fr');
        }});
        rows.forEach(function (r) {{ tbody.appendChild(r); }});
      }};
      th.addEventListener('click', sort);
      th.addEventListener('keydown', function (e) {{
        if (e.key === 'Enter' || e.key === ' ') {{ e.preventDefault(); sort(); }}
      }});
    }});
  }});

  // Export CSV
  var ex = document.getElementById('btnExport');
  if (ex) ex.addEventListener('click', function () {{
    var table = document.getElementById('tblFilieres');
    var rows = [["Année d'étude", 'Effectif', 'Reste à payer (FCFA)']];
    table.querySelectorAll('tbody tr').forEach(function (tr) {{
      var cells = tr.querySelectorAll('td');
      if (cells.length < 3) return;
      rows.push([cells[0].textContent.trim(), cells[1].textContent.trim(), cells[2].textContent.trim()]);
    }});
    var csv = rows.map(function (r) {{
      return r.map(function (c) {{ return '"' + String(c).replace(/"/g,'""') + '"'; }}).join(';');
    }}).join('\\r\\n');
    var blob = new Blob(['\\uFEFF' + csv], {{ type:'text/csv;charset=utf-8;' }});
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'edulmd-dashboard.csv';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
  }});
}})();
</script>
</body>
</html>
'''

open('public/preview-dashboard.html', 'w', encoding='utf-8').write(HTML)
print("preview-dashboard généré :", len(HTML), "octets")
