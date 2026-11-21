@extends('layout')
@section('title')
Tableau de bord
@endsection

@section('css')
<style>
  /* ── Cartes KPI enrichies ───────────────────────────────────────── */
  .dash-kpi {
    position: relative; background: #fff; border: 1px solid var(--ed-ligne);
    border-radius: 1rem; padding: 1.1rem 1.15rem; box-shadow: var(--ed-ombre);
    transition: box-shadow 200ms, transform 200ms, border-color 200ms; height: 100%;
    overflow: hidden;
  }
  .dash-kpi:hover { box-shadow: 0 8px 24px rgba(16,24,40,.09); transform: translateY(-2px); border-color: #E9D5DA; }
  .dash-kpi::after {
    content: ""; position: absolute; right: -28px; top: -28px;
    width: 96px; height: 96px; border-radius: 50%; background: currentColor; opacity: .045;
  }
  .dash-kpi .dk-top { display: flex; align-items: flex-start; justify-content: space-between; gap: .6rem; }
  .dash-kpi .dk-icon {
    width: 2.4rem; height: 2.4rem; border-radius: .7rem; flex: none;
    display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
  }
  .dash-kpi .dk-value {
    font-size: 1.6rem; font-weight: 800; letter-spacing: -.025em;
    color: #0B0B0C; line-height: 1.1; margin-top: .75rem;
  }
  .dash-kpi .dk-label { font-size: .76rem; font-weight: 600; color: #5C5F66; margin-top: .15rem; }
  .dash-kpi .dk-sub { font-size: .7rem; color: #8A8D94; margin-top: .4rem; }
  .dk-bordeaux { color: #822A40; } .dk-bordeaux .dk-icon { background: #F9E8EB; color: #822A40; }
  .dk-bleu { color: #1D38D8; } .dk-bleu .dk-icon { background: #EFF4FF; color: #1D38D8; }
  .dk-sombre { color: #0B0B0C; } .dk-sombre .dk-icon { background: #F4F4F6; color: #0B0B0C; }
  .dk-vert { color: #15803D; } .dk-vert .dk-icon { background: #F0FDF4; color: #15803D; }
  .dk-alerte { color: #DC2626; } .dk-alerte .dk-icon { background: #FEF2F2; color: #DC2626; }

  /* ── Cartes graphiques ──────────────────────────────────────────── */
  .chart-card {
    background: #fff; border: 1px solid var(--ed-ligne); border-radius: 1rem;
    box-shadow: var(--ed-ombre); height: 100%; display: flex; flex-direction: column;
  }
  .chart-card-head {
    display: flex; align-items: flex-start; justify-content: space-between; gap: .8rem;
    padding: 1rem 1.15rem .25rem;
  }
  .chart-card-title { font-size: .92rem; font-weight: 700; color: #0B0B0C; line-height: 1.3; }
  .chart-card-sub { font-size: .74rem; color: #8A8D94; margin-top: .1rem; }
  .chart-body { padding: .4rem .9rem 1rem; flex: 1 1 auto; position: relative; min-height: 0; }
  .chart-body canvas { max-height: 100%; }
  .chart-h { height: 260px; }
  .chart-h-sm { height: 210px; }

  /* Segments de période (toggle jour/mois) */
  .seg {
    display: inline-flex; background: #F4F4F6; border-radius: .55rem; padding: 2px; gap: 2px;
  }
  .seg button {
    border: 0; background: transparent; padding: .28rem .6rem; border-radius: .42rem;
    font-size: .72rem; font-weight: 600; color: #5C5F66; cursor: pointer; transition: all 140ms;
  }
  .seg button.on { background: #fff; color: #822A40; box-shadow: 0 1px 3px rgba(16,24,40,.12); }

  /* ── Barre de filtres ───────────────────────────────────────────── */
  .filter-bar {
    background: #fff; border: 1px solid var(--ed-ligne); border-radius: 1rem;
    box-shadow: var(--ed-ombre); padding: .9rem 1.1rem;
  }
  .filter-bar .fs-label {
    display: block; font-size: .68rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .07em; color: #8A8D94; margin-bottom: .3rem;
  }
  .filter-bar .form-control, .filter-bar .form-select {
    padding: .42rem .7rem; font-size: .82rem; border-radius: .6rem;
  }

  /* ── Tableaux triables ──────────────────────────────────────────── */
  .sortable { cursor: pointer; user-select: none; white-space: nowrap; }
  .sortable:hover { color: #822A40; }
  .sortable .sort-ind { display: inline-block; margin-left: .3rem; opacity: .35; font-size: .85em; }
  .sortable.asc .sort-ind, .sortable.desc .sort-ind { opacity: 1; color: #822A40; }
  .mini-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
  .mini-table thead th {
    padding: .5rem .7rem; text-align: left; font-size: .68rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .05em; color: #5C5F66;
    background: #FAFAFB; border-bottom: 1px solid var(--ed-ligne);
  }
  .mini-table tbody td { padding: .52rem .7rem; border-bottom: 1px solid #F1F1F4; color: #3F4147; }
  .mini-table tbody tr:last-child td { border-bottom: 0; }
  .mini-table tbody tr:hover { background: #FCFAFA; }
  .mini-table .num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; }
  .mini-table .num-neg { color: #B91C1C; }
  .mini-table .num-pos { color: #15803D; }

  .prog { height: 6px; background: #F0F0F3; border-radius: 99px; overflow: hidden; min-width: 70px; }
  .prog > span { display: block; height: 100%; border-radius: 99px; background: #822A40; transition: width 400ms cubic-bezier(.22,1,.36,1); }
  .prog.ok > span { background: #15803D; }
  .prog.warn > span { background: #D97706; }
  .prog.bad > span { background: #DC2626; }

  .pill { display: inline-flex; align-items: center; gap: .25rem; padding: .16rem .5rem; border-radius: 99px; font-size: .68rem; font-weight: 700; }
  .pill-ok { background: #F0FDF4; color: #14532D; }
  .pill-warn { background: #FFFBEB; color: #78350F; }
  .pill-bad { background: #FEF2F2; color: #7F1D1D; }
  .pill-info { background: #EFF4FF; color: #1E2F8A; }

  @media (max-width: 575.98px) {
    .dash-kpi .dk-value { font-size: 1.4rem; }
    .chart-h { height: 220px; }
  }
  @media (prefers-reduced-motion: reduce) {
    .dash-kpi, .prog > span { transition: none !important; }
  }
</style>
@endsection

@section('content')
<section class="px-6 pb-3 pt-5">

  {{-- ═══════════ En-tête + filtres ═══════════ --}}
  <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
    <div>
      <h1 class="text-[1.35rem] font-extrabold tracking-tight text-encre leading-tight">Tableau de bord</h1>
      <p class="text-[0.82rem] text-encre-muted mt-1">
        Vue d'ensemble de l'activité académique et financière
        @if($anneeAcad)
          — <span class="font-semibold text-bordeaux-800">
            {{ optional($annees->firstWhere('id_Annee_Acad', $anneeAcad))->ANNEE_ACAD }}
          </span>
        @endif
      </p>
    </div>
    <div class="flex items-center gap-2">
      <button type="button" class="ed-btn ed-btn-secondary ed-btn-sm" id="btnExport" title="Exporter le tableau en CSV">
        <i class="ri-download-2-line"></i>Exporter
      </button>
      <a href="{{ route('home') }}" class="ed-btn ed-btn-primary ed-btn-sm">
        <i class="ri-refresh-line"></i>Actualiser
      </a>
    </div>
  </div>

  {{-- Barre de filtres --}}
  <form method="GET" action="{{ route('home') }}" class="filter-bar mb-5" id="dashFilters">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
      <div>
        <label for="fAnnee" class="fs-label">Année académique</label>
        <select name="annee" id="fAnnee" class="form-select">
          <option value="">Toutes les années</option>
          @foreach($annees as $a)
            <option value="{{ $a->id_Annee_Acad }}" @selected($anneeAcad == $a->id_Annee_Acad)>
              {{ $a->ANNEE_ACAD }}
            </option>
          @endforeach
        </select>
      </div>
      <div>
        <label for="fAnneeEtude" class="fs-label">Année d'étude</label>
        <select name="annee_etude" id="fAnneeEtude" class="form-select">
          <option value="">Toutes les années d'étude</option>
          @foreach(\App\Models\AnneeEtude::orderBy('Code_Annee_Etude')->get() as $ae)
            <option value="{{ $ae->Code_Annee_Etude }}" @selected($anneeEtude == $ae->Code_Annee_Etude)>
              {{ $ae->Libelle_AnEtude }}
            </option>
          @endforeach
        </select>
      </div>
      <div>
        <label for="fStatut" class="fs-label">Statut</label>
        <select name="statut" id="fStatut" class="form-select">
          <option value="">Tous les statuts</option>
          @foreach(\App\Models\Statut::orderBy('codeStatut')->get() as $st)
            <option value="{{ $st->codeStatut }}" @selected($statut == $st->codeStatut)>
              {{ $st->LibStatut ?? $st->codeStatut }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="flex items-center gap-2">
        <button type="submit" class="ed-btn ed-btn-primary ed-btn-sm flex-1">
          <i class="ri-filter-3-line"></i>Filtrer
        </button>
        <a href="{{ route('home') }}" class="ed-btn ed-btn-secondary ed-btn-sm" title="Réinitialiser les filtres">
          <i class="ri-refresh-line"></i>
        </a>
      </div>
    </div>
  </form>

  {{-- ═══════════ KPI ═══════════ --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-5">

    <div class="dash-kpi dk-bordeaux">
      <div class="dk-top">
        <span class="dk-icon"><i class="ri-team-line"></i></span>
        <span class="pill pill-info">{{ number_format($nbEtudiantsCatalogue, 0, ',', ' ') }} au total</span>
      </div>
      <div class="dk-value">{{ number_format($nbInscrits, 0, ',', ' ') }}</div>
      <div class="dk-label">Inscriptions</div>
      <div class="dk-sub">sur le périmètre sélectionné</div>
    </div>

    <div class="dash-kpi dk-vert">
      <div class="dk-top">
        <span class="dk-icon"><i class="ri-hand-coin-line"></i></span>
        <span class="pill {{ $tauxRecouvrement >= 75 ? 'pill-ok' : ($tauxRecouvrement >= 40 ? 'pill-warn' : 'pill-bad') }}">
          {{ $tauxRecouvrement }}%
        </span>
      </div>
      <div class="dk-value">{{ number_format($encaisse, 0, ',', ' ') }}</div>
      <div class="dk-label">FCFA encaissés</div>
      <div class="dk-sub">sur {{ number_format($attendu, 0, ',', ' ') }} attendus</div>
    </div>

    <div class="dash-kpi dk-alerte">
      <div class="dk-top">
        <span class="dk-icon"><i class="ri-error-warning-line"></i></span>
        <span class="pill {{ $nbEnRetard ? 'pill-bad' : 'pill-ok' }}">{{ $nbEnRetard }} étudiant(s)</span>
      </div>
      <div class="dk-value">{{ number_format($reste, 0, ',', ' ') }}</div>
      <div class="dk-label">FCFA restant à recouvrer</div>
      <div class="dk-sub">reste à payer cumulé</div>
    </div>

    <div class="dash-kpi dk-sombre">
      <div class="dk-top">
        <span class="dk-icon"><i class="ri-alarm-warning-line"></i></span>
        <span class="pill {{ $nbSansPaiement ? 'pill-warn' : 'pill-ok' }}">à relancer</span>
      </div>
      <div class="dk-value">{{ number_format($nbSansPaiement, 0, ',', ' ') }}</div>
      <div class="dk-label">Sans aucun paiement</div>
      <div class="dk-sub">{{ number_format($nbPaiements, 0, ',', ' ') }} paiements enregistrés</div>
    </div>
  </div>

  {{-- Indicateurs secondaires --}}
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    <div class="dash-kpi dk-bleu">
      <div class="dk-top"><span class="dk-icon"><i class="ri-book-read-line"></i></span></div>
      <div class="dk-value text-[1.3rem]">{{ number_format($nbUe, 0, ',', ' ') }}</div>
      <div class="dk-label">Unités d'enseignement</div>
    </div>
    <div class="dash-kpi dk-bleu">
      <div class="dk-top"><span class="dk-icon"><i class="ri-file-text-line"></i></span></div>
      <div class="dk-value text-[1.3rem]">{{ number_format($nbEcue, 0, ',', ' ') }}</div>
      <div class="dk-label">ECUE</div>
    </div>
    <div class="dash-kpi dk-sombre">
      <div class="dk-top"><span class="dk-icon"><i class="ri-id-card-line"></i></span></div>
      <div class="dk-value text-[1.3rem]">{{ number_format($nbEnseignants, 0, ',', ' ') }}</div>
      <div class="dk-label">Enseignants</div>
    </div>
    <div class="dash-kpi dk-bordeaux">
      <div class="dk-top"><span class="dk-icon"><i class="ri-bar-chart-box-line"></i></span></div>
      <div class="dk-value text-[1.3rem]">
        @if($moyenne !== null){{ number_format($moyenne, 2, ',', ' ') }}@else — @endif
      </div>
      <div class="dk-label">Moyenne générale /20</div>
    </div>
  </div>

  {{-- ═══════════ Graphiques — ligne 1 ═══════════ --}}
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

    {{-- Histogramme : inscriptions par année académique --}}
    <div class="chart-card lg:col-span-2">
      <div class="chart-card-head">
        <div>
          <div class="chart-card-title">Évolution des inscriptions</div>
          <div class="chart-card-sub">Nombre d'inscriptions par année académique</div>
        </div>
        <div class="seg" role="group" aria-label="Type de graphique">
          <button type="button" class="on" data-chart-mode="bar">Barres</button>
          <button type="button" data-chart-mode="line">Courbe</button>
        </div>
      </div>
      <div class="chart-body chart-h">
        <canvas id="chartInscriptions" role="img" aria-label="Histogramme des inscriptions par année académique"></canvas>
      </div>
    </div>

    {{-- Camembert : répartition par sexe --}}
    <div class="chart-card">
      <div class="chart-card-head">
        <div>
          <div class="chart-card-title">Répartition par sexe</div>
          <div class="chart-card-sub">Sur le périmètre sélectionné</div>
        </div>
      </div>
      <div class="chart-body chart-h">
        <canvas id="chartSexe" role="img" aria-label="Diagramme circulaire de la répartition par sexe"></canvas>
      </div>
    </div>
  </div>

  {{-- ═══════════ Graphiques — ligne 2 ═══════════ --}}
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

    {{-- Courbe : encaissements mensuels --}}
    <div class="chart-card lg:col-span-2">
      <div class="chart-card-head">
        <div>
          <div class="chart-card-title">Encaissements mensuels</div>
          <div class="chart-card-sub">Montants perçus mois par mois (FCFA)</div>
        </div>
      </div>
      <div class="chart-body chart-h">
        <canvas id="chartMois" role="img" aria-label="Courbe des encaissements mensuels"></canvas>
      </div>
    </div>

    {{-- Anneau : modes de paiement --}}
    <div class="chart-card">
      <div class="chart-card-head">
        <div>
          <div class="chart-card-title">Modes de paiement</div>
          <div class="chart-card-sub">Répartition des montants</div>
        </div>
      </div>
      <div class="chart-body chart-h">
        <canvas id="chartMode" role="img" aria-label="Diagramme en anneau des modes de paiement"></canvas>
      </div>
    </div>
  </div>

  {{-- ═══════════ Graphique recouvrement + tableau classes ═══════════ --}}
  <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 mb-4">

    <div class="chart-card lg:col-span-2">
      <div class="chart-card-head">
        <div>
          <div class="chart-card-title">Recouvrement par année d'étude</div>
          <div class="chart-card-sub">Encaissé vs reste à payer</div>
        </div>
      </div>
      <div class="chart-body chart-h">
        <canvas id="chartRecouvrement" role="img" aria-label="Barres empilées du recouvrement par année d'étude"></canvas>
      </div>
    </div>

    {{-- Tableau triable : effectifs et recouvrement par année d'étude --}}
    <div class="chart-card lg:col-span-3">
      <div class="chart-card-head">
        <div>
          <div class="chart-card-title">Effectifs et recouvrement</div>
          <div class="chart-card-sub">Cliquez sur un en-tête pour trier</div>
        </div>
        <span class="pill pill-info" id="tblCount">{{ $tableFilieres->count() }} ligne(s)</span>
      </div>
      <div class="chart-body p-0">
        <div class="overflow-x-auto">
          <table class="mini-table" id="tblFilieres" data-sortable>
            <thead>
              <tr>
                <th class="sortable" data-sort="text" data-col="0">Année d'étude <span class="sort-ind">↕</span></th>
                <th class="sortable num" data-sort="num" data-col="1">Effectif <span class="sort-ind">↕</span></th>
                <th class="sortable num" data-sort="num" data-col="2">Reste (FCFA) <span class="sort-ind">↕</span></th>
                <th style="min-width:110px;">Part</th>
              </tr>
            </thead>
            <tbody>
              @php $maxEff = max(1, $tableFilieres->max('effectif')); @endphp
              @forelse($tableFilieres as $f)
                <tr>
                  <td class="font-semibold text-encre">{{ $f->label }}</td>
                  <td class="num" data-value="{{ $f->effectif }}">{{ number_format($f->effectif, 0, ',', ' ') }}</td>
                  <td class="num {{ $f->reste > 0 ? 'num-neg' : 'num-pos' }}" data-value="{{ $f->reste }}">
                    {{ number_format($f->reste, 0, ',', ' ') }}
                  </td>
                  <td>
                    <div class="prog {{ $f->reste == 0 ? 'ok' : ($f->reste > 0 && $f->reste > $maxEff ? 'bad' : '') }}">
                      <span style="width: {{ $f->effectif > 0 ? round($f->effectif / $maxEff * 100) : 0 }}%"></span>
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-center text-encre-faint py-6">Aucune donnée pour ce filtre.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  {{-- ═══════════ Derniers paiements ═══════════ --}}
  <div class="chart-card mb-2">
    <div class="chart-card-head">
      <div>
        <div class="chart-card-title">Derniers paiements enregistrés</div>
        <div class="chart-card-sub">15 opérations les plus récentes du périmètre</div>
      </div>
      <a href="{{ route('paiements.index') }}" class="ed-btn ed-btn-secondary ed-btn-sm">
        Voir tout <i class="ri-arrow-right-line"></i>
      </a>
    </div>
    <div class="chart-body p-0">
      <div class="overflow-x-auto">
        <table class="mini-table" data-sortable>
          <thead>
            <tr>
              <th class="sortable" data-sort="text" data-col="0">Date <span class="sort-ind">↕</span></th>
              <th class="sortable" data-sort="text" data-col="1">Étudiant <span class="sort-ind">↕</span></th>
              <th class="sortable" data-sort="text" data-col="2">Matricule <span class="sort-ind">↕</span></th>
              <th class="sortable" data-sort="text" data-col="3">Mode <span class="sort-ind">↕</span></th>
              <th class="sortable num" data-sort="num" data-col="4">Montant <span class="sort-ind">↕</span></th>
            </tr>
          </thead>
          <tbody>
            @forelse($derniersPaiements as $p)
              <tr>
                <td data-value="{{ $p->datePaiement }}">
                  {{ $p->datePaiement ? \Carbon\Carbon::parse($p->datePaiement)->format('d/m/Y') : '—' }}
                </td>
                <td class="font-semibold text-encre">
                  {{ $p->nomEtu ?? '—' }} {{ $p->prenomsEtu ?? '' }}
                </td>
                <td class="font-mono text-[0.78rem]">{{ $p->MATRICULE }}</td>
                <td><span class="pill pill-info">{{ $p->modePaiement ?: 'Espèces' }}</span></td>
                <td class="num text-encre" data-value="{{ $p->montantPaye }}">
                  {{ number_format((int) $p->montantPaye, 0, ',', ' ') }}
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-center text-encre-faint py-6">Aucun paiement enregistré pour ce filtre.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

</section>
@endsection

@section('js')
<script src="{{ asset('/assets/vendor/chart.js/chart.umd.js') }}"></script>
<script>
(function () {
  'use strict';

  if (typeof Chart === 'undefined') return;

  // ── Palette de la charte ──────────────────────────────────────────
  var C = {
    bordeaux: '#822A40', bordeauxLight: '#B55168', bordeauxSoft: '#E4ACB8',
    bleu: '#1D38D8', bleuLight: '#608CFA', bleuSoft: '#BFD4FE',
    noir: '#0B0B0C', gris: '#8A8D94', vert: '#15803D', ambre: '#D97706', rouge: '#DC2626'
  };
  var PALETTE = [C.bordeaux, C.bleu, C.bordeauxLight, C.bleuLight, C.noir, C.ambre, C.vert, C.gris];

  Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
  Chart.defaults.font.size = 11;
  Chart.defaults.color = '#5C5F66';

  var money = function (v) {
    return new Intl.NumberFormat('fr-FR').format(v) + ' F';
  };

  var tooltipBase = {
    backgroundColor: '#0B0B0C',
    padding: 10,
    cornerRadius: 8,
    titleFont: { size: 12, weight: '700' },
    bodyFont: { size: 11 },
    displayColors: true,
    boxPadding: 4,
  };

  var gridBase = {
    color: '#EFEFF3',
    drawBorder: false,
  };

  // ── 1. Inscriptions par année académique ─────────────────────────
  var insLabels = @json($parAnnee->pluck('label'));
  var insValues = @json($parAnnee->pluck('total'));

  var chartIns = new Chart(document.getElementById('chartInscriptions'), {
    type: 'bar',
    data: {
      labels: insLabels,
      datasets: [{
        label: 'Inscriptions',
        data: insValues,
        backgroundColor: C.bordeaux,
        hoverBackgroundColor: '#6B2435',
        borderRadius: 6,
        maxBarThickness: 54,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: Object.assign({}, tooltipBase, {
          callbacks: { label: function (ctx) { return ctx.parsed.y + ' inscription(s)'; } }
        })
      },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 11, weight: '600' } } },
        y: { beginAtZero: true, grid: gridBase, ticks: { precision: 0 } }
      }
    }
  });

  // Bascule barres / courbe
  document.querySelectorAll('[data-chart-mode]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('[data-chart-mode]').forEach(function (b) { b.classList.remove('on'); });
      btn.classList.add('on');
      var mode = btn.getAttribute('data-chart-mode');
      chartIns.config.type = mode;
      chartIns.data.datasets[0].backgroundColor = mode === 'line' ? 'rgba(130,42,64,.12)' : C.bordeaux;
      chartIns.data.datasets[0].borderColor = C.bordeaux;
      chartIns.data.datasets[0].borderWidth = mode === 'line' ? 2.5 : 0;
      chartIns.data.datasets[0].pointBackgroundColor = C.bordeaux;
      chartIns.data.datasets[0].pointRadius = mode === 'line' ? 3.5 : 0;
      chartIns.data.datasets[0].tension = 0.35;
      chartIns.data.datasets[0].fill = mode === 'line';
      chartIns.data.datasets[0].borderRadius = mode === 'bar' ? 6 : 0;
      chartIns.update();
    });
  });

  // ── 2. Répartition par sexe (camembert) ──────────────────────────
  var sexeLabels = @json($parSexe->pluck('label'));
  var sexeValues = @json($parSexe->pluck('total'));
  var sexeColors = sexeLabels.map(function (l) {
    if (l === 'Filles') return C.bordeaux;
    if (l === 'Garçons') return C.bleu;
    return C.gris;
  });

  new Chart(document.getElementById('chartSexe'), {
    type: 'doughnut',
    data: {
      labels: sexeLabels,
      datasets: [{
        data: sexeValues,
        backgroundColor: sexeColors,
        borderWidth: 3,
        borderColor: '#fff',
        hoverOffset: 6,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '62%',
      plugins: {
        legend: { position: 'bottom', labels: { padding: 14, usePointStyle: true, pointStyle: 'circle', boxWidth: 8 } },
        tooltip: Object.assign({}, tooltipBase, {
          callbacks: {
            label: function (ctx) {
              var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
              var pct = total ? Math.round(ctx.parsed / total * 100) : 0;
              return ' ' + ctx.label + ' : ' + ctx.parsed + ' (' + pct + '%)';
            }
          }
        })
      }
    }
  });

  // ── 3. Encaissements mensuels (courbe) ───────────────────────────
  var moisRaw = @json($parMois);
  var moisLabels = moisRaw.map(function (m) {
    if (!m.mois) return '—';
    var parts = m.mois.split('-');
    var d = new Date(parts[0], parseInt(parts[1], 10) - 1, 1);
    return d.toLocaleDateString('fr-FR', { month: 'short', year: '2-digit' });
  });
  var moisValues = moisRaw.map(function (m) { return Number(m.total) || 0; });

  new Chart(document.getElementById('chartMois'), {
    type: 'line',
    data: {
      labels: moisLabels,
      datasets: [{
        label: 'Encaissements',
        data: moisValues,
        borderColor: C.bleu,
        backgroundColor: 'rgba(29,56,216,.10)',
        borderWidth: 2.5,
        fill: true,
        tension: 0.35,
        pointBackgroundColor: C.bleu,
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 3.5,
        pointHoverRadius: 6,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: Object.assign({}, tooltipBase, {
          callbacks: { label: function (ctx) { return money(ctx.parsed.y); } }
        })
      },
      scales: {
        x: { grid: { display: false } },
        y: {
          beginAtZero: true,
          grid: gridBase,
          ticks: { callback: function (v) { return v >= 1000 ? (v / 1000) + 'k' : v; } }
        }
      }
    }
  });

  // ── 4. Modes de paiement (anneau) ────────────────────────────────
  var modeLabels = @json($parMode->pluck('label'));
  var modeValues = @json($parMode->pluck('total'));

  new Chart(document.getElementById('chartMode'), {
    type: 'doughnut',
    data: {
      labels: modeLabels,
      datasets: [{
        data: modeValues,
        backgroundColor: PALETTE,
        borderWidth: 3,
        borderColor: '#fff',
        hoverOffset: 6,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '58%',
      plugins: {
        legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true, pointStyle: 'circle', boxWidth: 8, font: { size: 10 } } },
        tooltip: Object.assign({}, tooltipBase, {
          callbacks: { label: function (ctx) { return ' ' + ctx.label + ' : ' + money(ctx.parsed); } }
        })
      }
    }
  });

  // ── 5. Recouvrement (barres empilées) ────────────────────────────
  var recLabels = @json($recouvrement->pluck('label'));
  var recEncaisse = @json($recouvrement->pluck('encaisse'));
  var recReste = @json($recouvrement->pluck('reste'));

  new Chart(document.getElementById('chartRecouvrement'), {
    type: 'bar',
    data: {
      labels: recLabels,
      datasets: [
        {
          label: 'Encaissé',
          data: recEncaisse,
          backgroundColor: C.bordeaux,
          borderRadius: { topLeft: 0, topRight: 0, bottomLeft: 6, bottomRight: 6 },
          maxBarThickness: 42,
        },
        {
          label: 'Reste à payer',
          data: recReste,
          backgroundColor: C.bordeauxSoft,
          borderRadius: { topLeft: 6, topRight: 6, bottomLeft: 0, bottomRight: 0 },
          maxBarThickness: 42,
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true, pointStyle: 'rectRounded', boxWidth: 8, font: { size: 10 } } },
        tooltip: Object.assign({}, tooltipBase, {
          callbacks: { label: function (ctx) { return ' ' + ctx.dataset.label + ' : ' + money(ctx.parsed.y); } }
        })
      },
      scales: {
        x: { stacked: true, grid: { display: false }, ticks: { font: { size: 10 } } },
        y: {
          stacked: true, beginAtZero: true, grid: gridBase,
          ticks: { callback: function (v) { return v >= 1000 ? (v / 1000) + 'k' : v; } }
        }
      }
    }
  });

  // ── 6. Tri des tableaux (clic sur en-tête) ───────────────────────
  document.querySelectorAll('table[data-sortable]').forEach(function (table) {
    var tbody = table.querySelector('tbody');
    table.querySelectorAll('th.sortable').forEach(function (th) {
      th.setAttribute('role', 'button');
      th.setAttribute('tabindex', '0');
      th.setAttribute('aria-label', 'Trier par ' + th.textContent.trim());

      var sort = function () {
        var col = parseInt(th.getAttribute('data-col'), 10);
        var isNum = th.getAttribute('data-sort') === 'num';
        var dir = th.classList.contains('asc') ? 'desc' : 'asc';

        table.querySelectorAll('th.sortable').forEach(function (h) {
          h.classList.remove('asc', 'desc');
          var ind = h.querySelector('.sort-ind');
          if (ind) ind.textContent = '↕';
        });
        th.classList.add(dir);
        var ind = th.querySelector('.sort-ind');
        if (ind) ind.textContent = dir === 'asc' ? '↑' : '↓';

        var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr')).filter(function (r) {
          return !r.querySelector('td[colspan]');
        });

        rows.sort(function (a, b) {
          var ca = a.children[col], cb = b.children[col];
          if (!ca || !cb) return 0;
          var va, vb;
          if (isNum) {
            va = parseFloat(ca.getAttribute('data-value') || ca.textContent.replace(/[^\d.-]/g, '')) || 0;
            vb = parseFloat(cb.getAttribute('data-value') || cb.textContent.replace(/[^\d.-]/g, '')) || 0;
            return dir === 'asc' ? va - vb : vb - va;
          }
          va = (ca.getAttribute('data-value') || ca.textContent).trim().toLowerCase();
          vb = (cb.getAttribute('data-value') || cb.textContent).trim().toLowerCase();
          return dir === 'asc' ? va.localeCompare(vb, 'fr') : vb.localeCompare(va, 'fr');
        });

        rows.forEach(function (r) { tbody.appendChild(r); });
      };

      th.addEventListener('click', sort);
      th.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); sort(); }
      });
    });
  });

  // ── 7. Export CSV du tableau principal ──────────────────────────
  var exportBtn = document.getElementById('btnExport');
  if (exportBtn) {
    exportBtn.addEventListener('click', function (e) {
      e.preventDefault();
      var table = document.getElementById('tblFilieres');
      if (!table) return;
      var rows = [['Année d\'étude', 'Effectif', 'Reste à payer (FCFA)']];
      table.querySelectorAll('tbody tr').forEach(function (tr) {
        var cells = tr.querySelectorAll('td');
        if (cells.length < 3 || cells[0].hasAttribute('colspan')) return;
        rows.push([
          cells[0].textContent.trim(),
          cells[1].textContent.trim(),
          cells[2].textContent.trim()
        ]);
      });
      var csv = rows.map(function (r) {
        return r.map(function (c) { return '"' + String(c).replace(/"/g, '""') + '"'; }).join(';');
      }).join('\r\n');
      var blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
      var url = URL.createObjectURL(blob);
      var a = document.createElement('a');
      a.href = url;
      a.download = 'edulmd-dashboard-' + new Date().toISOString().slice(0, 10) + '.csv';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    });
  }
})();
</script>
@endsection
