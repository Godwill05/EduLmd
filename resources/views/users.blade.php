@extends('layout')
@section('title')
Utilisateurs
@endsection
@section('content')
<section class="section">
    <div class="container-fluid">

        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
          <div>
            <h1 class="ed-page-title mb-0">Utilisateurs</h1>
            <p class="ed-page-sub mb-0">
              Chaque compte reçoit un rôle ; le rôle porte les permissions.
              Vous ne pouvez attribuer qu'un rôle moins élevé que le vôtre.
            </p>
          </div>
          <a href="{{ route('roles.index') }}" class="btn btn-outline-primary ms-auto">
            <i class="ri-shield-user-line me-1"></i>Gérer les rôles
          </a>
        </div>
        @if (session()->has('succes'))
        <div class="row">
            <div class="alert alert-success">
                {{session('succes')}}
            </div>
        </div>
        @endif
        @if (session()->has('warning'))
        <div class="row">
            <div class="alert alert-warning">
                {{session('warning')}}
            </div>
        </div>
        @endif
        @if (session()->has('echec'))
        <div class="row">
            <div class="alert alert-danger">
                {{session('echec')}}
            </div>
        </div>
        @endif
        <div class="row">
            <div class="card p-3">
                <h5 class="fw-bold">Nouvel utilisateur</h5>
                <form action="{{ route('utilisateurs.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-lg-4">
                            <label for="login" class="form-label">Login <span class="text-danger">*</span></label>
                            <input required type="text" name="login" id="login" class="form-control">
                        </div>
                        <div class="col-lg-4">
                            <label for="lastname" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input required type="text" name="lastname" id="lastname" class="form-control">
                        </div>
                        <div class="col-lg-4">
                            <label for="firstname" class="form-label">Prénom(s) <span class="text-danger">*</span></label>
                            <input required type="text" name="firstname" id="firstname" class="form-control">
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-lg-4">
                            <label for="telephone" class="form-label">Téléphone <span class="text-danger">*</span></label>
                            <input required type="text" name="telephone" id="telephone" class="form-control">
                        </div>
                        <div class="col-lg-4">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input required type="text" name="email" id="email" class="form-control">
                        </div>
                        <div class="col-lg-4">
                            <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                            <input required type="password" name="password" id="password" class="form-control">
                        </div>
                        <div class="col-lg-4">
                            <label for="role_id" class="form-label">Rôle</label>
                            <select name="role_id" id="role_id" class="form-select">
                                <option value="">— Aucun rôle (aucun accès) —</option>
                                @foreach ($roles as $role)
                                    @if (in_array($role->id, $roleAttribuables, true))
                                        <option value="{{ $role->id }}">{{ $role->name }} (niveau {{ $role->niveau() }})</option>
                                    @endif
                                @endforeach
                            </select>
                            <div class="form-text">Un compte sans rôle ne peut rien consulter.</div>
                        </div>
                        <div class="col-lg-12 mt-4 text-end">
                            <button class="btn-block btn btn-primary">Enregistrer</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="row">
            <div class="card p-2">
                <table class="table table-striped datatable">
                    <thead>
                        <th>Login</th>
                        <th>Nom</th>
                        <th>Prénom(s)</th>
                        <th>Téléphone</th>
                        <th>Rôle</th>
                        <th>Actions</th>
                    </thead>
                    <tbody>
                        @foreach ($utilisateurs as $user)
                            <tr>
                                <td>{{$user->login}}</td>
                                <td>{{$user->lastname}}</td>
                                <td>{{$user->firstname}}</td>
                                <td>{{$user->telephone}}</td>
                                <td>
                                    <form action="{{ route('utilisateurs.destroy', $user->id) }}" method="post">
                                        @csrf
                                        @method('DELETE')
                                        <a href="{{ route('utilisateurs.permissions',$user->id) }}" title="Gérer les rôles" class="btn" type="button"><i class=" ri-equalizer-line text-black"></i></a>

                                        <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#exampleModal{{$loop->iteration}}" title="Modifier l'utilisateur"><i class=" ri-pencil-line text-secondary"></i></button>

                                        <button type="submit" onclick="return confirm('Etes vous sûr de vouloir supprimer {{$user->login}}?')" class="btn" title="Supprimer l'utilisateur"><i class=" ri-delete-bin-line text-danger"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <div class="modal fade" id="exampleModal{{$loop->iteration}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Modifier les informations d'un utilisateur</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" action="{{ route('utilisateurs.update', $user->id) }}" id="modalUpd{{$loop->iteration}}">
                                        @csrf
                                        @method('PATCH')
                                        <div class="mb-3">
                                            <label for="login" class="col-form-label">Login:</label>
                                            <input required type="text" class="form-control" id="login" name="login" value="{{$user->login}}">
                                        </div>
                                        <div class="mb-3">
                                            <label for="lastname" class="col-form-label">Nom:</label>
                                            <input required type="text" class="form-control" id="lastname" name="lastname" value="{{$user->lastname}}">
                                        </div>
                                        <div class="mb-3">
                                            <label for="firstname" class="col-form-label">Prénom(s):</label>
                                            <input required type="text" class="form-control" id="firstname" name="firstname" value="{{$user->firstname}}">
                                        </div>
                                        <div class="mb-3">
                                            <label for="telephone" class="col-form-label">Téléphone:</label>
                                            <input required type="text" class="form-control" id="telephone" name="telephone" value="{{$user->telephone}}">
                                        </div>
                                        <div class="mb-3">
                                            <label for="email" class="col-form-label">Email:</label>
                                            <input required type="text" class="form-control" id="email" name="email" value="{{$user->email}}">
                                        </div>

                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <div class="mt-3 text-end">
                                        <a onclick="$('#modalUpd{{$loop->iteration}}').submit();" class="btn btn-primary">Modifier</a>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                    </div>
                                </div>
                                </div>
                            </div>
                            </div>

                            <div class="modal fade" id="roleModal{{$loop->iteration}}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('utilisateurs.role', $user->id) }}" method="POST">
                                            @csrf
                                            <div class="modal-header">
                                                <h1 class="modal-title fs-5">Rôle de {{ $user->fullname() }}</h1>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                            </div>
                                            <div class="modal-body">
                                                @php($niveauCible = \App\Support\Hierarchie::niveau($user))
                                                @if ($niveauCible <= $monNiveau)
                                                    <div class="alert alert-warning mb-0">
                                                        Ce compte est au niveau {{ $niveauCible }}, au moins aussi élevé que le vôtre
                                                        ({{ $monNiveau }}) : seul un utilisateur de niveau supérieur peut changer son rôle.
                                                    </div>
                                                @else
                                                    <label for="role-{{$user->id}}" class="form-label fw-semibold">Rôle attribué</label>
                                                    <select name="role_id" id="role-{{$user->id}}" class="form-select">
                                                        <option value="">— Retirer le rôle —</option>
                                                        @foreach ($roles as $role)
                                                            @if (in_array($role->id, $roleAttribuables, true))
                                                                <option value="{{ $role->id }}"
                                                                    @selected($user->roles->contains('id', $role->id))>
                                                                    {{ $role->name }} (niveau {{ $role->niveau() }})
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                    <div class="form-text mt-2">
                                                        Le remplacement retire le rôle principal actuel. Les permissions
                                                        individuelles accordées à ce compte sont conservées.
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                                @if ($niveauCible > $monNiveau)
                                                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                                                @endif
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
