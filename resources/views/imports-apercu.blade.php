@extends('layout')
@section('title')
Aperçu de l'import
@endsection

@section('css')
<style>
  .imp-card { background:#fff; border:1px solid var(--ed-ligne); border-radius:1rem; box-shadow:var(--ed-ombre); }
  .imp-etapes { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1.25rem; }
  .imp-etape {
    display:flex; align-items:center; gap:.45rem; font-size:.78rem; font-weight:600; color:#8A8D94;
    background:#F7F7F9; border:1px solid var(--ed-ligne); border-radius:999px; padding:.35rem .75rem;
  }
  .imp-etape .n {
    width:1.25rem; height:1.25rem; border-radius:999px; display:grid; place-items:center;
    background:#E6E6EA; color:#5C5F66; font-size:.68rem; font-weight:800;
  }
  .imp-etape.done { background:#EAF7EE; border-color:#BFE3C9; color:#15803D; }
  .imp-etape.done .n { background:#15803D; color:#fff; }
  .imp-etape.on { background:#FCF5F6; border-color:#C9A3AC; color:#822A40; }
  .imp-etape.on .n { background:#822A40; color:#fff; }

  .imp-sum { display:grid; grid-template-columns:repeat(auto-fit,minmax(9rem,1fr)); gap:.6rem; }
  .imp-sum > div { border:1px solid var(--ed-ligne); border-radius:.7rem; padding:.7rem .8rem; background:#fff; }
  .imp-sum .v { font-size:1.4rem; font-weight:800; line-height:1.1; letter-spacing:-.02em; }
  .imp-sum .l { font-size:.72rem; font-weight:600; color:#5C5F66; margin-top:.15rem; }
  .imp-sum .ok .v { color:#15803D; }
  .imp-sum .warn .v { color:#B45309; }
  .imp-sum .bad .v { color:#B91C1C; }

  .imp-head { display:flex; align-items:center; gap:.5rem; padding:.9rem 1.1rem; border-bottom:1px solid var(--ed-ligne); }
  .imp-head h2 { margin:0; font-size:.92rem; font-weight:700; }
  .imp-head .n {
    margin-left:auto; font-size:.7rem; font-weight:700; background:#F4F4F6; color:#5C5F66;
    padding:.12rem .45rem; border-radius:99px;
  }
  .imp-ligne {
    display:flex; gap:.7rem; padding:.55rem 1.1rem; border-bottom:1px solid #F5F5F7;
    font-size:.78rem; align-items:flex-start;
  }
  .imp-ligne:last-child { border-bottom:0; }
  .imp-ligne .num {
    flex:0 0 auto; font-family:ui-monospace,monospace; font-size:.72rem; color:#8A8D94;
    min-width:3.2rem; padding-top:.05rem;
  }
  .imp-ligne .qui { flex:0 0 auto; min-width:9rem; font-weight:600; color:#0B0B0B; }
  .imp-ligne .quoi { flex:1 1 auto; color:#B91C1C; }
  .imp-ligne.warn .quoi { color:#B45309; }
  .imp-ligne.ok .quoi { color:#15803D; }
  .imp-vide { padding:1.5rem 1.1rem; text-align:center; font-size:.82rem; color:#8A8D94; }
  .imp-bascule { display:flex; gap:.3rem; margin-left:auto; }
  .imp-bascule button {
    font-size:.7rem; font-weight:700; padding:.15rem .5rem; border-radius:99px;
    border:1px solid var(--ed-ligne); background:#fff; color:#5C5F66; cursor:pointer;
  }
  .imp-bascule button.on { background:#822A40; border-color:#822A40; color:#fff; }
</style>
@endsection

@section('content')
<section class="px-6 pb-3 pt-5">

  <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
    <div>
      <h1 class="text-[1.35rem] font-extrabold tracking-tight text-encre leading-tight">Aperçu de l'import</h1>
      <p class="text-[0.82rem] text-encre-muted mt-1">{{ $typeLibelle }} — rien n'a encore été enregistré.</p>
    </div>
    <a href="{{ route('imports.index') }}" class="ed-btn ed-btn-secondary ed-btn-sm">
      <i class="ri-arrow-left-line"></i>Changer de fichier
    </a>
  </div>

  <div class="imp-etapes">
    <div class="imp-etape done"><span class="n"><i class="ri-check-line"></i></span>Fichier analysé</div>
    <div class="imp-etape on"><span class="n">2</span>Prévisualiser le rapport</div>
    <div class="imp-etape"><span class="n">3</span>Confirmer l'import</div>
  </div>

  {{-- Résumé chiffré --}}
  <div class="imp-sum mb-4">
    <div class="ok">
      <div class="v">{{ count($rapport['valides']) }}</div>
      <div class="l">Lignes importables</div>
    </div>
    <div class="bad">
      <div class="v">{{ count($rapport['erreurs']) }}</div>
      <div class="l">Lignes en erreur</div>
    </div>
    <div class="warn">
      <div class="v">{{ count($rapport['avertissements']) }}</div>
      <div class="l">Avertissements</div>
    </div>
    <div>
      <div class="v">{{ $rapport['total'] }}</div>
      <div class="l">Lignes lues</div>
    </div>
    <div>
      <div class="v">{{ count($rapport['entetes']) }}</div>
      <div class="l">Colonnes</div>
    </div>
  </div>

  {{-- Détail des erreurs --}}
  @if (count($rapport['erreurs']))
    <div class="imp-card mb-4">
      <div class="imp-head">
        <i class="ri-error-warning-line text-[#B91C1C]"></i>
        <h2>Lignes en erreur — elles seront ignorées</h2>
        <span class="n">{{ count($rapport['erreurs']) }}</span>
      </div>
      @foreach ($rapport['erreurs'] as $e)
        <div class="imp-ligne">
          <span class="num">L. {{ $e['ligne'] }}</span>
          <span class="qui">{{ $e['matricule'] ?: '—' }}{{ $e['nom'] ? ' · ' . $e['nom'] : '' }}</span>
          <span class="quoi">{{ implode(' ', $e['motifs']) }}</span>
        </div>
      @endforeach
    </div>
  @endif

  {{-- Avertissements --}}
  @if (count($rapport['avertissements']))
    <div class="imp-card mb-4">
      <div class="imp-head">
        <i class="ri-question-line text-[#B45309]"></i>
        <h2>Avertissements — lignes importées malgré tout</h2>
        <span class="n">{{ count($rapport['avertissements']) }}</span>
      </div>
      @foreach ($rapport['avertissements'] as $a)
        <div class="imp-ligne warn">
          <span class="num">L. {{ $a['ligne'] }}</span>
          <span class="qui">{{ $a['matricule'] ?: '—' }}{{ $a['nom'] ? ' · ' . $a['nom'] : '' }}</span>
          <span class="quoi">{{ implode(' ', $a['motifs']) }}</span>
        </div>
      @endforeach
    </div>
  @endif

  {{-- Aperçu des lignes valides --}}
  <div class="imp-card mb-4">
    <div class="imp-head">
      <i class="ri-checkbox-circle-line text-[#15803D]"></i>
      <h2>Lignes prêtes à être importées</h2>
      <span class="n">{{ count($rapport['valides']) }}</span>
      @if (count($rapport['valides']) > 20)
        <div class="imp-bascule" id="impBascule">
          <button type="button" data-mode="20" class="on">20 premières</button>
          <button type="button" data-mode="all">Tout voir</button>
        </div>
      @endif
    </div>

    @forelse (array_slice($rapport['valides'], 0, 20) as $i => $l)
      <div class="imp-ligne ok" data-apercu="valide">
        <span class="num">L. {{ $l['_ligne'] ?? ($i + 2) }}</span>
        <span class="qui">{{ $l['matricule'] ?? ($l['code'] ?? '—') }}</span>
        <span class="quoi" style="color:#5C5F66;">
          @if (isset($l['code']))
            {{ $l['libelle'] }}
          @else
            {{ trim(($l['nom'] ?? '') . ' ' . ($l['prenoms'] ?? '')) }}
            @if (!empty($l['anneeEtude'])) · Année {{ $l['anneeEtude'] }} @endif
            @if (!empty($l['montant'])) · {{ number_format($l['montant'], 0, ',', ' ') }} F @endif
          @endif
        </span>
      </div>
    @empty
      <div class="imp-vide">Aucune ligne valide dans ce fichier.</div>
    @endforelse

    @if (count($rapport['valides']) > 20)
      <div id="impReste" hidden>
        @foreach (array_slice($rapport['valides'], 20) as $i => $l)
          <div class="imp-ligne ok" data-apercu="valide">
            <span class="num">L. {{ $l['_ligne'] ?? ($i + 22) }}</span>
            <span class="qui">{{ $l['matricule'] ?? ($l['code'] ?? '—') }}</span>
            <span class="quoi" style="color:#5C5F66;">
              @if (isset($l['code']))
                {{ $l['libelle'] }}
              @else
                {{ trim(($l['nom'] ?? '') . ' ' . ($l['prenoms'] ?? '')) }}
                @if (!empty($l['anneeEtude'])) · Année {{ $l['anneeEtude'] }} @endif
                @if (!empty($l['montant'])) · {{ number_format($l['montant'], 0, ',', ' ') }} F @endif
              @endif
            </span>
          </div>
        @endforeach
      </div>
    @endif
  </div>

  {{-- Confirmation --}}
  <div class="imp-card p-4">
    <div class="flex flex-wrap items-center gap-3">
      @if (count($rapport['valides']))
        <form method="POST" action="{{ route('imports.confirmer') }}">
          @csrf
          <button class="btn btn-primary">
            <i class="ri-check-double-line me-1"></i>
            Importer {{ count($rapport['valides']) }} ligne(s)
          </button>
        </form>
        <span class="text-[0.78rem] text-encre-muted">
          Les {{ count($rapport['erreurs']) }} ligne(s) en erreur seront ignorées.
        </span>
      @else
        <span class="text-[0.85rem] text-[#B91C1C] font-semibold">
          Aucune ligne importable : corrigez le fichier puis recommencez.
        </span>
        <a href="{{ route('imports.index') }}" class="btn btn-outline-secondary btn-sm">Revenir</a>
      @endif
    </div>
  </div>

</section>
@endsection

@section('js')
<script>
(function () {
  var bascule = document.getElementById('impBascule');
  var reste = document.getElementById('impReste');
  if (!bascule || !reste) return;

  bascule.addEventListener('click', function (e) {
    var b = e.target.closest('button[data-mode]');
    if (!b) return;
    var tout = b.getAttribute('data-mode') === 'all';

    reste.hidden = !tout;
    bascule.querySelectorAll('button').forEach(function (x) { x.classList.remove('on'); });
    b.classList.add('on');
  });
})();
</script>
@endsection
