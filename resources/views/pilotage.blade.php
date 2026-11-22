@extends('layout')

@section('title')
Pilotage
@endsection

@section('css')
<style>
  .pi-grille { display: grid; gap: 1rem; }
  .pi-kpi {
    background: #fff; border: 1px solid var(--ed-ligne); border-radius: 1rem;
    padding: 1.1rem 1.25rem; display: flex; flex-direction: column; gap: .15rem;
  }
  .pi-kpi .libelle { font-size: .72rem; font-weight: 700; letter-spacing: .05em;
    text-transform: uppercase; color: #8a8d94; }
  .pi-kpi .valeur { font-size: 1.7rem; font-weight: 800; letter-spacing: -.02em; color: #0b0b0c; }
  .pi-kpi .detail { font-size: .76rem; color: #8a8d94; }
  .pi-kpi .valeur.bon { color: #15803d; }
  .pi-kpi .valeur.moyen { color: #b45309; }
  .pi-kpi .valeur.faible { color: #b91c1c; }

  /* Barre de progression : le taux est lisible d'un coup d'œil,
     sans avoir à comparer des nombres entre eux. */
  .pi-barre { position: relative; height: .45rem; border-radius: 99px; background: #f0f0f3; overflow: hidden; }
  .pi-barre > span { position: absolute; inset: 0 auto 0 0; border-radius: 99px; }
  .pi-barre > span.bon { background: #16a34a; }
  .pi-barre > span.moyen { background: #f59e0b; }
  .pi-barre > span.faible { background: #dc2626; }

  .pi-table { width: 100%; border-collapse: collapse; }
  .pi-table thead th {
    font-size: .66rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase;
    color: #8a8d94; text-align: left; padding: .55rem .8rem; border-bottom: 1px solid var(--ed-ligne);
  }
  .pi-table tbody td { padding: .55rem .8rem; font-size: .82rem; border-bottom: 1px solid #f4f4f6; }
  .pi-table tbody tr:last-child td { border-bottom: 0; }
  .pi-table tbody tr:hover { background: #fcfcfd; }
  .pi-table .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
  .pi-table .taux { font-weight: 700; white-space: nowrap; }
  .pi-table .taux.bon { color: #15803d; }
  .pi-table .taux.moyen { color: #b45309; }
  .pi-table .taux.faible { color: #b91c1c; }
  .pi-table .ue-nom { max-width: 26rem; }

  .pi-legende { font-size: .72rem; color: #8a8d94; margin-top: .5rem; }

  .pi-onglets { display: flex; gap: .35rem; flex-wrap: wrap; }
  .pi-onglet {
    border: 1px solid var(--ed-ligne); background: #fff; border-radius: .6rem;
    padding: .4rem .85rem; font-size: .78rem; font-weight: 600; color: #5c5f66;
    cursor: pointer; font-family: inherit;
  }
  .pi-onglet.is-active { background: #822a40; border-color: #822a40; color: #fff; }

  .pi-vide { padding: 2rem; text-align: center; color: #8a8d94; font-size: .82rem; }

  /* ── Graphiques ──────────────────────────────────────────────────
     Un cadre de hauteur fixe : Chart.js a besoin d'un parent dont la
     hauteur est déterminée, sinon `maintainAspectRatio:false` donne un
     canvas de zéro pixel de haut. */
  .pi-chart { position: relative; height: 17rem; padding: .4rem 1.1rem 1.1rem; }
  .pi-chart-grand { height: 21rem; }
  @media (max-width: 640px) {
    .pi-chart { height: 13rem; }
    .pi-chart-grand { height: 15rem; }
  }

  /* Bascule tableau / graphique : les deux vues du même chiffre. Un
     directeur regarde la courbe, un comptable vérifie les nombres. */
  .pi-vues { display: inline-flex; gap: .2rem; padding: .18rem;
             background: #F4F4F6; border-radius: .5rem; }
  .pi-vue {
    border: 0; background: transparent; border-radius: .35rem;
    padding: .25rem .6rem; font-size: .7rem; font-weight: 600;
    color: #5C5F66; cursor: pointer; font-family: inherit;
    transition: background .15s, color .15s;
  }
  .pi-vue:hover { color: #822A40; }
  .pi-vue.is-on { background: #fff; color: #822A40; font-weight: 800;
                  box-shadow: 0 1px 2px rgba(16,24,40,.08); }
  .pi-vue[hidden] { display: none; }

  @media (min-width: 900px) {
    .pi-grille.kpi { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .pi-grille.deux { grid-template-columns: 1.35fr 1fr; }
  }
</style>
@endsection

@section('content')
<div class="pagetitle">
  <h1>Pilotage</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
      <li class="breadcrumb-item active">Pilotage</li>
    </ol>
  </nav>
</div>

<section class="section">
  <div class="row">
    <div class="col-lg-12">

      {{-- Filtres : une seule année pilote toute la page. --}}
      <form method="GET" action="{{ route('pilotage') }}" class="d-flex flex-wrap align-items-end gap-3 mb-4">
        <div>
          <label for="annee" class="form-label mb-1" style="font-size:.78rem;font-weight:600;">Année académique</label>
          <select name="annee" id="annee" class="form-select" onchange="this.form.submit()">
            @foreach ($annees as $a)
              <option value="{{ $a->id_Annee_Acad }}" @selected($a->id_Annee_Acad == $anneeChoisie)>{{ $a->ANNEE_ACAD }}</option>
            @endforeach
          </select>
        </div>
        <div class="ms-auto d-flex gap-2">
          <a href="{{ route('pilotage.export', ['annee' => $anneeChoisie]) }}"
             class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-download me-1"></i>Exporter
          </a>
          <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-speedometer2 me-1"></i>Tableau de bord classique
          </a>
        </div>
      </form>

      @if ($anneeInconnue ?? false)
        <div class="alert alert-warning d-flex gap-2 align-items-start mb-4" role="alert">
          <i class="bi bi-info-circle-fill"></i>
          <div>
            L'année académique demandée (identifiant {{ $anneeDemandee }}) n'existe pas encore
            dans la base. Les chiffres affichés sont ceux de
            <strong>{{ optional($annees->firstWhere('id_Annee_Acad', $anneeChoisie))->ANNEE_ACAD }}</strong>,
            l'année la plus récente enregistrée.
          </div>
        </div>
      @endif

      {{-- ── Les quatre chiffres qui comptent ─────────────────────────── --}}
      <div class="pi-grille kpi mb-4">

        <div class="pi-kpi">
          <span class="libelle">Recouvrement</span>
          <span class="valeur {{ $synthese['taux_recouvrement'] >= 90 ? 'bon' : ($synthese['taux_recouvrement'] >= 70 ? 'moyen' : 'faible') }}">
            {{ number_format($synthese['taux_recouvrement'], 1, ',', ' ') }} %
          </span>
          <span class="detail">
            {{ number_format($synthese['encaisse'], 0, ',', ' ') }} F encaissés sur
            {{ number_format($synthese['attendu'], 0, ',', ' ') }} F attendus
          </span>
          <div class="pi-barre mt-2">
            <span class="{{ $synthese['taux_recouvrement'] >= 90 ? 'bon' : ($synthese['taux_recouvrement'] >= 70 ? 'moyen' : 'faible') }}"
                  style="width: {{ min(100, $synthese['taux_recouvrement']) }}%"></span>
          </div>
        </div>

        <div class="pi-kpi">
          <span class="libelle">Reste à recouvrer</span>
          <span class="valeur {{ $synthese['reste'] > 0 ? 'faible' : 'bon' }}">
            {{ number_format($synthese['reste'], 0, ',', ' ') }} F
          </span>
          <span class="detail">{{ number_format($synthese['effectif'], 0, ',', ' ') }} inscription(s) sur l'année</span>
        </div>

        <div class="pi-kpi">
          <span class="libelle">Réussite (décisions du jury)</span>
          <span class="valeur {{ $synthese['taux_reussite'] >= 70 ? 'bon' : ($synthese['taux_reussite'] >= 50 ? 'moyen' : 'faible') }}">
            {{ number_format($synthese['taux_reussite'], 1, ',', ' ') }} %
          </span>
          <span class="detail">Sur les décisions enregistrées pour l'année</span>
          <div class="pi-barre mt-2">
            <span class="{{ $synthese['taux_reussite'] >= 70 ? 'bon' : ($synthese['taux_reussite'] >= 50 ? 'moyen' : 'faible') }}"
                  style="width: {{ min(100, $synthese['taux_reussite']) }}%"></span>
          </div>
        </div>

        <div class="pi-kpi">
          <span class="libelle">UE en difficulté</span>
          <span class="valeur {{ $synthese['ue_en_difficulte'] === 0 ? 'bon' : ($synthese['ue_en_difficulte'] <= 20 ? 'moyen' : 'faible') }}">
            {{ $synthese['ue_en_difficulte'] }}
          </span>
          <span class="detail">
            Moins de la moitié des inscrits valident (min. 5 inscrits), sur cette année
          </span>
        </div>

      </div>

      {{-- ── Évolution sur les quatre dernières années ─────────────────── --}}
      <div class="ed-card mb-4">
        <div class="ed-card-head">
          <h2 class="ed-card-title m-0">Évolution sur quatre ans</h2>
          <div class="d-flex align-items-center gap-2">
            <span class="pi-legende m-0 d-none d-lg-inline">Effectifs et taux, année par année</span>
            <div class="pi-vues" role="group" aria-label="Affichage">
              <button type="button" class="pi-vue is-on" data-vue="graphe" data-cible="evol">Graphique</button>
              <button type="button" class="pi-vue" data-vue="tableau" data-cible="evol">Tableau</button>
            </div>
          </div>
        </div>

        {{-- Vue graphique --}}
        <div data-vue-panneau="graphe" data-pour="evol">
          <div class="pi-chart pi-chart-grand">
            <canvas id="chartEvolution" aria-label="Évolution des effectifs, du recouvrement et de la réussite"
                    role="img"></canvas>
          </div>
          <div class="px-3 pb-3">
            <p class="pi-legende">
              L'effectif est en barres (échelle de gauche), les taux en courbes (échelle de droite).
              Les deux mouvements opposés sont le sujet : plus d'étudiants, moins de réussite.
            </p>
          </div>
        </div>

        {{-- Vue tableau --}}
        <div data-vue-panneau="tableau" data-pour="evol" hidden>
        <div class="table-responsive">
          <table class="pi-table">
            <thead>
              <tr>
                <th>Année académique</th>
                <th class="num">Effectif</th>
                <th class="num">Encaissé</th>
                <th class="num">Attendu</th>
                <th class="num">Recouvrement</th>
                <th class="num">Réussite</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($evolution as $i => $ligne)
                @php $reussite = $reussitesParAnnee[$ligne['annee']] ?? null; @endphp
                <tr>
                  <td><strong>{{ $ligne['annee'] }}</strong></td>
                  <td class="num">{{ number_format($ligne['effectif'], 0, ',', ' ') }}</td>
                  <td class="num">{{ number_format($ligne['encaisse'], 0, ',', ' ') }} F</td>
                  <td class="num">{{ number_format($ligne['attendu'], 0, ',', ' ') }} F</td>
                  <td class="num taux {{ $ligne['taux'] >= 90 ? 'bon' : ($ligne['taux'] >= 70 ? 'moyen' : 'faible') }}">
                    {{ number_format($ligne['taux'], 1, ',', ' ') }} %
                  </td>
                  <td class="num taux {{ $reussite === null ? '' : ($reussite >= 70 ? 'bon' : ($reussite >= 50 ? 'moyen' : 'faible')) }}">
                    {{ $reussite === null ? '—' : number_format($reussite, 1, ',', ' ') . ' %' }}
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="px-3 pb-3">
          <p class="pi-legende">
            Le taux de réussite est calculé sur les décisions du jury enregistrées ; une année sans
            délibération complète apparaît avec un tiret.
          </p>
        </div>
        </div>{{-- /vue tableau --}}
      </div>

      {{-- ── Recouvrement par filière, en barres horizontales ─────────── --}}
      @if (count($recouvrement) > 0)
        <div class="ed-card mb-4">
          <div class="ed-card-head">
            <h2 class="ed-card-title m-0">Recouvrement par filière</h2>
            <div class="d-flex align-items-center gap-2">
              <span class="pi-legende m-0 d-none d-lg-inline">Où l'argent rentre le moins bien</span>
              <div class="pi-vues" role="group" aria-label="Affichage">
                <button type="button" class="pi-vue is-on" data-vue="graphe" data-cible="fil">Graphique</button>
                <button type="button" class="pi-vue" data-vue="tableau" data-cible="fil">Tableau</button>
              </div>
            </div>
          </div>

          <div data-vue-panneau="graphe" data-pour="fil">
            <div class="pi-chart">
              <canvas id="chartFilieres" aria-label="Taux de recouvrement par filière" role="img"></canvas>
            </div>
            <div class="px-3 pb-3">
              <p class="pi-legende">
                100 % serait l'idéal ; une barre courte signale une filière où les relances
                doivent commencer.
              </p>
            </div>
          </div>

          {{-- Vue tableau : le détail par filière, avec les montants --}}
          <div data-vue-panneau="tableau" data-pour="fil" hidden>
            <div class="table-responsive">
              <table class="pi-table">
                <thead>
                  <tr>
                    <th>Filière</th>
                    <th class="num">Effectif</th>
                    <th class="num">Encaissé</th>
                    <th class="num">Reste</th>
                    <th class="num">Taux</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($recouvrement as $ligne)
                    <tr>
                      <td>{{ $ligne['libelle'] }}</td>
                      <td class="num">{{ $ligne['effectif'] }}</td>
                      <td class="num">{{ number_format($ligne['encaisse'], 0, ',', ' ') }} F</td>
                      <td class="num">{{ number_format($ligne['reste'], 0, ',', ' ') }} F</td>
                      <td class="num taux {{ $ligne['taux'] >= 90 ? 'bon' : ($ligne['taux'] >= 70 ? 'moyen' : 'faible') }}">
                        {{ number_format($ligne['taux'], 1, ',', ' ') }} %
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      @endif

      <div class="pi-grille deux">

        {{-- ── Recouvrement par filière ───────────────────────────────── --}}
        <div class="ed-card">
          <div class="ed-card-head">
            <h2 class="ed-card-title m-0">Recouvrement par filière</h2>
            <span class="pi-legende m-0">Les plus faibles d'abord</span>
          </div>
          @if (count($recouvrement) === 0)
            <div class="pi-vide">Aucune donnée financière pour cette année.</div>
          @else
            <div class="table-responsive">
              <table class="pi-table">
                <thead>
                  <tr>
                    <th>Filière</th>
                    <th class="num">Effectif</th>
                    <th class="num">Encaissé</th>
                    <th class="num">Reste</th>
                    <th class="num">Taux</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($recouvrement as $ligne)
                    <tr>
                      <td>{{ $ligne['libelle'] }}</td>
                      <td class="num">{{ $ligne['effectif'] }}</td>
                      <td class="num">{{ number_format($ligne['encaisse'], 0, ',', ' ') }} F</td>
                      <td class="num">{{ number_format($ligne['reste'], 0, ',', ' ') }} F</td>
                      <td class="num taux {{ $ligne['taux'] >= 90 ? 'bon' : ($ligne['taux'] >= 70 ? 'moyen' : 'faible') }}">
                        {{ number_format($ligne['taux'], 1, ',', ' ') }} %
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>

        {{-- ── Impayés les plus lourds ────────────────────────────────── --}}
        <div class="ed-card">
          <div class="ed-card-head">
            <h2 class="ed-card-title m-0">Impayés les plus lourds</h2>
            <a href="{{ route('paiements.index') }}" class="pi-legende m-0" style="text-decoration:underline;">Voir les paiements</a>
          </div>
          @if (count($impayes) === 0)
            <div class="pi-vide">Aucun impayé sur cette année.</div>
          @else
            <div class="table-responsive">
              <table class="pi-table">
                <thead>
                  <tr>
                    <th>Étudiant</th>
                    <th class="num">Reste</th>
                    <th class="num">Depuis</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($impayes as $ligne)
                    <tr>
                      <td>
                        <a href="{{ route('etudiants.fiche', ['matricule' => $ligne['matricule']]) }}">
                          {{ $ligne['nom'] ?: $ligne['matricule'] }}
                        </a>
                        <div class="detail" style="font-size:.7rem;color:#8a8d94;">{{ $ligne['filiere'] }}</div>
                      </td>
                      <td class="num taux faible">{{ number_format($ligne['reste'], 0, ',', ' ') }} F</td>
                      <td class="num">{{ $ligne['depuis'] }} j</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>

      </div>

      {{-- ── UE les plus difficiles ─────────────────────────────────────── --}}
      <div class="ed-card mt-4">
        <div class="ed-card-head">
          <h2 class="ed-card-title m-0">UE les plus difficiles</h2>
          <div class="d-flex align-items-center gap-2">
            <span class="pi-legende m-0 d-none d-lg-inline">Taux de validation, min. 5 inscrits</span>
            @if (count($ueDifficiles) > 0)
              <div class="pi-vues" role="group" aria-label="Affichage">
                <button type="button" class="pi-vue is-on" data-vue="graphe" data-cible="ue">Graphique</button>
                <button type="button" class="pi-vue" data-vue="tableau" data-cible="ue">Tableau</button>
              </div>
            @endif
          </div>
        </div>

        @if (count($ueDifficiles) === 0)
          <div class="pi-vide">Pas assez de résultats enregistrés pour dégager une tendance.</div>
        @else
          {{-- Vue graphique : les dix UE en barres horizontales --}}
          <div data-vue-panneau="graphe" data-pour="ue">
            <div class="pi-chart pi-chart-grand">
              <canvas id="chartUe" role="img"
                      aria-label="Taux de validation des dix UE les plus difficiles"></canvas>
            </div>
            <div class="px-3 pb-3">
              <p class="pi-legende">
                Les dix UE où le plus d'étudiants échouent. Un taux à 0 % sur 50 inscrits n'a pas
                la même cause qu'un 0 % sur 5 : le nombre d'inscrits est rappelé après le taux.
              </p>
            </div>
          </div>

          {{-- Vue tableau : le détail chiffré --}}
          <div data-vue-panneau="tableau" data-pour="ue" hidden>
            <div class="table-responsive">
              <table class="pi-table">
                <thead>
                  <tr>
                    <th>Unité d'enseignement</th>
                    <th class="num">Inscrits</th>
                    <th class="num">Validée</th>
                    <th class="num">Moyenne</th>
                    <th class="num">Taux</th>
                    <th style="width:9rem;">Répartition</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($ueDifficiles as $ligne)
                    <tr>
                      <td class="ue-nom">
                        {{ $ligne['ue'] }}
                        <div style="font-size:.68rem;color:#8a8d94;">{{ $ligne['code'] }}</div>
                      </td>
                      <td class="num">{{ $ligne['presents'] }}</td>
                      <td class="num">{{ $ligne['reussis'] }}</td>
                      <td class="num">{{ number_format($ligne['moyenne'], 2, ',', ' ') }}</td>
                      <td class="num taux {{ $ligne['taux'] >= 70 ? 'bon' : ($ligne['taux'] >= 50 ? 'moyen' : 'faible') }}">
                        {{ number_format($ligne['taux'], 1, ',', ' ') }} %
                      </td>
                      <td>
                        <div class="pi-barre">
                          <span class="{{ $ligne['taux'] >= 70 ? 'bon' : ($ligne['taux'] >= 50 ? 'moyen' : 'faible') }}"
                                style="width: {{ min(100, $ligne['taux']) }}%"></span>
                        </div>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <div class="px-3 pb-3">
              <p class="pi-legende">
                Une UE dont le taux est très bas mérite un examen : barème trop sévère, horaires
                insuffisants, ou enseignant à soutenir. Le détail se trouve dans les récapitulatifs.
              </p>
            </div>
          </div>
        @endif
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

  /* ── Couleurs lues dans le document ────────────────────────────────
     Elles viennent des variables CSS, donc du thème et de l'accent
     choisis. Un changement d'apparence recharge les graphiques (voir
     l'écouteur `edulmd:theme` en bas) : inutile de coder « bordeaux » en
     dur, sinon un utilisateur en thème violet garderait des courbes
     bordeaux. */
  function couleurs() {
    var s = getComputedStyle(document.documentElement);
    var v = function (nom, repli) { return (s.getPropertyValue(nom) || '').trim() || repli; };
    var sombre = document.documentElement.getAttribute('data-theme') === 'dark';

    return {
      accent: v('--ed-bordeaux', '#822A40'),
      accentFonce: v('--ed-bordeaux-fonce', '#6B2435'),
      accentClair: v('--ed-bordeaux-clair', '#FBEDF1'),
      texte: sombre ? '#C9CBD2' : '#5C5F66',
      texteFort: sombre ? '#F2F2F4' : '#0B0B0C',
      grille: sombre ? '#2B2D33' : '#EFEFF3',
      vert: '#16A34A',
      ambre: '#F59E0B',
      rouge: '#DC2626',
      bleu: '#1D38D8',
      sombre: sombre
    };
  }

  function base(c) {
    return {
      responsive: true,
      maintainAspectRatio: false,
      /* Les animations restent sobres : un graphique qui pousse pendant
         deux secondes empêche de lire les chiffres. */
      animation: { duration: 650, easing: 'easeOutQuart' },
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: {
          display: true,
          position: 'bottom',
          labels: {
            boxWidth: 10, boxHeight: 10, usePointStyle: true, pointStyle: 'circle',
            padding: 14, color: c.texte, font: { size: 11, weight: '600' }
          }
        },
        tooltip: {
          backgroundColor: c.sombre ? '#26282F' : '#0B0B0C',
          titleColor: '#fff', bodyColor: '#fff',
          padding: 10, cornerRadius: 8,
          titleFont: { size: 12, weight: '700' },
          bodyFont: { size: 11 },
          boxPadding: 4, displayColors: true
        }
      }
    };
  }

  var euros = function (v) { return new Intl.NumberFormat('fr-FR').format(v); };

  /* ── Données rendues par le contrôleur ───────────────────────────── */
  var evolution   = @json($evolution);
  var reussites   = @json($reussitesParAnnee);
  var recouvrement = @json($recouvrement);
  var ueDifficiles = @json($ueDifficiles);

  var graphes = [];

  function detruire() {
    graphes.forEach(function (g) { try { g.destroy(); } catch (e) {} });
    graphes = [];
  }

  /* ── 1. Évolution : effectif en barres, taux en courbes ──────────── */
  function dessinerEvolution(c) {
    var el = document.getElementById('chartEvolution');
    if (!el || !evolution.length) return;

    // Les années sans délibération n'ont pas de taux : on passe null
    // plutôt que 0, sinon la courbe plonge au plancher et ment.
    var tauxRecouvrement = evolution.map(function (e) {
      return e.attendu > 0 ? Math.round(e.taux * 10) / 10 : null;
    });
    var tauxReussite = evolution.map(function (e) {
      var t = reussites[e.annee];
      return (t === undefined || t === null) ? null : t;
    });

    var o = base(c);
    o.scales = {
      x: {
        grid: { display: false },
        ticks: { color: c.texte, font: { size: 11, weight: '600' } }
      },
      y: {
        beginAtZero: true,
        position: 'left',
        title: { display: true, text: 'Étudiants', color: c.texte, font: { size: 10, weight: '700' } },
        grid: { color: c.grille, drawBorder: false },
        ticks: { color: c.texte, precision: 0 }
      },
      y1: {
        beginAtZero: true,
        max: 100,
        position: 'right',
        title: { display: true, text: 'Taux (%)', color: c.texte, font: { size: 10, weight: '700' } },
        grid: { display: false },
        ticks: { color: c.texte, callback: function (v) { return v + ' %'; } }
      }
    };
    o.plugins.tooltip.callbacks = {
      label: function (ctx) {
        if (ctx.dataset.yAxisID === 'y1') {
          return ctx.dataset.label + ' : ' + (ctx.parsed.y === null ? '—' : ctx.parsed.y + ' %');
        }
        return ctx.dataset.label + ' : ' + euros(ctx.parsed.y) + ' étudiant(s)';
      }
    };

    graphes.push(new Chart(el, {
      type: 'bar',
      data: {
        labels: evolution.map(function (e) { return e.annee; }),
        datasets: [
          {
            label: 'Effectif',
            data: evolution.map(function (e) { return e.effectif; }),
            backgroundColor: c.accent,
            hoverBackgroundColor: c.accentFonce,
            borderRadius: 7,
            maxBarThickness: 68,
            order: 2
          },
          {
            type: 'line',
            label: 'Recouvrement',
            data: tauxRecouvrement,
            borderColor: c.vert,
            backgroundColor: 'transparent',
            borderWidth: 2.5,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: c.vert,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            tension: 0.32,
            spanGaps: true,
            yAxisID: 'y1',
            order: 0
          },
          {
            type: 'line',
            label: 'Réussite',
            data: tauxReussite,
            borderColor: c.rouge,
            backgroundColor: 'transparent',
            borderWidth: 2.5,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: c.rouge,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            tension: 0.32,
            spanGaps: true,
            yAxisID: 'y1',
            order: 1
          }
        ]
      },
      options: o
    }));
  }

  /* ── 2. Recouvrement par filière : barres horizontales ──────────── */
  function dessinerFilieres(c) {
    var el = document.getElementById('chartFilieres');
    if (!el || !recouvrement.length) return;

    // Les plus faibles en haut : l'œil descend du problème vers le sain.
    var lignes = recouvrement.slice(0, 12).slice().reverse();

    var o = base(c);
    o.indexAxis = 'y';
    o.plugins.legend.display = false;
    o.plugins.tooltip.callbacks = {
      label: function (ctx) {
        var l = lignes[ctx.dataIndex];
        return [
          'Recouvrement : ' + ctx.parsed.x + ' %',
          'Encaissé : ' + euros(l.encaisse) + ' F',
          'Reste : ' + euros(l.reste) + ' F',
          'Effectif : ' + l.effectif
        ];
      }
    };
    o.scales = {
      x: {
        beginAtZero: true, max: 100,
        grid: { color: c.grille, drawBorder: false },
        ticks: { color: c.texte, callback: function (v) { return v + ' %'; } }
      },
      y: {
        grid: { display: false },
        ticks: {
          color: c.texte,
          font: { size: 10, weight: '600' },
          callback: function (v) {
            var l = lignes[v];
            return l ? l.libelle : '';
          }
        }
      }
    };

    // Couleur par seuil : vert / ambre / rouge. C'est la lecture
    // immédiate du graphique, avant même de lire les pourcentages.
    var couleursBarres = lignes.map(function (l) {
      return l.taux >= 90 ? c.vert : (l.taux >= 70 ? c.ambre : c.rouge);
    });

    graphes.push(new Chart(el, {
      type: 'bar',
      data: {
        labels: lignes.map(function (l) { return l.libelle; }),
        datasets: [{
          label: 'Recouvrement',
          data: lignes.map(function (l) { return Math.round(l.taux * 10) / 10; }),
          backgroundColor: couleursBarres,
          hoverBackgroundColor: couleursBarres,
          borderRadius: 5,
          maxBarThickness: 22
        }]
      },
      options: o
    }));
  }

  /* ── 3. UE les plus difficiles ──────────────────────────────────── */
  function dessinerUe(c) {
    var el = document.getElementById('chartUe');
    if (!el || !ueDifficiles.length) return;

    var lignes = ueDifficiles.slice(0, 10).slice().reverse();

    var o = base(c);
    o.indexAxis = 'y';
    o.plugins.legend.display = false;
    o.plugins.tooltip.callbacks = {
      label: function (ctx) {
        var l = lignes[ctx.dataIndex];
        return [
          'Taux de validation : ' + l.taux + ' %',
          'Validée : ' + l.reussis + ' / ' + l.presents,
          'Moyenne : ' + String(l.moyenne).replace('.', ',')
        ];
      }
    };
    o.scales = {
      x: {
        beginAtZero: true, max: 100,
        grid: { color: c.grille, drawBorder: false },
        ticks: { color: c.texte, callback: function (v) { return v + ' %'; } }
      },
      y: {
        grid: { display: false },
        ticks: {
          color: c.texte,
          font: { size: 10, weight: '600' },
          callback: function (v) {
            var l = lignes[v];
            if (!l) return '';
            // Troncature : les libellés d'UE dépassent parfois 80
            // caractères et écraseraient le graphique.
            var nom = l.ue.length > 34 ? l.ue.slice(0, 32) + '…' : l.ue;
            return nom + '  (' + l.presents + ')';
          }
        }
      }
    };

    graphes.push(new Chart(el, {
      type: 'bar',
      data: {
        labels: lignes.map(function (l) { return l.ue; }),
        datasets: [{
          label: 'Taux de validation',
          data: lignes.map(function (l) { return Math.round(l.taux * 10) / 10; }),
          backgroundColor: lignes.map(function (l) {
            return l.taux >= 70 ? c.vert : (l.taux >= 50 ? c.ambre : c.rouge);
          }),
          borderRadius: 5,
          maxBarThickness: 24
        }]
      },
      options: o
    }));
  }

  function toutDessiner() {
    detruire();
    var c = couleurs();
    dessinerEvolution(c);
    dessinerFilieres(c);
    dessinerUe(c);
  }

  toutDessiner();

  /* ── Bascule Graphique / Tableau ─────────────────────────────────── */
  document.querySelectorAll('[data-vue]').forEach(function (bouton) {
    bouton.addEventListener('click', function () {
      var cible = bouton.getAttribute('data-cible');
      var vue = bouton.getAttribute('data-vue');

      document.querySelectorAll('[data-vue][data-cible="' + cible + '"]').forEach(function (b) {
        b.classList.toggle('is-on', b === bouton);
      });

      document.querySelectorAll('[data-vue-panneau][data-pour="' + cible + '"]').forEach(function (p) {
        p.hidden = p.getAttribute('data-vue-panneau') !== vue;
      });

      // Le canvas avait une taille nulle tant qu'il était caché : il
      // faut demander à Chart.js de se remesurer.
      window.requestAnimationFrame(function () {
        graphes.forEach(function (g) { try { g.resize(); } catch (e) {} });
      });
    });
  });

  /* ── Le thème change → les couleurs doivent suivre ───────────────── */
  var refaire = null;
  document.addEventListener('edulmd:theme', function () {
    window.clearTimeout(refaire);
    refaire = window.setTimeout(toutDessiner, 120);
  });

  /* Chart.js ne suit pas les redimensionnements de la fenêtre quand le
     canvas est dans un conteneur `overflow:auto` : on force. */
  window.addEventListener('resize', function () {
    window.clearTimeout(refaire);
    refaire = window.setTimeout(function () {
      graphes.forEach(function (g) { try { g.resize(); } catch (e) {} });
    }, 150);
  });
})();
</script>
@endsection
