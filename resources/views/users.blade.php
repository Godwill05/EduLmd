@extends('layout')
@section('title')
Utilisateurs
@endsection
@section('content')
<section class="section">
    <div class="container-fluid">
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
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
