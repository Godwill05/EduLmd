@extends('layout')
@section('title', 'Permissions du rôle ' . $role->name)

@section('content')
<section class="section">
<div class="container-fluid">

  <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <div>
      <h1 class="ed-page-title mb-0">{{ $role->name }}</h1>
      <p class="ed-page-sub mb-0">
        Cochez ce que ce rôle autorise. Les utilisateurs du rôle héritent
        immédiatement de ces permissions.
      </p>
    </div>
    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary ms-auto">
      <i class="ri-arrow-left-line me-1"></i>Rôles
    </a>
  </div>

  @if (session('echec'))
    <div class="alert alert-danger d-flex align-items-center gap-2">
      <i class="ri-error-warning-line"></i>{{ session('echec') }}
    </div>
  @endif

  <form action="{{ route('roles.update', $role) }}" method="POST">
    @csrf @method('PUT')

    <div class="ed-card mb-3">
      <div class="ed-card-head">
        <h2 class="ed-card-title mb-0"><i class="ri-stairs-line me-1"></i>Niveau</h2>
      </div>
      <div class="row g-3 p-3">
        <div class="col-lg-3">
          <label for="order" class="form-label fw-semibold">Niveau du rôle</label>
          <input type="number" class="form-control @error('order') is-invalid @enderror"
                 id="order" name="order" min="{{ $monNiveau + 1 }}" max="100"
                 value="{{ old('order', $role->niveau()) }}">
          @error('order')<div class="invalid-feedback">{{ $message }}</div>@enderror
          <div class="form-text">
            Votre niveau est {{ $monNiveau }} : vous ne pouvez fixer qu'un niveau supérieur.
          </div>
        </div>
      </div>
    </div>

    <div class="ed-card mb-3">
      <div class="ed-card-head d-flex align-items-center">
        <h2 class="ed-card-title mb-0">
          <i class="ri-key-2-line me-1"></i>Permissions
          <span class="ed-chip ms-1">{{ count($accordees) }}/{{ $permissions->count() }}</span>
        </h2>
        <div class="form-check form-switch ms-auto mb-0">
          <input class="form-check-input" type="checkbox" id="toutCocher">
          <label class="form-check-label small" for="toutCocher">Tout cocher</label>
        </div>
      </div>

      @php
        $groupes = $permissions->groupBy(fn ($p) => $p->type ?: 'général');
        $icones = [
          'domaine' => 'ri-price-tag-3-line', 'utilisateurs' => 'ri-group-line',
          'inscriptions' => 'ri-file-list-3-line', 'etudiants' => 'ri-user-3-line',
          'enseignants' => 'ri-user-star-line', 'universites' => 'ri-building-line',
          'etablissements' => 'ri-building-4-line', 'général' => 'ri-shield-keyhole-line',
        ];
      @endphp

      <div class="row g-3 p-3">
        @foreach ($groupes as $type => $liste)
          <div class="col-lg-6">
            <div class="border rounded p-3 h-100">
              <div class="fw-bolder mb-2">
                <i class="{{ $icones[$type] ?? 'ri-shield-keyhole-line' }} me-1"></i>
                {{ ucfirst($type) }}
              </div>
              @foreach ($liste as $permission)
                <div class="form-check form-switch">
                  <input class="form-check-input perm"
                         type="checkbox"
                         name="permissions[]"
                         value="{{ $permission->name }}"
                         id="perm-{{ $permission->id }}"
                         @checked(in_array($permission->name, $accordees, true))>
                  <label class="form-check-label" for="perm-{{ $permission->id }}">
                    {{ $permission->name }}
                  </label>
                </div>
              @endforeach
            </div>
          </div>
        @endforeach
      </div>
    </div>

    <div class="d-flex gap-2 mb-4">
      <button type="submit" class="btn btn-primary"><i class="ri-save-3-line me-1"></i>Enregistrer</button>
      <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Annuler</a>
    </div>
  </form>

</div>
</section>
@endsection

@section('js')
<script>
  document.getElementById('toutCocher').addEventListener('change', function () {
    document.querySelectorAll('input.perm').forEach(function (c) { c.checked = this.checked; }, this);
  });
</script>
@endsection
