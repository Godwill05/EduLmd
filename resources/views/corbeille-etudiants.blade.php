@extends('layout')
@section('title', 'Corbeille des étudiants')

@section('content')
<section class="section">
<div class="container-fluid">

  <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <div>
      <h1 class="ed-page-title mb-0">Corbeille des étudiants</h1>
      <p class="ed-page-sub mb-0">
        Les fiches retirées conservent leur parcours complet et peuvent être restaurées.
      </p>
    </div>
    <a href="{{ route('qualite-donnees.index') }}" class="btn btn-outline-secondary ms-auto">
      <i class="ri-arrow-left-line me-1"></i>Qualité des données
    </a>
  </div>

  @if (session('succes'))
    <div class="alert alert-success d-flex align-items-center gap-2">
      <i class="ri-checkbox-circle-line"></i>{{ session('succes') }}
    </div>
  @endif
  @if (session('echec'))
    <div class="alert alert-danger d-flex align-items-center gap-2">
      <i class="ri-error-warning-line"></i>{{ session('echec') }}
    </div>
  @endif

  <div class="ed-card">
    <div class="ed-card-head">
      <h2 class="ed-card-title mb-0">
        <i class="ri-delete-bin-6-line me-1"></i>Fiches en corbeille
        <span class="ed-chip ms-1">{{ $total }}</span>
      </h2>
    </div>

    @if ($entrees->isEmpty())
      <div class="ed-eval-empty">
        <i class="ri-checkbox-circle-line" style="color:#15803D"></i>
        <p>La corbeille est vide.</p>
        <span class="ed-cmdk-tip">Les fiches que vous retirez apparaîtront ici.</span>
      </div>
    @else
      <div class="table-responsive">
        <table class="table align-middle mb-0 ed-corbeille-table">
          <thead>
            <tr>
              <th>Matricule</th>
              <th>Étudiant</th>
              <th>Motif</th>
              <th class="text-center">Parcours conservé</th>
              <th>Retiré le</th>
              <th class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($entrees as $entree)
              <tr>
                <td><span class="ed-mat">{{ $entree->MATRICULE }}</span></td>
                <td class="fw-semibold">{{ $entree->nom_complet }}</td>
                <td>
                  @if ($entree->type === 'doublon')
                    <span class="ed-chip">doublon</span>
                  @else
                    <span class="ed-chip ed-chip-muted">manuel</span>
                  @endif
                  @if ($entree->motif)
                    <div class="ed-corbeille-motif">{{ $entree->motif }}</div>
                  @endif
                </td>
                <td class="text-center ed-corbeille-parcours">
                  <span title="Inscriptions"><i class="ri-file-list-3-line"></i> {{ $entree->nb_inscriptions }}</span>
                  <span title="Paiements"><i class="ri-hand-coin-line"></i> {{ $entree->nb_paiements }}</span>
                  <span title="Notes"><i class="ri-edit-2-line"></i> {{ count($entree->donnees['obtenir'] ?? []) }}</span>
                </td>
                <td class="ed-corbeille-date">
                  {{ $entree->created_at?->format('d/m/Y à H:i') }}
                  @if ($entree->supprime_par)
                    <div class="ed-corbeille-motif">par {{ $entree->supprime_par }}</div>
                  @endif
                </td>
                <td class="text-end">
                  <form method="POST" action="{{ route('corbeille.restaurer', ['id' => $entree->id]) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-primary"
                            data-confirm="Restaurer la fiche {{ $entree->MATRICULE }} avec ses inscriptions, notes et paiements ?">
                      <i class="ri-arrow-go-back-line me-1"></i>Restaurer
                    </button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="p-3">
        @include('partials.pagination', ['paginateur' => $entrees])
      </div>
    @endif
  </div>
</div>
</section>
@endsection
