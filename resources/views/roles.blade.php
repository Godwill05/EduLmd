@extends('layout')
@section('title', 'Rôles et niveaux')

@section('content')
<section class="section">
<div class="container-fluid">

  <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <div>
      <h1 class="ed-page-title mb-0">Rôles et niveaux</h1>
      <p class="ed-page-sub mb-0">
        Plus le niveau est petit, plus le rôle est élevé. Un rôle ne peut être
        attribué que par un utilisateur d'un niveau strictement supérieur.
      </p>
    </div>
    <span class="ed-chip ms-auto"><i class="ri-stairs-line me-1"></i>Votre niveau : {{ $monNiveau }}</span>
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

  <div class="ed-card mb-3">
    <div class="ed-card-head">
      <h2 class="ed-card-title mb-0">
        <i class="ri-shield-user-line me-1"></i>Rôles
        <span class="ed-chip ms-1">{{ $roles->count() }}</span>
      </h2>
    </div>

    @if ($roles->isEmpty())
      <div class="ed-eval-empty">
        <i class="ri-shield-line"></i>
        <p>Aucun rôle défini.</p>
        <span class="ed-cmdk-tip">Créez le premier rôle dans le formulaire ci-dessous.</span>
      </div>
    @else
      <div class="table-responsive">
        <table class="table align-middle mb-0 ed-corbeille-table">
          <thead>
            <tr>
              <th style="width:90px">Niveau</th>
              <th>Rôle</th>
              <th class="text-center">Permissions</th>
              <th class="text-center">Utilisateurs</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($roles as $role)
              <tr>
                <td>
                  <span class="ed-chip {{ $role->niveau() <= 2 ? '' : 'ed-chip-muted' }}">
                    {{ $role->niveau() }}
                  </span>
                </td>
                <td>
                  <span class="fw-semibold">{{ $role->name }}</span>
                  @if ($role->estEleve())
                    <i class="ri-vip-crown-2-line ms-1" title="Rôle élevé"></i>
                  @endif
                  @if ($role->niveau() <= $monNiveau)
                    <div class="ed-corbeille-motif">Au moins aussi élevé que votre niveau : non modifiable.</div>
                  @endif
                </td>
                <td class="text-center">{{ $nbPermissions[$role->id] ?? 0 }}</td>
                <td class="text-center">{{ $effectifs[$role->id] ?? 0 }}</td>
                <td class="text-end">
                  @if ($peutGerer && $role->niveau() > $monNiveau)
                    <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-primary"
                       title="Configurer les permissions">
                      <i class="ri-equalizer-line me-1"></i>Permissions
                    </a>
                    <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Supprimer le rôle {{ $role->name }} ?')">
                      @csrf @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                        <i class="ri-delete-bin-line"></i>
                      </button>
                    </form>
                  @else
                    <span class="text-muted small">—</span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  @if ($peutGerer)
    <div class="ed-card">
      <div class="ed-card-head">
        <h2 class="ed-card-title mb-0"><i class="ri-add-circle-line me-1"></i>Nouveau rôle</h2>
      </div>
      <form action="{{ route('roles.store') }}" method="POST" class="p-3">
        @csrf
        <div class="row g-3">
          <div class="col-lg-5">
            <label for="name" class="form-label fw-semibold">Nom du rôle <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('name') is-invalid @enderror"
                   id="name" name="name" value="{{ old('name') }}"
                   placeholder="Ex. Scolarité, Comptabilité, Enseignant…" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-lg-3">
            <label for="order" class="form-label fw-semibold">Niveau <span class="text-danger">*</span></label>
            <input type="number" class="form-control @error('order') is-invalid @enderror"
                   id="order" name="order" min="{{ max(1, $monNiveau + 1) }}" max="100"
                   value="{{ old('order', min(100, $monNiveau + 1)) }}" required>
            @error('order')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Doit rester supérieur à {{ $monNiveau }}. 1 = le plus élevé.</div>
          </div>
          <div class="col-lg-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary">
              <i class="ri-add-line me-1"></i>Créer le rôle
            </button>
          </div>
        </div>
      </form>
    </div>
  @endif

  <div class="ed-card mt-3">
    <div class="ed-card-head">
      <h2 class="ed-card-title mb-0"><i class="ri-question-line me-1"></i>Où sont stockés les rôles ?</h2>
    </div>
    <div class="p-3">
      <p class="mb-2">
        Les rôles et les permissions sont gérés par le paquet <code>spatie/laravel-permission</code>,
        dans quatre tables standard :
      </p>
      <ul class="mb-2">
        <li><code>roles</code> — un rôle par ligne ; la colonne <code>order</code> porte le niveau.</li>
        <li><code>permissions</code> — une permission par ligne (ex. « Gérer les utilisateurs »).</li>
        <li><code>role_has_permissions</code> — quelles permissions va avec quel rôle.</li>
        <li><code>model_has_roles</code> — quel utilisateur porte quel rôle (<code>model_type</code> = <code>App\Models\User</code>).</li>
      </ul>
      <p class="mb-0">
        Tout est modifiable depuis cette page, mais aussi directement en base
        ou avec <code>php artisan tinker</code> :
        <code>$u-&gt;assignRole('Scolarité')</code>.
      </p>
    </div>
  </div>

</div>
</section>
@endsection
