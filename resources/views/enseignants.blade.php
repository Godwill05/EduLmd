@extends('layout')
@section('title')
Enseignants
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
    @if (session()->has('echec'))
    <div class="row">
        <div class="alert alert-danger">
            {{session('echec')}}
        </div>
    </div>
    @endif
    <div class="row">
        <div class="p-4 card">
            <h5 class="fw-bold">Nouvel enseignant</h5>
            <form action="{{ route('enseignants.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-lg-4 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="nom">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" id="nom" class="form-control" placeholder="Doe">
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="prenom">Prénom(s) <span class="text-danger">*</span></label>
                            <input type="text" name="prenom" id="prenom" class="form-control" placeholder="John">
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="dateNaissance">Date de naissance <span class="text-danger">*</span></label>
                            <input type="date" name="dateNaissance" id="dateNaissance" class="form-control" placeholder="01/01/2001">
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="lieuNaissance">Lieu de naissance <span class="text-danger">*</span></label>
                            <input type="text" name="lieuNaissance" id="lieuNaissance" class="form-control" placeholder="Nowhere">
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="nationalite">Nationalité <span class="text-danger">*</span></label>
                            <select class="form-control form-select" name="nationalite" id="nationalite">
                                <option value="">Sélectionner une nationalité...</option>
                                @foreach (\App\Models\Nationalite::all() as $nationalite)
                                <option value="{{$nationalite->codeNationalite}}">{{$nationalite->libelleNationalite}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="sexe">Sexe <span class="text-danger">*</span></label>
                            <select class="form-control form-select" name="sexe" id="sexe">
                                <option value="">Sélectionner un genre...</option>
                                <option value="M">Masculin</option>
                                <option value="F">Féminin</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="telephone">Téléphone <span class="text-danger">*</span></label>
                            <input type="text" name="telephone" id="telephone" class="form-control" placeholder="+229 90011234">
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                            <input type="text" name="email" id="email" class="form-control" placeholder="nom@example.com">
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label class="form-label" for="typeProfesseur">Type de professeur <span class="text-danger">*</span></label>
                            <select class="form-control form-select" name="typeProfesseur" id="typeProfesseur">
                                <option value="">Sélectionner un type...</option>
                                @foreach (\App\Models\Typeprofesseur::all() as $tp)
                                <option value="{{$tp->codTypProf}}">{{$tp->libTypProf}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <button class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
        <div class="card p-2">
            <table class="table table-striped datatable">
                <thead>
                    <th>#</th>
                    <th>Nom Complet</th>
                    <th>Date & lieu de naissance</th>
                    <th>Contacts</th>
                    <th>Actions</th>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>{{$loop->iteration}}</td>
                            <td>{{$item->nomProf}} {{$item->prenomsProf}}</td>
                            <td>{{\Carbon\Carbon::parse($item->DateNais)->format('d/m/Y')}} à {{$item->LieuNais}}</td>
                            <td>Email : {{$item->emailProf}}<br>Téléphone : {{$item->telProf}}</td>
                            <td>
                                <form action="{{ route('enseignants.destroy', $item->numeroProf) }}" method="post">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn"><i class="bi bi-eye" data-bs-toggle="modal" data-bs-target="#modalId{{$loop->iteration}}"></i></button>

                                    <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#exampleModal{{$loop->iteration}}"><i class="bi bi-pencil text-secondary"></i></button>

                                    <button type="submit" onclick="return confirm('Etes vous sûr de vouloir supprimer {{$item->nomEtu}} {{$item->prenomsEtu}}?')" class="btn"><i class="bi bi-trash text-danger"></i></button>
                                </form>
                            </td>
                        </tr>
                        <div class="modal fade" id="modalId{{$loop->iteration}}" tabindex="-1" role="dialog" aria-labelledby="modalTitleId" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="modalTitleId">A propos de l'enseignant</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        Nom : {{$item->nomProf}}
                                        <hr>
                                        Prénom(s) : {{$item->prenomsProf}}
                                        <hr>
                                        Date de naissance : {{$item->DateNais}}
                                        <hr>
                                        Lieu de naissance : {{$item->LieuNais}}
                                        <hr>
                                        Nationalité : {{$item->nationalite->libelleNationalite}}
                                        <hr>
                                        Sexe : {{$item->Sexe}}
                                        <hr>
                                        Téléphone : {{$item->telProf}}
                                        <hr>
                                        Email : {{$item->emailProf}}
                                        <hr>
                                        Type de professeur : {{$item->typeprofesseur->libTypProf}}
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="exampleModal{{$loop->iteration}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Modifier un domaine</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" action="{{ route('enseignants.update', $item->numeroProf) }}" id="modalUpd{{$loop->iteration}}">

@csrf
                                        @method('PATCH')
                                        <div class="row g-3">
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label" for="nom">Nom <span class="text-danger">*</span></label>
                                                    <input value="{{$item->nomProf}}" type="text" name="nom" id="nom" class="form-control" placeholder="Doe">
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label" for="prenom">Prénom(s) <span class="text-danger">*</span></label>
                                                    <input value="{{$item->prenomsProf}}" type="text" name="prenom" id="prenom" class="form-control" placeholder="John">
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label" for="dateNaissance">Date de naissance <span class="text-danger">*</span></label>
                                                    <input value="{{$item->DateNais}}" type="date" name="dateNaissance" id="dateNaissance" class="form-control" placeholder="01/01/2001">
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label" for="lieuNaissance">Lieu de naissance <span class="text-danger">*</span></label>
                                                    <input value="{{$item->LieuNais}}" type="text" name="lieuNaissance" id="lieuNaissance" class="form-control" placeholder="Nowhere">
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label" for="nationalite">Nationalité <span class="text-danger">*</span></label>
                                                    <select class="form-control form-select" name="nationalite" id="nationalite">
                                                        <option value="">Sélectionner une nationalité...</option>
                                                        @foreach (\App\Models\Nationalite::all() as $nationalite)
                                                        <option value="{{$nationalite->codeNationalite}}" @selected($nationalite->codeNationalite==$item->codeNationalite)>{{$nationalite->libelleNationalite}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label" for="sexe">Sexe <span class="text-danger">*</span></label>
                                                    <select class="form-control form-select" name="sexe" id="sexe">
                                                        <option value="">Sélectionner un genre...</option>
                                                        <option value="M" @selected($item->Sexe=="M")>Masculin</option>
                                                        <option value="F" @selected($item->Sexe=="F")>Féminin</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label" for="telephone">Téléphone <span class="text-danger">*</span></label>
                                                    <input value="{{$item->telProf}}" type="text" name="telephone" id="telephone" class="form-control" placeholder="+229 90011234">
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                                                    <input value="{{$item->emailProf}}" type="email" name="email" id="email" class="form-control" placeholder="nom@example.com">
                                                </div>
                                            </div>
                                            <div class="col-lg-12 col-sm-12 col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label" for="typeProfesseur">Type du professeur <span class="text-danger">*</span></label>
                                                    <select class="form-control form-select" name="typeProfesseur" id="typeProfesseur">
                                                        <option value="">Sélectionner un type...</option>
                                                        @foreach (\App\Models\Typeprofesseur::all() as $tp)
                                                        <option value="{{$tp->codTypProf}}" @selected($tp->codTypProf==$item->codTypProf)>{{$tp->libTypProf}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
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
@section('js')
<script>
    $(document).ready(function() {
        $('select').select2({
            theme: "bootstrap-5",
        });
    });
  </script>
@endsection
