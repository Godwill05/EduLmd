@extends('layout')
@section('title')
Filières complètes
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
            <h5 class="fw-bold">Constituer une filière complète</h5>
            <form action="{{ route('filieres.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <label for="nom" class="form-label">Nom</label>
                        <input type="text" class="form-control" name="nom" id="nom" placeholder="Gestion Transport et Logistique">
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <label class="form-label" for="annee1">Année d'étude 1</label>
                        <select name="annee1" id="annee1" class="form-control form-select
                        @error('annee1')
                        is-invalid
                        @enderror">
                            <option value="">Sélectionner une année d'étude...</option>
                            @foreach (\App\Models\AnneeEtude::whereDoesntHave('filierescompletes1')->whereDoesntHave('filierescompletes2')->whereDoesntHave('filierescompletes3')->get() as $anEtd)
                            <option value="{{$anEtd->Code_Annee_Etude}}">{{$anEtd->Libelle_AnEtude}}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Ce champ est obligatoire !</div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <label class="form-label" for="annee2">Année d'étude 2</label>
                        <select name="annee2" id="annee2" class="form-control form-select
                        @error('annee2')
                        is-invalid
                        @enderror">
                            <option value="">Sélectionner une année d'étude...</option>
                            @foreach (\App\Models\AnneeEtude::whereDoesntHave('filierescompletes1')->whereDoesntHave('filierescompletes2')->whereDoesntHave('filierescompletes3')->get() as $anEtd)
                            <option value="{{$anEtd->Code_Annee_Etude}}">{{$anEtd->Libelle_AnEtude}}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Ce champ est obligatoire !</div>
                    </div>
                    <div class="col-lg-6 col-sm-12 col-md-12">
                        <label class="form-label" for="annee3">Année d'étude 3 <span class="fst-italic text-danger fw-bold">(Licence uniquement !)</span></label>
                        <select name="annee3" id="annee3" class="form-control form-select
                        @error('annee3')
                        is-invalid
                        @enderror">
                            <option value="">Sélectionner une année d'étude...</option>
                            @foreach (\App\Models\AnneeEtude::whereDoesntHave('filierescompletes1')->whereDoesntHave('filierescompletes2')->whereDoesntHave('filierescompletes3')->get() as $anEtd)
                            <option value="{{$anEtd->Code_Annee_Etude}}">{{$anEtd->Libelle_AnEtude}}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Ce champ est obligatoire !</div>
                    </div>
                </div>
                <div class="text-end mt-4">
                    <button class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
    <div class="row">
        <div class="card p-2">
            <table class="table table-striped datatable">
                <thead>
                    <th>Nom</th>
                    <th>Année Etude 1</th>
                    <th>Année Etude 2</th>
                    <th>Année Etude 3</th>
                    <th>Actions</th>
                </thead>
                <tbody>
                    @foreach (\App\Models\FiliereCpl::all() as $fc)
                        <tr>
                            <td>{{$fc->nom}}</td>
                            <td>{{$fc->anneeEtude1->Libelle_AnEtude}}</td>
                            <td>{{$fc->anneeEtude2->Libelle_AnEtude}}</td>
                            <td>{{$fc->anneeEtude3->Libelle_AnEtude ?? '-'}}</td>
                            <td>
                                <form action="{{ route('filieres.destroy', $fc->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#exampleModal{{$loop->iteration}}"><i class=" ri-pencil-line text-secondary"></i></button>

                                    <button type="submit" onclick="return confirm('Etes vous sûr de vouloir supprimer {{$fc->nom}}?')" class="btn"><i class=" ri-delete-bin-line text-danger"></i></button>
                                </form>
                            </td>
                        </tr>
                        <div class="modal fade" id="exampleModal{{$loop->iteration}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Modifier un domaine</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" action="{{ route('filieres.update', $fc->id) }}" id="modalUpd{{$loop->iteration}}">
                                        @csrf
                                        @method('PATCH')
                                        <div class="row g-3">
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <label for="nom" class="form-label">Nom</label>
                                                <input type="text" class="form-control" name="nom" id="nom" value="{{$fc->nom}}">
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <label class="form-label" for="annee1">Année d'étude 1</label>
                                                <select name="annee1" id="annee1" class="form-control form-select
                                                @error('annee1')
                                                is-invalid
                                                @enderror">
                                                    <option value="">Sélectionner une année d'étude...</option>
                                                    @foreach (\App\Models\AnneeEtude::all() as $anEtd)
                                                    <option value="{{$anEtd->Code_Annee_Etude}}" @selected($anEtd->Code_Annee_Etude==$fc->annee1)>{{$anEtd->Libelle_AnEtude}}</option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback">Ce champ est obligatoire !</div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <label class="form-label" for="annee2">Année d'étude 2</label>
                                                <select name="annee2" id="annee2" class="form-control form-select
                                                @error('annee2')
                                                is-invalid
                                                @enderror">
                                                    <option value="">Sélectionner une année d'étude...</option>
                                                    @foreach (\App\Models\AnneeEtude::all() as $anEtd)
                                                    <option value="{{$anEtd->Code_Annee_Etude}}" @selected($anEtd->Code_Annee_Etude==$fc->annee2)>{{$anEtd->Libelle_AnEtude}}</option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback">Ce champ est obligatoire !</div>
                                            </div>
                                            <div class="col-lg-6 col-sm-12 col-md-12">
                                                <label class="form-label" for="annee3">Année d'étude 3 <span class="fst-italic text-danger fw-bold">(Licence uniquement !)</span></label>
                                                <select name="annee3" id="annee3" class="form-control form-select
                                                @error('annee3')
                                                is-invalid
                                                @enderror">
                                                    <option value="">Sélectionner une année d'étude...</option>
                                                    @foreach (\App\Models\AnneeEtude::all() as $anEtd)
                                                    <option value="{{$anEtd->Code_Annee_Etude}}" @selected($anEtd->Code_Annee_Etude==$fc->annee3)>{{$anEtd->Libelle_AnEtude}}</option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback">Ce champ est obligatoire !</div>
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
