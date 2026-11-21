@extends('layout')
@section('title', 'À propos')

@section('content')
<section class="section">
<div class="container-fluid">

  <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <div>
      <h1 class="ed-page-title mb-0">À propos</h1>
      <p class="ed-page-sub mb-0">
        Les coordonnées et les constantes de l'établissement, modifiables directement ici.
      </p>
    </div>
    @can('Modifier les paramètres système')
      <span class="ed-chip ms-auto"><i class="ri-edit-line me-1"></i>Mode édition</span>
    @else
      <span class="ed-chip ed-chip-muted ms-auto"><i class="ri-eye-line me-1"></i>Lecture seule</span>
    @endcan
  </div>

  @if (session('succes'))
    <div class="alert alert-success d-flex align-items-center gap-2">
      <i class="ri-checkbox-circle-line"></i>{{ session('succes') }}
    </div>
  @endif
  @if (session('warning'))
    <div class="alert alert-warning d-flex align-items-center gap-2">
      <i class="ri-error-warning-line"></i>{{ session('warning') }}
    </div>
  @endif
  @if (session('echec'))
    <div class="alert alert-danger d-flex align-items-center gap-2">
      <i class="ri-error-warning-line"></i>{{ session('echec') }}
    </div>
  @endif

  @php($peutModifier = auth()->user()?->can('Modifier les paramètres système'))

  @if (empty($groupes))
    {{-- La table parametres_systeme n'existe pas encore, mais les
         coordonnées existent dans `ufr` : on les affiche quand même. --}}
    <div class="ed-card mb-3">
      <div class="ed-card-head">
        <h2 class="ed-card-title mb-0"><i class="ri-building-2-line me-1"></i>Établissement</h2>
      </div>
      <div class="row g-3 p-3">
        @foreach ([
          ['Nom', \App\Support\Parametres::get('etablissement.nom')],
          ['Adresse', \App\Support\Parametres::get('etablissement.adresse')],
          ['Ville', \App\Support\Parametres::get('etablissement.ville')],
          ['Téléphone', \App\Support\Parametres::get('contact.telephone_1')],
          ['E-mail', \App\Support\Parametres::get('contact.email_1')],
          ['Site web', \App\Support\Parametres::get('contact.site_web')],
          ['Directeur', \App\Support\Parametres::get('direction.recteur')],
          ['RH', \App\Support\Parametres::get('direction.rh')],
        ] as [$libelle, $valeur])
          <div class="col-lg-6">
            <div class="form-label fw-semibold mb-1">{{ $libelle }}</div>
            <div class="form-control bg-light">{{ $valeur ?: '—' }}</div>
          </div>
        @endforeach
      </div>
      <div class="p-3 pt-0">
        <div class="alert alert-info mb-0 d-flex align-items-start gap-2">
          <i class="ri-information-line mt-1"></i>
          <div>
            Ces valeurs sont lues dans la table <code>ufr</code>. Pour les rendre
            modifiables depuis cette page, jouez la migration :
            <code>php artisan migrate</code> (elle s'ajoute aux tables existantes,
            elle ne les recrée pas).
          </div>
        </div>
      </div>
    </div>
  @else
    <form action="{{ route('parametres-systeme.update') }}" method="POST" id="formParametres">
      @csrf
      @method('PATCH')

      @foreach ($groupes as $code => $lignes)
        <div class="ed-card mb-3">
          <div class="ed-card-head">
            <h2 class="ed-card-title mb-0">
              <i class="ri-{{ $code === 'contact' ? 'contacts-line' : ($code === 'direction' ? 'user-star-line' : ($code === 'academique' ? 'graduation-cap-line' : 'building-2-line')) }} me-1"></i>
              {{ \App\Support\Parametres::libelleGroupe($code) }}
              <span class="ed-chip ms-1">{{ $lignes->count() }}</span>
            </h2>
          </div>

          <div class="row g-3 p-3">
            @foreach ($lignes as $parametre)
              <div class="col-lg-6">
                <label for="p-{{ $parametre->cle }}" class="form-label fw-semibold mb-1">
                  {{ $parametre->libelle }}
                </label>
                <input
                  type="{{ $parametre->type === 'email' ? 'email' : ($parametre->type === 'tel' ? 'tel' : ($parametre->type === 'url' ? 'url' : ($parametre->type === 'nombre' ? 'number' : 'text'))) }}"
                  class="form-control @error('parametres.' . $parametre->cle) is-invalid @enderror"
                  id="p-{{ $parametre->cle }}"
                  name="parametres[{{ $parametre->cle }}]"
                  value="{{ old('parametres.' . $parametre->cle, $parametre->valeur) }}"
                  @readonly(! $peutModifier)
                  @if ($parametre->type === 'nombre') step="any" @endif
                >
                @error('parametres.' . $parametre->cle)
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @if ($parametre->aide)
                  <div class="form-text">{{ $parametre->aide }}</div>
                @endif
              </div>
            @endforeach
          </div>
        </div>
      @endforeach

      @if ($peutModifier)
        <div class="d-flex align-items-center gap-2 mb-4">
          <button type="submit" class="btn btn-primary">
            <i class="ri-save-3-line me-1"></i>Enregistrer les paramètres
          </button>
          <a href="{{ route('about-us') }}" class="btn btn-outline-secondary">Annuler</a>
          <span class="ed-cmdk-tip ms-auto">
            Ces valeurs alimentent les en-têtes et pieds de page des documents imprimés.
          </span>
        </div>
      @endif
    </form>
  @endif

  <div class="ed-card">
    <div class="ed-card-head">
      <h2 class="ed-card-title mb-0"><i class="ri-information-line me-1"></i>Application</h2>
    </div>
    <div class="row g-3 p-3">
      @foreach ([
        ['EduLMD', 'Solution de gestion de la scolarité LMD'],
        ['Laravel ' . app()->version(), 'Version du framework'],
        ['PHP ' . PHP_VERSION, 'Version de PHP'],
        ['LMD Web Solutions', 'Éditeur'],
      ] as [$titre, $sous])
        <div class="col-lg-3 col-md-6">
          <div class="p-3 border rounded h-100">
            <div class="fw-bolder">{{ $titre }}</div>
            <div class="form-text mb-0">{{ $sous }}</div>
          </div>
        </div>
      @endforeach
    </div>
  </div>

</div>
</section>
@endsection
