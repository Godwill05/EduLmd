@extends('layout')

@section('title')
Emploi du temps
@endsection

@section('css')
<style>
  .edt-card { background:#fff; border:1px solid var(--ed-ligne); border-radius:1rem;
              box-shadow:var(--ed-ombre); overflow:hidden; }
  .edt-filtres { padding:1rem 1.1rem; border-bottom:1px solid var(--ed-ligne); background:#FCFCFD; }
  .edt-lbl { display:block; font-size:.68rem; font-weight:700; text-transform:uppercase;
             letter-spacing:.04em; color:#8A8D94; margin-bottom:.25rem; }

  /* La grille : une colonne par jour, une ligne par heure de départ.
     Défilement horizontal sur petit écran plutôt que colonnes écrasées. */
  .edt-scroll { overflow-x:auto; }
  .edt-grille { min-width:52rem; width:100%; border-collapse:separate; border-spacing:0; }

  .edt-grille thead th {
    background:#822A40; color:#fff; font-size:.72rem; font-weight:700;
    text-transform:uppercase; letter-spacing:.05em; text-align:center;
    padding:.6rem .5rem; border-right:1px solid rgba(255,255,255,.18);
  }
  .edt-grille thead th:first-child { background:#6b2134; width:5.5rem; }
  .edt-grille thead th:last-child { border-right:0; }

  .edt-grille td { border-bottom:1px solid #EEEEF1; padding:.45rem .4rem; vertical-align:top; }
  .edt-grille td.heure {
    background:#FAFAFB; font-size:.72rem; font-weight:700; color:#5C5F66;
    text-align:center; vertical-align:middle; font-variant-numeric:tabular-nums;
    border-right:1px solid #EEEEF1; white-space:nowrap;
  }
  .edt-grille tr:nth-child(even) td.heure { background:#F5F5F7; }
  .edt-grille td.jour { width:calc((100% - 5.5rem) / 7); min-height:3.2rem; }

  .edt-seance {
    background:#FDF3F5; border-left:3px solid #822A40; border-radius:.35rem;
    padding:.35rem .45rem; margin-bottom:.3rem; font-size:.72rem; line-height:1.3;
  }
  .edt-seance:last-child { margin-bottom:0; }
  .edt-seance .titre { font-weight:700; color:#0B0B0B; }
  .edt-seance .meta { color:#8A8D94; font-size:.66rem; }
  .edt-seance .salle { font-weight:700; color:#822A40; }
  .edt-seance .periode { color:#A0A3A9; font-size:.62rem; display:block; margin-top:.1rem; }

  .edt-vide { text-align:center; padding:3rem 1.5rem; color:#8A8D94; }
  .edt-vide i { font-size:2.2rem; }

  .edt-synthese { display:flex; gap:1.5rem; flex-wrap:wrap; padding:.9rem 1.1rem;
                  background:#FCFCFD; border-top:1px solid var(--ed-ligne); font-size:.78rem; }
  .edt-synthese b { color:#0B0B0B; }

  .edt-note { font-size:.74rem; color:#8A8D94; padding:.7rem 1.1rem 0; }
</style>
@endsection

@section('content')
<div class="pagetitle">
  <h1>Emploi du temps</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
      <li class="breadcrumb-item active">Emploi du temps</li>
    </ol>
  </nav>
</div>

<section class="section">
  <div class="row">
    <div class="col-lg-12">

      @if (count($classes) === 0)
        <div class="edt-card">
          <div class="edt-vide">
            <i class="bi bi-calendar-x"></i>
            <p class="mt-2 mb-1">Aucun créneau n'est encore saisi.</p>
            <p class="mb-0" style="font-size:.8rem;">
              Les créneaux se saisissent dans <strong>Gestion des cours</strong> : on y choisit
              la classe, l'ECUE, la salle, les dates et les heures.
            </p>
          </div>
        </div>
      @else
        <div class="edt-card">
          <form method="GET" action="{{ route('emploi-du-temps.index') }}" class="edt-filtres">
            <div class="row g-2 align-items-end">
              <div class="col-md-4">
                <label class="edt-lbl" for="classe">Classe</label>
                <select name="classe" id="classe" class="form-select form-select-sm" onchange="this.form.submit()">
                  @foreach ($classes as $c)
                    <option value="{{ $c->id_S_Gr_Spe }}" @selected((int) $c->id_S_Gr_Spe === (int) $classeChoisie)>
                      {{ $c->Nom_Classe }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-2">
                <label class="edt-lbl" for="du">Du</label>
                <input type="date" name="du" id="du" value="{{ $du }}" class="form-control form-control-sm">
              </div>
              <div class="col-md-2">
                <label class="edt-lbl" for="au">Au</label>
                <input type="date" name="au" id="au" value="{{ $au }}" class="form-control form-control-sm">
              </div>
              <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Appliquer</button>
                <a href="{{ route('emploi-du-temps.index') }}" class="btn btn-outline-secondary btn-sm">Tout voir</a>
                @if ($classeChoisie)
                  <a href="{{ route('emploi-du-temps.imprimer', array_filter(['classe' => $classeChoisie, 'du' => $du, 'au' => $au])) }}"
                     target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-printer me-1"></i>Imprimer
                  </a>
                @endif
              </div>
            </div>
          </form>

          @if ($grille['total'] === 0)
            <div class="edt-vide">
              <i class="bi bi-calendar-x"></i>
              <p class="mt-2 mb-0">
                @if ($du || $au)
                  Aucun créneau pour {{ $classe->Nom_Classe ?? 'cette classe' }} sur cette période.
                @else
                  Aucun créneau saisi pour {{ $classe->Nom_Classe ?? 'cette classe' }}.
                @endif
              </p>
            </div>
          @else
            <div class="edt-scroll">
              <table class="edt-grille">
                <thead>
                  <tr>
                    <th>Heure</th>
                    @foreach (\App\Support\EmploiDuTemps::JOURS as $jour)
                      <th>{{ $jour }}</th>
                    @endforeach
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="heure">{{ $grille['depart'] }}</td>
                    @foreach (array_keys(\App\Support\EmploiDuTemps::JOURS) as $num)
                      <td class="jour">
                        @if (empty($grille['grille'][$num][$grille['depart']]))
                          <span style="color:#D9D9DE;font-size:.7rem;">—</span>
                        @else
                          @foreach ($grille['grille'][$num][$grille['depart']] as $s)
                            <div class="edt-seance">
                              <div class="titre">{{ $s['libelle_ecue'] }}</div>
                              <div class="meta">
                                {{ $s['ue'] }} · <span class="salle">{{ $s['libelle_salle'] }}</span>
                              </div>
                              <span class="periode">
                                {{ \Carbon\Carbon::parse($s['du'])->format('d/m') }}
                                → {{ \Carbon\Carbon::parse($s['au'])->format('d/m/Y') }}
                              </span>
                            </div>
                          @endforeach
                        @endif
                      </td>
                    @endforeach
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="edt-synthese">
              <span>Classe : <b>{{ $classe->Nom_Classe ?? '—' }}</b></span>
              <span>Séances : <b>{{ $grille['total'] }}</b></span>
              <span>Amplitude : <b>{{ $grille['depart'] }} – {{ $grille['arrivee'] }}</b></span>
              @if ($du || $au)
                <span>Période : <b>{{ $du ?: '…' }} → {{ $au ?: '…' }}</b></span>
              @endif
            </div>
          @endif
        </div>

        <p class="edt-note">
          <i class="bi bi-info-circle me-1"></i>
          Un créneau saisi du 13 au 29 juillet apparaît sur chaque jour de la semaine que cette
          période contient : c'est ce que vivent les étudiants. Les créneaux se modifient dans
          <strong>Gestion des cours</strong>.
        </p>
      @endif

    </div>
  </div>
</section>
@endsection
