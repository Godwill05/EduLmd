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
          <span class="detail">Moins de la moitié des inscrits valident (min. 5 inscrits)</span>
        </div>

      </div>

      {{-- ── Évolution sur les quatre dernières années ─────────────────── --}}
      <div class="ed-card mb-4">
        <div class="ed-card-head">
          <h2 class="ed-card-title m-0">Évolution</h2>
          <span class="pi-legende m-0">Effectifs et taux, année par année</span>
        </div>
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
      </div>

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
          <span class="pi-legende m-0">Taux de validation le plus bas, minimum 5 inscrits</span>
        </div>
        @if (count($ueDifficiles) === 0)
          <div class="pi-vide">Pas assez de résultats enregistrés pour dégager une tendance.</div>
        @else
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
        @endif
        <div class="px-3 pb-3">
          <p class="pi-legende">
            Une UE dont le taux est très bas mérite un examen : barème trop sévère, horaires
            insuffisants, ou enseignant à soutenir. Le détail se trouve dans les récapitulatifs.
          </p>
        </div>
      </div>

    </div>
  </div>
</section>
@endsection
