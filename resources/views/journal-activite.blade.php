@extends('layout')
@section('title')
Journal d'activité
@endsection

@section('css')
<style>
  .al-kpi { display:flex; gap:.6rem; flex-wrap:wrap; margin-bottom:1rem; }
  .al-chip {
    display:inline-flex; align-items:center; gap:.4rem;
    background:#fff; border:1px solid var(--ed-ligne); border-radius:99px;
    padding:.35rem .8rem; font-size:.78rem; font-weight:600; color:#5C5F66;
    text-decoration:none; box-shadow:var(--ed-ombre);
  }
  .al-chip:hover { border-color:#C9A3AC; color:#822A40; }
  .al-chip.on { background:#822A40; border-color:#822A40; color:#fff; }
  .al-chip b { font-weight:800; }

  .al-card { background:#fff; border:1px solid var(--ed-ligne); border-radius:1rem; box-shadow:var(--ed-ombre); overflow:hidden; }
  .al-filtres { padding:1rem 1.1rem; border-bottom:1px solid var(--ed-ligne); background:#FCFCFD; }
  .al-filtres .row-g { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:.6rem; }
  .al-lbl { display:block; font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#8A8D94; margin-bottom:.2rem; }

  .al-item { display:flex; gap:.85rem; padding:.9rem 1.1rem; border-bottom:1px solid #F2F2F5; }
  .al-item:last-child { border-bottom:0; }
  .al-item:hover { background:#FDFAFA; }

  .al-ico {
    flex:0 0 auto; width:2.1rem; height:2.1rem; border-radius:.6rem;
    display:grid; place-items:center; font-size:.95rem;
  }
  .al-ico.ok   { background:#EAF7EE; color:#15803D; }
  .al-ico.info { background:#EEF1FE; color:#1D38D8; }
  .al-ico.bad  { background:#FDECEC; color:#B91C1C; }

  .al-body { flex:1 1 auto; min-width:0; }
  .al-titre { font-size:.84rem; font-weight:700; color:#0B0B0B; }
  .al-titre .ev { font-weight:600; color:#5C5F66; }
  .al-meta { font-size:.72rem; color:#8A8D94; margin-top:.15rem; display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; }
  .al-meta .sep { color:#D9D9DE; }

  .al-diff { margin-top:.5rem; border:1px solid var(--ed-ligne); border-radius:.6rem; overflow:hidden; }
  .al-diff-t { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em;
               background:#F7F7F9; color:#8A8D94; padding:.28rem .6rem; cursor:pointer;
               display:flex; align-items:center; gap:.35rem; }
  .al-diff table { width:100%; border-collapse:collapse; font-size:.75rem; }
  .al-diff td { padding:.28rem .6rem; border-top:1px solid #F2F2F5; vertical-align:top; }
  .al-diff td.c { font-family:ui-monospace,monospace; color:#5C5F66; width:32%; font-size:.72rem; }
  .al-diff td.a { color:#B91C1C; text-decoration:line-through; text-decoration-color:#E5A5A5; }
  .al-diff td.n { color:#15803D; font-weight:600; }

  .al-empty { text-align:center; padding:3rem 1.5rem; color:#8A8D94; }
  .al-empty i { font-size:2.2rem; }
</style>
@endsection

@section('content')
<section class="px-6 pb-3 pt-5">

  <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
    <div>
      <h1 class="text-[1.35rem] font-extrabold tracking-tight text-encre leading-tight">Journal d'activité</h1>
      <p class="text-[0.82rem] text-encre-muted mt-1">
        Qui a créé, modifié ou supprimé quoi, et quand — avec le détail des valeurs.
      </p>
    </div>
    <a href="{{ route('home') }}" class="ed-btn ed-btn-secondary ed-btn-sm">
      <i class="ri-arrow-left-line"></i>Tableau de bord
    </a>
  </div>

  {{-- Compteurs par événement, cliquables comme filtres --}}
  <div class="al-kpi">
    <a href="{{ route('journal-activite.index') }}"
       class="al-chip {{ !$event ? 'on' : '' }}">
      Tout <b>{{ number_format($resume->sum(), 0, ',', ' ') }}</b>
    </a>
    @foreach ($evenements as $cle => $info)
      @if (isset($resume[$cle]))
        <a href="{{ route('journal-activite.index', ['event' => $cle]) }}"
           class="al-chip {{ $event === $cle ? 'on' : '' }}">
          {{ $info[0] }} <b>{{ number_format($resume[$cle], 0, ',', ' ') }}</b>
        </a>
      @endif
    @endforeach
  </div>

  <div class="al-card">
    {{-- Filtres --}}
    <form method="GET" action="{{ route('journal-activite.index') }}" class="al-filtres">
      <div class="row-g">
        <div>
          <label class="al-lbl">Utilisateur</label>
          <select name="user" class="form-select form-select-sm">
            <option value="">Tous</option>
            @foreach ($utilisateurs as $u)
              <option value="{{ $u->id }}" @selected($userId === (int) $u->id)>{{ $u->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="al-lbl">Événement</label>
          <select name="event" class="form-select form-select-sm">
            <option value="">Tous</option>
            @foreach ($evenements as $cle => $info)
              <option value="{{ $cle }}" @selected($event === $cle)>{{ $info[0] }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="al-lbl">Objet</label>
          <select name="modele" class="form-select form-select-sm">
            <option value="">Tous</option>
            @foreach ($modeles as $cle => $libelle)
              <option value="{{ $cle }}" @selected($modele === $cle)>{{ $libelle }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="al-lbl">Du</label>
          <input type="date" name="du" value="{{ $du }}" class="form-control form-control-sm">
        </div>
        <div>
          <label class="al-lbl">Au</label>
          <input type="date" name="au" value="{{ $au }}" class="form-control form-control-sm">
        </div>
        <div>
          <label class="al-lbl">Recherche</label>
          <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm"
                 placeholder="matricule, URL…">
        </div>
      </div>
      <div class="flex gap-2 mt-3">
        <button class="btn btn-primary btn-sm"><i class="ri-filter-3-line me-1"></i>Filtrer</button>
        <a href="{{ route('journal-activite.index') }}" class="btn btn-outline-secondary btn-sm">Réinitialiser</a>
      </div>
    </form>

    {{-- Flux --}}
    @forelse ($audits as $a)
      <div class="al-item">
        <div class="al-ico {{ $a->evenement_ton }}">
          <i class="ri-{{ $a->icone }}"></i>
        </div>
        <div class="al-body">
          <div class="al-titre">
            <span class="ev">{{ $a->evenement_libelle }}</span>
            — {{ $a->modele_libelle }}
            @if ($a->auditable_id)
              <span class="text-encre-faint">#</span>{{ $a->auditable_id }}
            @endif
          </div>
          <div class="al-meta">
            <span><i class="ri-user-3-line"></i> {{ $a->user_nom ?: 'Système' }}</span>
            <span class="sep">|</span>
            <span><i class="ri-time-line"></i> {{ $a->moment_lisible }}</span>
            @if ($a->ip_address)
              <span class="sep">|</span>
              <span><i class="ri-computer-line"></i> {{ $a->ip_address }}</span>
            @endif
            @if ($a->url)
              <span class="sep">|</span>
              <span class="truncate max-w-[24rem]" title="{{ $a->url }}">{{ $a->url }}</span>
            @endif
          </div>

          @if (count($a->diff))
            <details class="al-diff">
              <summary class="al-diff-t">
                <i class="ri-arrow-right-s-line"></i>
                {{ count($a->diff) }} champ(s) modifié(s)
              </summary>
              <table>
                @foreach ($a->diff as $ch)
                  <tr>
                    <td class="c">{{ $ch['champ'] }}</td>
                    <td class="a">{{ $ch['avant'] }}</td>
                    <td class="n">{{ $ch['apres'] }}</td>
                  </tr>
                @endforeach
              </table>
            </details>
          @endif
        </div>
      </div>
    @empty
      <div class="al-empty">
        <i class="ri-file-list-3-line"></i>
        <p class="mt-2 mb-0">Aucune activité ne correspond à ces filtres.</p>
      </div>
    @endforelse
  </div>

  <div class="mt-4">@include('partials.pagination', ['paginateur' => $audits])</div>

</section>
@endsection
