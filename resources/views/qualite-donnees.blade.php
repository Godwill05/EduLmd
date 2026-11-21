@extends('layout')
@section('title')
Qualité des données
@endsection

@section('css')
<style>
  .dq-kpi {
    background:#fff; border:1px solid var(--ed-ligne); border-radius:1rem;
    padding:1rem 1.1rem; box-shadow:var(--ed-ombre); height:100%;
  }
  .dq-kpi .v { font-size:1.5rem; font-weight:800; letter-spacing:-.02em; line-height:1.1; }
  .dq-kpi .l { font-size:.75rem; font-weight:600; color:#5C5F66; margin-top:.2rem; }
  .dq-kpi .s { font-size:.68rem; color:#8A8D94; margin-top:.15rem; }
  .dq-kpi.danger .v { color:#B91C1C; }
  .dq-kpi.warn .v { color:#B45309; }
  .dq-kpi.ok .v { color:#15803D; }

  .dq-card { background:#fff; border:1px solid var(--ed-ligne); border-radius:1rem; box-shadow:var(--ed-ombre); }
  .dq-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:1rem 1.15rem .5rem; }
  .dq-title { font-size:.95rem; font-weight:700; color:#0B0B0B; }
  .dq-sub { font-size:.75rem; color:#8A8D94; margin-top:.1rem; }

  .dq-tabs { display:flex; flex-wrap:wrap; gap:.25rem; border-bottom:1px solid var(--ed-ligne); padding:0 1.15rem; }
  .dq-tab {
    padding:.65rem .9rem; font-size:.82rem; font-weight:600; color:#5C5F66;
    border-bottom:2px solid transparent; text-decoration:none; margin-bottom:-1px;
  }
  .dq-tab:hover { color:#822A40; }
  .dq-tab.on { color:#822A40; border-bottom-color:#822A40; }
  .dq-tab .n {
    display:inline-block; margin-left:.35rem; font-size:.68rem; font-weight:700;
    background:#F4F4F6; color:#5C5F66; padding:.08rem .38rem; border-radius:99px;
  }
  .dq-tab.on .n { background:#F9E8EB; color:#6B2435; }

  .dq-groupe { border:1px solid var(--ed-ligne); border-radius:.8rem; margin-bottom:.7rem; overflow:hidden; }
  .dq-groupe-head {
    display:flex; align-items:center; gap:.6rem; padding:.6rem .8rem;
    background:#FCF5F6; border-bottom:1px solid var(--ed-ligne);
  }
  .dq-groupe-head .nom { font-weight:700; font-size:.85rem; color:#6B2435; }
  .dq-groupe-head .badge-n {
    margin-left:auto; font-size:.68rem; font-weight:700;
    background:#822A40; color:#fff; padding:.12rem .45rem; border-radius:99px;
  }
  .dq-etu {
    display:flex; align-items:center; gap:.6rem; padding:.5rem .8rem;
    border-bottom:1px solid #F5F5F7; font-size:.8rem;
  }
  .dq-etu:last-child { border-bottom:0; }
  .dq-etu .mat { font-family:ui-monospace,monospace; font-size:.74rem; color:#5C5F66; }
  .dq-etu .manque { color:#B91C1C; font-size:.72rem; }
  .dq-etu .actions { margin-left:auto; display:flex; gap:.25rem; }

  .dq-empty { text-align:center; padding:2.5rem 1.5rem; color:#8A8D94; }
  .dq-empty i { font-size:2rem; color:#15803D; }
  .dq-empty p { margin:.5rem 0 0; font-size:.85rem; }
</style>
@endsection

@section('content')
<section class="px-6 pb-3 pt-5">

  <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
    <div>
      <h1 class="text-[1.35rem] font-extrabold tracking-tight text-encre leading-tight">Qualité des données</h1>
      <p class="text-[0.82rem] text-encre-muted mt-1">
        Doublons probables, informations manquantes et inscriptions sans paiement.
      </p>
    </div>
    <a href="{{ route('home') }}" class="ed-btn ed-btn-secondary ed-btn-sm">
      <i class="ri-arrow-left-line"></i>Tableau de bord
    </a>
  </div>

  {{-- KPI de complétude --}}
  <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
    <div class="dq-kpi {{ $stats['tauxComplet'] >= 80 ? 'ok' : ($stats['tauxComplet'] >= 50 ? 'warn' : 'danger') }}">
      <div class="v">{{ $stats['tauxComplet'] }}%</div>
      <div class="l">Complétude</div>
      <div class="s">{{ number_format($stats['total'], 0, ',', ' ') }} étudiants</div>
    </div>
    <div class="dq-kpi {{ $stats['sans_photo'] ? 'warn' : 'ok' }}">
      <div class="v">{{ number_format($stats['sans_photo'], 0, ',', ' ') }}</div>
      <div class="l">Sans photo</div>
    </div>
    <div class="dq-kpi {{ $stats['sans_tel'] ? 'warn' : 'ok' }}">
      <div class="v">{{ number_format($stats['sans_tel'], 0, ',', ' ') }}</div>
      <div class="l">Sans téléphone</div>
    </div>
    <div class="dq-kpi {{ $stats['sans_email'] ? 'warn' : 'ok' }}">
      <div class="v">{{ number_format($stats['sans_email'], 0, ',', ' ') }}</div>
      <div class="l">Sans email</div>
    </div>
    <div class="dq-kpi {{ $stats['sans_date'] ? 'warn' : 'ok' }}">
      <div class="v">{{ number_format($stats['sans_date'], 0, ',', ' ') }}</div>
      <div class="l">Sans date de naissance</div>
    </div>
  </div>

  <div class="dq-card">
    {{-- Onglets --}}
    <div class="dq-tabs">
      <a href="{{ route('qualite-donnees.index', ['tab' => 'doublons']) }}"
         class="dq-tab {{ $tab === 'doublons' ? 'on' : '' }}">
        <i class="ri-file-copy-2-line me-1"></i>Doublons
        <span class="n">{{ $doublons->count() }}</span>
      </a>
      <a href="{{ route('qualite-donnees.index', ['tab' => 'manquants']) }}"
         class="dq-tab {{ $tab === 'manquants' ? 'on' : '' }}">
        <i class="ri-question-line me-1"></i>Champs manquants
      </a>
      <a href="{{ route('qualite-donnees.index', ['tab' => 'sans-paiement']) }}"
         class="dq-tab {{ $tab === 'sans-paiement' ? 'on' : '' }}">
        <i class="ri-hand-coin-line me-1"></i>Sans paiement
      </a>
    </div>

    {{-- ══════════ Onglet doublons ══════════ --}}
    @if ($tab === 'doublons')
      <div class="p-4">
        <p class="text-[0.8rem] text-encre-muted mb-3">
          Groupes d'étudiants partageant exactement le même nom et les mêmes prénoms.
          Vérifiez la date de naissance avant toute fusion — deux homonymes peuvent
          être deux personnes différentes.
        </p>

        @forelse ($doublonsDetail as $groupe)
          <div class="dq-groupe">
            <div class="dq-groupe-head">
              <i class="ri-error-warning-line text-[#B45309]"></i>
              <span class="nom">{{ $groupe['nom'] }} {{ $groupe['prenoms'] }}</span>
              <span class="badge-n">{{ $groupe['occurrences'] }} fiches</span>
            </div>
            @foreach ($groupe['etudiants'] as $e)
              <div class="dq-etu">
                <span class="mat">{{ $e['matricule'] }}</span>
                <span class="font-semibold text-encre">{{ $e['nom'] }}</span>
                <span class="text-encre-faint">·</span>
                <span>Né(e) le {{ $e['dateNais'] ?: '—' }}</span>
                <span class="text-encre-faint">·</span>
                <span>{{ $e['inscriptions'] }} inscription(s)</span>
                @if (!$e['tel'] && !$e['email'])
                  <span class="manque"><i class="ri-error-warning-line"></i> sans contact</span>
                @endif
                <span class="actions">
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('etudiants.fiche', ['matricule' => $e['matricule']]) }}" title="Voir la fiche">
                    <i class="ri-user-line"></i>
                  </a>
                  <a class="btn btn-sm btn-outline-secondary" href="{{ route('paiements.index') }}?matricule={{ urlencode($e['matricule']) }}" title="Paiements">
                    <i class="ri-hand-coin-line"></i>
                  </a>
                </span>
              </div>
            @endforeach
          </div>
        @empty
          <div class="dq-empty">
            <i class="ri-checkbox-circle-line"></i>
            <p>Aucun doublon détecté. Vos données sont propres.</p>
          </div>
        @endforelse
      </div>

    {{-- ══════════ Onglet champs manquants ══════════ --}}
    @elseif ($tab === 'manquants')
      <div class="p-4">
        <form method="GET" action="{{ route('qualite-donnees.index') }}" class="flex flex-wrap items-end gap-3 mb-4">
          <input type="hidden" name="tab" value="manquants">
          <div>
            <label class="block text-[0.7rem] font-bold uppercase tracking-wider text-encre-faint mb-1">Champ manquant</label>
            <select name="champ" class="form-select form-select-sm" onchange="this.form.submit()">
              <option value="photo" @selected($champ === 'photo')>Photo</option>
              <option value="telEtu" @selected($champ === 'telEtu')>Téléphone</option>
              <option value="emailEtu" @selected($champ === 'emailEtu')>Email</option>
              <option value="dateNaisEtu" @selected($champ === 'dateNaisEtu')>Date de naissance</option>
            </select>
          </div>
          <button class="btn btn-primary btn-sm">Actualiser</button>
        </form>

        <div class="overflow-x-auto">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Matricule</th>
                <th>Nom complet</th>
                <th>Sexe</th>
                <th>Téléphone</th>
                <th>Email</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($incomplets as $e)
                <tr>
                  <td class="font-mono text-[0.78rem]">{{ $e->MATRICULE }}</td>
                  <td class="font-semibold">{{ $e->nomEtu }} {{ $e->prenomsEtu }}</td>
                  <td>{{ $e->sexeEtu ?: '—' }}</td>
                  <td>{{ $e->telEtu ?: '—' }}</td>
                  <td>{{ $e->emailEtu ?: '—' }}</td>
                  <td>
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('etudiants.fiche', ['matricule' => $e->MATRICULE]) }}">
                      <i class="ri-edit-line"></i>Compléter
                    </a>
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="text-center text-encre-faint py-6">Aucun étudiant concerné.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="mt-3">@include('partials.pagination', ['paginateur' => $incomplets])</div>
      </div>

    {{-- ══════════ Onglet sans paiement ══════════ --}}
    @else
      <div class="p-4">
        <p class="text-[0.8rem] text-encre-muted mb-3">
          Inscriptions avec un reste à payer mais <strong>aucun paiement enregistré</strong>.
          Ce sont les dossiers à relancer en priorité.
        </p>

        <div class="overflow-x-auto">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Matricule</th>
                <th>Étudiant</th>
                <th>Année d'étude</th>
                <th>Année académique</th>
                <th class="text-end">Reste à payer</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($sansPaiement as $i)
                <tr>
                  <td class="font-mono text-[0.78rem]">{{ $i->MATRICULE }}</td>
                  <td class="font-semibold">{{ $i->nomEtu }} {{ $i->prenomsEtu }}</td>
                  <td>{{ $i->Libelle_AnEtude ?: '—' }}</td>
                  <td>{{ $i->ANNEE_ACAD ?: '—' }}</td>
                  <td class="text-end font-semibold text-[#B91C1C]">
                    {{ number_format((int) $i->reste_paye, 0, ',', ' ') }} F
                  </td>
                  <td>
                    <a class="btn btn-sm btn-primary" href="{{ route('paiements.index') }}?matricule={{ urlencode($i->MATRICULE) }}">
                      <i class="ri-add-line"></i>Encaisser
                    </a>
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="text-center text-encre-faint py-6">Toutes les inscriptions ont au moins un paiement.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="mt-3">@include('partials.pagination', ['paginateur' => $sansPaiement])</div>
      </div>
    @endif
  </div>

</section>
@endsection
